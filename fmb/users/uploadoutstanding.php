<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$canImport = user_email_in(DATA_IMPORT_EMAILS);

// Change this each year (or move it to your config file)
$currentYear = '1447-1448';
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-6">
                <h2 class="mb-3">Upload FMB Report</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if ($canImport) { ?>
                    <form id="uploadreciept" class="form-horizontal my-3" method="POST"
                        action="uploadoutstanding.php" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3 row">
                            <label for="import_reciept" class="col-4 control-label">Import FMB Report</label>
                            <div class="col-4">
                                <input type="file" class="form-control" name="import_reciept" accept=".xls,.xlsx" id="import_reciept">
                            </div>
                            <div class="col-4">
                                <button class="btn btn-light" type="submit" name="import">Import</button>
                            </div>
                        </div>
                    </form>
                <?php } ?>

                <?php if ($canImport && isset($_POST['import']) && isset($_FILES['import_reciept'])) {

                    // ---- Upload validation ----
                    $upload = $_FILES['import_reciept'];
                    $ext = strtolower(pathinfo($upload['name'] ?? '', PATHINFO_EXTENSION));
                    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !in_array($ext, ['xls', 'xlsx'], true)) {
                        echo "<p style='color:red;'>Please choose a valid .xls or .xlsx file.</p>";
                        include('footer.php');
                        exit;
                    }

                    $filePath = $upload['tmp_name'];

                    try {
                        $spreadsheet = IOFactory::load($filePath);
                    } catch (\Throwable $e) {
                        error_log('[uploadoutstanding.php] ' . $e->getMessage());
                        echo "Could not read that file. Please check it's a valid .xls/.xlsx export.";
                        include('footer.php');
                        exit;
                    }

                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();

                    // Reports may have a title row above the real column headings.
                    // Resolve columns by heading name so extra or moved columns do not
                    // break the import.
                    $normalizeHeader = function ($v) {
                        // Lowercase FIRST, then collapse non-alphanumerics. (The other way
                        // round stripped every capital letter, so "ITS ID" became "" and
                        // no heading ever matched.)
                        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower((string) $v)));
                    };

                    $headerRowIndex = null;
                    $columns = [];
                    foreach ($rows as $index => $row) {
                        $candidate = [];
                        foreach ($row as $columnIndex => $heading) {
                            $normalized = $normalizeHeader($heading);
                            if ($normalized !== '') {
                                $candidate[$normalized] = $columnIndex;
                            }
                        }

                        if (
                            isset($candidate['its id']) ||
                            isset($candidate['its no']) ||
                            isset($candidate['its'])
                        ) {
                            $headerRowIndex = $index;
                            $columns = $candidate;
                            break;
                        }
                    }

                    if ($headerRowIndex === null) {
                        // Legacy exports did not always include recognized headings.
                        // Preserve support for them using the old column positions.
                        $headerRowIndex = -1;
                    }

                    $columnIndex = function (array $names) use ($columns) {
                        foreach ($names as $name) {
                            if (isset($columns[$name])) {
                                return $columns[$name];
                            }
                        }
                        return null;
                    };

                    $itsColumn       = $columnIndex(['its id', 'its no', 'its']);
                    $sabeelColumn    = $columnIndex(['sabeel number', 'sabeel no', 'sabeel']);
                    $yearColumn      = $columnIndex(['year']);
                    $outstandingColumn = $columnIndex(['niyaz outstanding', 'outstanding']);
                    $takmeemColumn   = $columnIndex(['total takhmeen', 'niyaz takhmeen', 'takhmeen', 'takmeem']);
                    $previousColumn  = $columnIndex(['previous due']);

                    // Fallback for an older workbook that had no descriptive headers.
                    if ($itsColumn === null) $itsColumn = 0;
                    if ($sabeelColumn === null) $sabeelColumn = 2;
                    if ($yearColumn === null) $yearColumn = 5;
                    if ($outstandingColumn === null) $outstandingColumn = 7;
                    if ($takmeemColumn === null) $takmeemColumn = 10;
                    if ($previousColumn === null) $previousColumn = 15;

                    $rows = array_slice($rows, $headerRowIndex + 1);

                    // Convert "1,250.50" / "-3" / null to a rounded int
                    $num = function ($v) {
                        return (int) round((float) str_replace(',', '', (string) ($v ?? 0)));
                    };

                    // Pass 1: the latest year present for each ITS. A member can appear
                    // on several rows (one per takhmeen year); only the latest row may
                    // write, so the result no longer depends on the order of the rows.
                    // Years look like "1447-1448", so a plain string compare works.
                    $latestYear = [];
                    foreach ($rows as $row) {
                        $k = trim((string) ($row[$itsColumn] ?? ''));
                        $y = trim((string) ($row[$yearColumn] ?? ''));
                        if ($k === '' || !preg_match('/^\d{8}$/', $k)) {
                            continue;
                        }
                        if (!isset($latestYear[$k]) || strcmp($y, $latestYear[$k]) > 0) {
                            $latestYear[$k] = $y;
                        }
                    }

                    $updated       = 0;
                    $skipped       = 0;
                    $notFound      = 0;
                    $thaliConflict = 0;
                    $its = '';
                    $sabeelNo = '';
                    $failed = false;

                    try {
                        $link->begin_transaction();

                        // Pass 2: process rows
                        foreach ($rows as $row) {
                            $its      = trim((string) ($row[$itsColumn] ?? ''));
                            $sabeelNo = trim((string) ($row[$sabeelColumn] ?? ''));
                            $rowYear  = trim((string) ($row[$yearColumn] ?? ''));

                            // Skip the totals row at the bottom of the report
                            if (stripos($its, 'GRAND TOTAL') !== false || stripos($rowYear, 'GRAND TOTAL') !== false) {
                                continue;
                            }

                            // A valid ITS is exactly 8 digits (blocks ITS "0" etc.)
                            if (!preg_match('/^\d{8}$/', $its)) {
                                $its = '';
                            }

                            $outstanding = max(0, $num($row[$outstandingColumn] ?? 0));
                            $sheetPrev   = max(0, $num($row[$previousColumn] ?? 0));

                            // A newer row exists for this member: don't let an older row overwrite it
                            if ($its !== '' && isset($latestYear[$its]) && strcmp($rowYear, $latestYear[$its]) < 0) {
                                $skipped++;
                                continue;
                            }

                            if ($rowYear === $currentYear) {
                                $takmeem = max(0, $num($row[$takmeemColumn] ?? 0));
                                $prev    = max($sheetPrev, $outstanding - $takmeem);
                                $paid    = max(0, $sheetPrev + $takmeem - $outstanding);
                            } else {
                                // Latest row is an older year: carry its balance forward as Previous Due
                                // Otherwise carry the old balance forward as Previous Due
                                $takmeem = 0;
                                $prev    = $outstanding;
                                $paid    = 0;
                            }

                            // Find the member's row id: ITS first, then sabeel number
                            $id = null;

                            if ($its !== '') {
                                $res = db_query($link, "SELECT id FROM thalilist WHERE ITS_No = ? LIMIT 1", "s", [$its]);
                                if ($res->num_rows > 0) {
                                    $id = $res->fetch_assoc()['id'];
                                }
                            }

                            if ($id === null && $sabeelNo !== '') {
                                $res = db_query($link, "SELECT id FROM thalilist WHERE Thali = ? LIMIT 1", "s", [$sabeelNo]);
                                if ($res->num_rows > 0) {
                                    $id = $res->fetch_assoc()['id'];
                                }
                            }

                            if ($id === null) {
                                $notFound++;
                                echo "<p style='color:red;'>Skipped : ITS " . e($its) . " / Sabeel " . e($sabeelNo) . " not found.</p>";
                                continue;
                            }

                            // Build the UPDATE (parameterised)
                            $setColumns = [];
                            $setParams  = [];
                            $setTypes   = '';

                            // Thali has a UNIQUE key: only write it if no OTHER member already holds it
                            if ($sabeelNo !== '') {
                                $dup = db_query($link, "SELECT id FROM thalilist WHERE Thali = ? AND id <> ? LIMIT 1", "si", [$sabeelNo, $id]);
                                if ($dup->num_rows > 0) {
                                    $thaliConflict++;
                                    echo "<p style='color:#b36b00;'>Warning : sabeel " . e($sabeelNo)
                                        . " is already used by another member, so Thali was NOT changed for ITS "
                                        . e($its) . " (dues were still updated).</p>";
                                } else {
                                    $setColumns[] = '`Thali` = ?';
                                    $setParams[]  = $sabeelNo;
                                    $setTypes    .= 's';
                                }
                            }

                            $setColumns[] = '`Previous_Due` = ?';
                            $setParams[]  = $prev;
                            $setTypes    .= 'i';

                            $setColumns[] = '`yearly_hub` = ?';
                            $setParams[]  = $takmeem;
                            $setTypes    .= 'i';

                            $setColumns[] = '`Paid` = ?';
                            $setParams[]  = $paid;
                            $setTypes    .= 'i';

                            $setParams[] = $id;
                            $setTypes   .= 'i';

                            db_query(
                                $link,
                                "UPDATE thalilist SET " . implode(', ', $setColumns) . " WHERE id = ?",
                                $setTypes,
                                $setParams
                            );
                            $updated++;
                        }

                        $link->commit();
                    } catch (\Throwable $e) {
                        $link->rollback();
                        $failed = true;
                        error_log('[uploadoutstanding.php] import failed: ' . $e->getMessage());
                        echo "<p style='color:red;'>Import FAILED and was rolled back; nothing was saved. Last row: ITS "
                            . e($its) . " / Sabeel " . e($sabeelNo) . ". Error: " . e($e->getMessage()) . "</p>";
                    }

                    if (!$failed) echo "<p style='color:green;'>Import complete. Updated: " . (int) $updated
                        . ", Skipped (older duplicate): " . (int) $skipped
                        . ", Not found: " . (int) $notFound
                        . ", Thali conflicts: " . (int) $thaliConflict . "</p>";
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

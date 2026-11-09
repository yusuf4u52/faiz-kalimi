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
                    array_shift($rows); // header row

                    // Convert "1,250.50" / "-3" / null to a rounded int
                    $num = function ($v) {
                        return (int) round((float) str_replace(',', '', (string) ($v ?? 0)));
                    };

                    // Pass 1: ITS numbers that have a current-year row
                    $currentIts = [];
                    foreach ($rows as $row) {
                        if (trim((string) ($row[5] ?? '')) === $currentYear) {
                            $currentIts[trim((string) ($row[0] ?? ''))] = true;
                        }
                    }

                    $updated  = 0;
                    $skipped  = 0;
                    $notFound = 0;
                    $its = '';
                    $sabeelNo = '';

                    try {
                        $link->begin_transaction();

                        // Pass 2: process rows
                        foreach ($rows as $row) {
                            $its      = trim((string) ($row[0] ?? ''));
                            $sabeelNo = trim((string) ($row[2] ?? ''));
                            $rowYear  = trim((string) ($row[5] ?? ''));

                            // Skip the totals row at the bottom of the report
                            if (stripos($its, 'GRAND TOTAL') !== false || stripos($rowYear, 'GRAND TOTAL') !== false) {
                                continue;
                            }

                            // A valid ITS is exactly 8 digits (blocks ITS "0" etc.)
                            if (!preg_match('/^\d{8}$/', $its)) {
                                $its = '';
                            }

                            $outstanding = max(0, $num($row[7] ?? 0));   // Niyaz (Outstanding)
                            $sheetPrev   = max(0, $num($row[15] ?? 0));  // Previous Due

                            if ($rowYear === $currentYear) {
                                $takmeem = max(0, $num($row[10] ?? 0));  // Total Takhmeen
                                $prev    = max($sheetPrev, $outstanding - $takmeem);
                                $paid    = max(0, $sheetPrev + $takmeem - $outstanding);
                            } else {
                                // Older-year row: never overwrite a member who has a current-year row
                                if ($its !== '' && isset($currentIts[$its])) {
                                    $skipped++;
                                    continue;
                                }
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

                            if ($sabeelNo !== '') {
                                $setColumns[] = '`Thali` = ?';
                                $setParams[]  = $sabeelNo;
                                $setTypes    .= 's';
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
                        error_log('[uploadoutstanding.php] import failed: ' . $e->getMessage());
                        // TEMPORARY diagnostic output - remove the detail lines once the issue is fixed
                        echo "<p style='color:red;'>Import failed, nothing saved.<br>"
                            . "Last row: ITS " . e($its) . " / Sabeel " . e($sabeelNo) . "<br>"
                            . "Error: " . e($e->getMessage()) . " (line " . (int) $e->getLine() . ")</p>";
                        include('footer.php');
                        exit;
                    }

                    echo "<p style='color:green;'>Import complete. Updated: " . (int) $updated
                        . ", Skipped (older duplicate): " . (int) $skipped
                        . ", Not found: " . (int) $notFound . "</p>";
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

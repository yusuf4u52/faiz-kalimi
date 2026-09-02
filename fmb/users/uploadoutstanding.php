<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$canImport = user_email_in(DATA_IMPORT_EMAILS);
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
                    $filePath = $_FILES['import_reciept']['tmp_name'];

                    try {
                        $spreadsheet = IOFactory::load($filePath);
                    } catch (Exception $e) {
                        error_log('[uploadoutstanding.php] ' . $e->getMessage());
                        echo "Could not read that file. Please check it's a valid .xls/.xlsx export.";
                        include('footer.php');
                        exit;
                    }

                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();
                    array_shift($rows); // header row

                    foreach ($rows as $row) {
                        $its = trim((string) ($row[0] ?? ''));
                        $sabeelNo = trim((string) ($row[2] ?? ''));
                        $outstanding = (int) str_replace(',', '', (string) ($row[7] ?? '0'));

                        $takmeem = 0;
                        if (trim((string) ($row[5] ?? '')) === '1447-1448') {
                            $takmeem = (int) ($row[10] ?? 0);
                        } elseif ((int) ($row[10] ?? 0) > 0) {
                            $takmeem = 1;
                        }

                        if ($takmeem <= 0) {
                            continue;
                        }

                        if ($takmeem > $outstanding) {
                            $prev = 0;
                            $paid = $takmeem - $outstanding;
                        } else {
                            $prev = $outstanding - $takmeem;
                            $paid = 0;
                        }

                        // Figure out which column identifies this member —
                        // ITS number first, falling back to sabeel number —
                        // then build the matching WHERE clause as a
                        // parameter rather than string-interpolated SQL.
                        $whereColumn = null;
                        $whereValue = null;

                        if ($its !== '') {
                            $checkIts = db_query($link, "SELECT id FROM thalilist WHERE ITS_No = ? LIMIT 1", "s", [$its]);
                            if ($checkIts->num_rows > 0) {
                                $whereColumn = 'ITS_No';
                                $whereValue = $its;
                            }
                        }

                        if ($whereColumn === null && $sabeelNo !== '') {
                            $checkSabeel = db_query($link, "SELECT id FROM thalilist WHERE Thali = ? LIMIT 1", "s", [$sabeelNo]);
                            if ($checkSabeel->num_rows > 0) {
                                $whereColumn = 'Thali';
                                $whereValue = $sabeelNo;
                            }
                        }

                        if ($whereColumn === null) {
                            echo "<p style='color:red;'>Skipped : ITS " . e($its) . " / Sabeel " . e($sabeelNo) . " not found.</p>";
                            continue;
                        }

                        $setColumns = [];
                        $setParams = [];
                        $setTypes = '';

                        if ($sabeelNo !== '') {
                            $setColumns[] = '`Thali` = ?';
                            $setParams[] = $sabeelNo;
                            $setTypes .= 's';
                        }
                        
                        $setColumns[] = '`Previous_Due` = ?';
                        $setParams[] = $prev;
                        $setTypes .= 'i';
                        $setColumns[] = '`yearly_hub` = ?';
                        $setParams[] = $takmeem;
                        $setTypes .= 'i';
                        $setColumns[] = '`Paid` = ?';
                        $setParams[] = $paid;
                        $setTypes .= 'i';

                        $setParams[] = $whereValue;
                        $setTypes .= 's';

                        db_query(
                            $link,
                            "UPDATE thalilist SET " . implode(', ', $setColumns) . " WHERE `$whereColumn` = ?",
                            $setTypes,
                            $setParams
                        );
                    }
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

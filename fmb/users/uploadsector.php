<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$canImport = user_email_in(SECTOR_IMPORT_EMAILS);
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-6">
                <h2 class="mb-3">Upload Thali List</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if ($canImport) { ?>
                    <form id="uploadsector" class="form-horizontal my-3" method="POST"
                        action="uploadsector.php" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3 row">
                            <label for="import_reciept" class="col-4 control-label">Import Sector List</label>
                            <div class="col-4">
                                <input type="file" class="form-control" name="import_sector" accept=".xls,.xlsx" id="import_sector">
                            </div>
                            <div class="col-4">
                                <button class="btn btn-light" type="submit" name="import">Import</button>
                            </div>
                        </div>
                    </form>
                <?php } ?>
                <?php if ($canImport && isset($_POST['import']) && isset($_FILES['import_sector'])) {
                    $filePath = $_FILES['import_sector']['tmp_name'];

                    $spreadsheet = IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();
                    array_shift($rows); // header row

                    foreach ($rows as $row) {
                        $itsNo = trim((string) ($row[1] ?? ''));
                        if ($itsNo === '') {
                            continue;
                        }

                        if (($row[15] ?? '') === 'Transfer') {
                            db_query(
                                $link,
                                "UPDATE thalilist SET `Active` = 0, `hardstop` = 1, `hardstop_comment` = ? WHERE `ITS_No` = ?",
                                "ss",
                                [(string) ($row[16] ?? ''), $itsNo]
                            );
                            echo '<h4 class="text-danger">' . e($itsNo) . ' Transfer successfully</h4>';
                        } elseif (is_numeric($row[12] ?? null) && (float) $row[12] > 0) {
                            $zabihat = (empty($row[13]) && $row[13] === '') ? 0 : (float) $row[13];

                            db_query(
                                $link,
                                "UPDATE thalilist SET `Active` = 1, `yearly_hub` = ?, `Zabihat` = ? WHERE `ITS_No` = ?",
                                "dds",
                                [(float) $row[12], $zabihat, $itsNo]
                            );
                            echo '<h4 class="text-success">' . e($itsNo) . ' updated successfully</h4>';
                        } else {
                            db_query(
                                $link,
                                "UPDATE thalilist SET `Active` = 0, `yearly_hub` = 0, `Zabihat` = 0 WHERE `ITS_No` = ?",
                                "s",
                                [$itsNo]
                            );
                            echo '<h4 class="text-warning">' . e($itsNo) . ' Stopped successfully</h4>';
                        }
                    }
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

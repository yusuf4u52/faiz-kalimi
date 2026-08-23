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
                <h2 class="mb-3">Upload FMB Reciept</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if ($canImport) { ?>
                    <form id="uploadreciept" class="form-horizontal my-3" method="POST"
                        action="uploadreciept.php" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3 row">
                            <label for="import_reciept" class="col-4 control-label">Import FMB Reciept</label>
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

                    $spreadsheet = IOFactory::load($filePath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();
                    array_shift($rows); // header row

                    foreach ($rows as $row) {
                        $Receipt_No = trim((string) ($row[0] ?? ''));
                        $its = trim((string) ($row[2] ?? ''));
                        $takhmeemYear = trim((string) ($row[15] ?? ''));
                        $receiptsTable = $takhmeemYear === '1447-1448' ? 'receipts' : 'receipts_1447';
                        $date = date('Y-m-d', strtotime((string) ($row[1] ?? '')));

                        if ($Receipt_No === '' || $its === '') {
                            continue;
                        }

                        $thalilistResult = db_query($link, "SELECT id, Thali FROM thalilist WHERE `ITS_No` = ? LIMIT 1", "s", [$its]);
                        if ($thalilistResult->num_rows === 0) {
                            continue;
                        }
                        $thali = mysqli_fetch_assoc($thalilistResult);

                        // Single upsert instead of SELECT-then-branch.
                        db_query(
                            $link,
                            "INSERT INTO `$receiptsTable`
                                (`Receipt_No`, `Thali_No`, `userid`, `name`, `Amount`, `Date`, `received_by`, `payment_type`, `transaction_id`, `takmeem_year`)
                             VALUES (?, ?, ?, ?, ?, ?, 'saminabarnagarwala2812@gmail.com', ?, ?, ?)
                             ON DUPLICATE KEY UPDATE
                                `Thali_No` = VALUES(`Thali_No`),
                                `userid` = VALUES(`userid`),
                                `name` = VALUES(`name`),
                                `Amount` = VALUES(`Amount`),
                                `received_by` = VALUES(`received_by`),
                                `payment_type` = VALUES(`payment_type`),
                                `transaction_id` = VALUES(`transaction_id`),
                                `takmeem_year` = VALUES(`takmeem_year`)",
                            "ssisdssss",
                            [
                                $Receipt_No,
                                (string) $thali['Thali'],
                                (int) $thali['id'],
                                (string) ($row[4] ?? ''),
                                (float) ($row[6] ?? 0),
                                $date,
                                (string) ($row[9] ?? ''),
                                (string) ($row[14] ?? ''),
                                (string) ($row[15] ?? ''),
                            ]
                        );

                        $wasUpdate = mysqli_affected_rows($link) > 1;
                        echo '<h4>' . e($its) . ' reciept ' . ($wasUpdate ? 'updated' : 'inserted') . ' successfully</h4>';
                    }
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

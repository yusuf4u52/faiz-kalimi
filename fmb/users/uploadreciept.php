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

                    // Reports may have a title row above the real column headings.
                    $normalizeHeader = function ($v) {
                        return strtolower(trim(preg_replace('/[^a-z0-9]+/', ' ', (string) $v)));
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
                            isset($candidate['receiptno']) ||
                            isset($candidate['recieptno']) ||
                            isset($candidate['reciept no']) ||
                            isset($candidate['receipt no'])
                        ) {
                            $headerRowIndex = $index;
                            $columns = $candidate;
                            break;
                        }
                    }

                    if ($headerRowIndex === null) {
                        // Legacy exports did not include a separate title row.
                        $headerRowIndex = 0;
                    }

                    $columnIndex = function (array $names) use ($columns) {
                        foreach ($names as $name) {
                            if (isset($columns[$name])) {
                                return $columns[$name];
                            }
                        }
                        return null;
                    };

                    $receiptColumn = $columnIndex(['receiptno', 'recieptno', 'reciept no', 'receipt no']);
                    $dateColumn = $columnIndex(['date']);
                    $itsColumn = $columnIndex(['its no', 'its']);
                    $nameColumn = $columnIndex(['full name']);
                    $receiptNameColumn = $columnIndex(['reciept name', 'receipt name']);
                    $sabeelColumn = $columnIndex(['sabeel number', 'sabeel no', 'sabeel']);
                    $amountColumn = $columnIndex(['amount']);
                    $paymentColumn = $columnIndex(['paytype', 'pay type', 'payment type']);
                    $transactionColumn = $columnIndex(['reference no']);
                    $yearColumn = $columnIndex(['takhmeen year']);

                    // Fallback for older exports using fixed positions.
                    if ($receiptColumn === null) $receiptColumn = 0;
                    if ($dateColumn === null) $dateColumn = 1;
                    if ($itsColumn === null) $itsColumn = 2;
                    if ($nameColumn === null) $nameColumn = 4;
                    if ($sabeelColumn === null) $sabeelColumn = 7;
                    if ($amountColumn === null) $amountColumn = 6;
                    if ($paymentColumn === null) $paymentColumn = 9;
                    if ($transactionColumn === null) $transactionColumn = 14;
                    if ($yearColumn === null) $yearColumn = 15;

                    $inserted = 0;
                    $updated = 0;
                    $notFound = 0;
                    $skipped = 0;

                    foreach ($rows as $row) {
                        $Receipt_No = trim((string) ($row[$receiptColumn] ?? ''));

                        // If the heading is missing or shifted, find the receipt number
                        // itself. FMB receipt numbers are short numeric values near the
                        // start of the row (usually 1759, 1760, and so on).
                        if (!preg_match('/^\d{3,6}$/', $Receipt_No)) {
                            foreach (array_slice($row, 0, 6) as $candidate) {
                                $candidate = trim((string) $candidate);
                                if (preg_match('/^\d{3,6}$/', $candidate)) {
                                    $Receipt_No = $candidate;
                                    break;
                                }
                            }
                        }

                        $its = trim((string) ($row[$itsColumn] ?? ''));
                        $takhmeemYear = trim((string) ($row[$yearColumn] ?? ''));
                        $receiptsTable = $takhmeemYear === '1447-1448' ? 'receipts' : 'receipts_1447';
                        $dateTimestamp = strtotime((string) ($row[$dateColumn] ?? ''));
                        $date = $dateTimestamp !== false ? date('Y-m-d', $dateTimestamp) : '';

                        if (
                            $Receipt_No === '' ||
                            stripos($Receipt_No, 'grand total') !== false ||
                            stripos($Receipt_No, 'receiptno') !== false ||
                            stripos($Receipt_No, 'reciept no') !== false ||
                            stripos($Receipt_No, 'receipt no') !== false
                        ) {
                            $skipped++;
                            continue;
                        }

                        $sabeelNo = trim((string) ($row[$sabeelColumn] ?? ''));
                        $thalilistResult = null;

                        if (preg_match('/^\d{8}$/', $its)) {
                            $thalilistResult = db_query($link, "SELECT id, Thali FROM thalilist WHERE `ITS_No` = ? LIMIT 1", "s", [$its]);
                        }
                        if (($thalilistResult === null || $thalilistResult->num_rows === 0) && $sabeelNo !== '') {
                            $thalilistResult = db_query($link, "SELECT id, Thali FROM thalilist WHERE `Thali` = ? LIMIT 1", "s", [$sabeelNo]);
                        }
                        if ($thalilistResult === null || $thalilistResult->num_rows === 0) {
                            $notFound++;
                            echo "<p style='color:red;'>Skipped receipt " . e($Receipt_No)
                                . ": ITS " . e($its) . " / Sabeel " . e($sabeelNo) . " not found.</p>";
                            continue;
                        }
                        $thali = mysqli_fetch_assoc($thalilistResult);

                        $name = '';
                        if ($receiptNameColumn !== null) {
                            $name = trim((string) ($row[$receiptNameColumn] ?? ''));
                        }
                        if ($name === '') {
                            $name = trim((string) ($row[$nameColumn] ?? ''));
                        }
                        $amount = (float) str_replace(',', '', (string) ($row[$amountColumn] ?? 0));
                        $paymentType = trim((string) ($row[$paymentColumn] ?? ''));
                        $transactionId = trim((string) ($row[$transactionColumn] ?? ''));

                        try {
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
                                    $name,
                                    $amount,
                                    $date,
                                    $paymentType,
                                    $transactionId,
                                    $takhmeemYear,
                                ]
                            );

                            $wasUpdate = mysqli_affected_rows($link) > 1;
                            if ($wasUpdate) {
                                $updated++;
                            } else {
                                $inserted++;
                            }
                            echo '<h4>' . e($its !== '' ? $its : $sabeelNo) . ' reciept ' . ($wasUpdate ? 'updated' : 'inserted') . ' successfully</h4>';
                        } catch (\Throwable $e) {
                            error_log('[uploadreciept.php] receipt ' . $Receipt_No . ' failed: ' . $e->getMessage());
                            echo "<p style='color:red;'>Could not import receipt " . e($Receipt_No) . ": " . e($e->getMessage()) . "</p>";
                        }
                    }

                    echo "<p style='color:green;'>Import complete. Inserted: " . (int) $inserted
                        . ", Updated: " . (int) $updated
                        . ", Not found: " . (int) $notFound
                        . ", Skipped: " . (int) $skipped . "</p>";
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

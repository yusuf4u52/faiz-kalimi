<?php
include('../connection.php');
include('../_authCheck.php');
require_once('helpers.php');
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!is_roti_privileged_user()) {
    http_response_code(403);
    echo "You are not authorised to import this sheet.";
    exit;
}

if (!isset($_FILES['recieve_import']) || $_FILES['recieve_import']['error'] !== UPLOAD_ERR_OK) {
    echo "No file uploaded.";
    exit;
}

$recievedBy = current_user_email();
if ($recievedBy === null) {
    http_response_code(403);
    echo "Your session has expired. Please log in again.";
    exit;
}

$originalName = $_FILES['recieve_import']['name'];
if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'xlsx') {
    echo "Please upload a .xlsx file.";
    exit;
}

$filePath = $_FILES['recieve_import']['tmp_name'];
$spreadsheet = IOFactory::load($filePath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();
$dateHeaders = array_slice($rows[0], 1); // Get date headers from the first row

for ($i = 1; $i < count($rows); $i++) {
    $row = $rows[$i];
    $code = trim((string) $row[0]);
    if ($code === '') {
        continue;
    }

    $roti_maker = db_query($link, "SELECT `id` FROM fmb_roti_maker WHERE `code` = ? LIMIT 1", "s", [$code]);
    if ($roti_maker->num_rows === 0) {
        continue;
    }
    $maker = mysqli_fetch_assoc($roti_maker);

    for ($j = 1; $j < count($row); $j++) {
        $roti = is_numeric($row[$j]) ? (int) $row[$j] : 0;
        if ($roti <= 0 || empty($dateHeaders[$j - 1])) {
            continue;
        }

        $dateRaw = trim((string) $dateHeaders[$j - 1]);
        $date = date('Y-m-d', strtotime($dateRaw));

        // Single atomic upsert instead of SELECT-then-branch — relies on the
        // UNIQUE KEY (maker_id, recieved_date) added in schema-modernization.sql.
        db_query(
            $link,
            "INSERT INTO fmb_roti_recieved (`maker_id`, `recieved_date`, `roti_recieved`, `roti_status`, `recieved_by`) VALUES (?, ?, ?, 'pending', ?)
             ON DUPLICATE KEY UPDATE
                `roti_recieved` = VALUES(`roti_recieved`),
                `roti_status` = 'pending',
                `recieved_by` = VALUES(`recieved_by`)",
            "isis",
            [$maker['id'], $date, $roti, $recievedBy]
        );
    }
}

header("Location: /fmb/users/roti/recieved.php?action=upload");
exit;

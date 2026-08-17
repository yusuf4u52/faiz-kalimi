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

if (!isset($_FILES['distribute_import']) || $_FILES['distribute_import']['error'] !== UPLOAD_ERR_OK) {
    echo "No file uploaded.";
    exit;
}

$distributedBy = current_user_email();
if ($distributedBy === null) {
    http_response_code(403);
    echo "Your session has expired. Please log in again.";
    exit;
}

$originalName = $_FILES['distribute_import']['name'];
if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'xlsx') {
    echo "Please upload a .xlsx file.";
    exit;
}

$filePath = $_FILES['distribute_import']['tmp_name'];
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
        // No matching maker for this row — skip it rather than silently
        // attributing it to whichever maker matched on a previous row.
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
        // UNIQUE KEY (maker_id, distribution_date) added in
        // schema-modernization.sql. Matters more here than in the form-based
        // save flow, since an import loops over many maker/date cells fast
        // enough that the old two-query race window was easier to hit.
        db_query(
            $link,
            "INSERT INTO fmb_roti_distribution (`maker_id`, `distribution_date`, `roti_recieved`, `roti_status`, `distributed_by`) VALUES (?, ?, ?, 'pending', ?)
             ON DUPLICATE KEY UPDATE
                `roti_recieved` = VALUES(`roti_recieved`),
                `roti_status` = 'pending',
                `distributed_by` = VALUES(`distributed_by`)",
            "isis",
            [$maker['id'], $date, $roti, $distributedBy]
        );
    }
}

header("Location: /testfmb/users/roti/distribute.php?action=upload");
exit;

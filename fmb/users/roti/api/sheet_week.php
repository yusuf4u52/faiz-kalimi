<?php
/**
 * GET /roti/api/sheet_week.php?date=YYYY-MM-DD
 *
 * Returns the single-week sheet (Monday-Saturday, containing whatever date is
 * passed) as JSON: every maker's Opening/Given Atta & Oil, daily roti
 * received, and every computed total/required/closing figure — everything
 * roti/sheet.php needs to render one week.
 */
include('../../connection.php');
include('../../_authCheck.php');
require_once('../helpers.php');
include('../../getHijriDate.php');

header('Content-Type: application/json');

$anchor = $_GET['date'] ?? date('Y-m-d');
if (!DateTime::createFromFormat('Y-m-d', $anchor)) {
    $anchor = date('Y-m-d');
}

$weekStart = week_start_monday($anchor);
echo json_encode(build_week_matrix($link, $weekStart), JSON_UNESCAPED_UNICODE);

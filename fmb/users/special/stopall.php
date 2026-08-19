<?php
include('../connection.php');
include('../_authCheck.php');
require_once('../helpers.php');
include('../getHijriDate.php');

$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
$hijridate = getHijriDate($tomorrow_date);

$action = $_POST['action'] ?? null;

if ($action === 'stop_friday') {
    db_query($link, "UPDATE thalilist SET `Active` = 0, `Thali_stop_date` = ? WHERE `thalisize` = 'Friday'", "s", [$hijridate]);
    header("Location: /fmb/users/special/friday.php?action=delete");
    exit;
}

if ($action === 'stop_barnamaj') {
    db_query($link, "UPDATE thalilist SET `Active` = 0, `Thali_stop_date` = ? WHERE `thalisize` = 'Barnamaj'", "s", [$hijridate]);
    header("Location: /fmb/users/special/barnamaj.php?action=delete");
    exit;
}

header("Location: /fmb/users/index.php");
exit;

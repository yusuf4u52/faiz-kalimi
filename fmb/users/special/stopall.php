<?php
include('../connection.php');
include('../_authCheck.php');
include('getHijriDate.php');

$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
$hijridate = getHijriDate($tomorrow_date);

if (isset($_POST['action']) && $_POST['action'] == 'stop_friday') {
    $stop_friday = "UPDATE thalilist SET `Active` = '0', `Thali_stop_date` = '" . $hijridate . "' WHERE `thalisize` = 'Friday'";
    mysqli_query($link,$stop_friday) or die(mysqli_error($link));
    header("Location: /fmb/users/special/friday.php?action=delete");
}

if (isset($_POST['action']) && $_POST['action'] == 'stop_barnamaj') {
    $stop_barnamaj = "UPDATE thalilist SET `Active` = '0', `Thali_stop_date` = '" . $hijridate . "' WHERE `thalisize` = 'Barnamaj'";
    mysqli_query($link,$stop_barnamaj) or die(mysqli_error($link));
    header("Location: /fmb/users/special/barnamaj.php?action=delete");
}
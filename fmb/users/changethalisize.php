<?php

include('connection.php');
include('_authCheck.php');
include('getHijriDate.php');


$today = getTodayDateHijri();
// mark all previous change sizes as processed before making new entry 
mysqli_query($link, "update change_table set processed = 1 where userid = '" . $_POST['id'] . "' and `Operation` in ('Change Size') and processed = 0") or die(mysqli_error($link));
mysqli_query($link, "UPDATE thalilist set thalisize='" . $_POST['thalisize'] . "' WHERE id = '" . $_POST['id'] . "'") or die(mysqli_error($link));
mysqli_query($link, "INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`,`processed`) VALUES ('" . $_POST['Thali'] . "','" . $_POST['id'] . "', 'Change Size','" . $today . "',0)") or die(mysqli_error($link));

if (isset($_SERVER["HTTP_REFERER"])) {
    header("Location: " . $_SERVER["HTTP_REFERER"]);
}

<?php
include('connection.php');
include('getHijriDate.php');

$today = getTodayDateHijri();
session_start();
if (is_null($_SESSION['fromLogin'])) {
  //send them back\
  header("Location: login.php");
}

// check if request is in cut off time
date_default_timezone_set('Asia/Kolkata');
$cutoffTime = '18:00'; //Cut off at 6 pm
$startTime = '23:59'; //reset back to open at midnight

$time = new DateTime($cutoffTime);
$time1 = date_format($time, 'H:i');
$time = new DateTime($startTime);
$time2 = date_format($time, 'H:i');

$current = date("H:i");
if ($current > $time1 && $current < $time2) {
  $cutoffmessage =  'Start thali not allowed post 6 PM. Please message 9503054797';
  header("Location: index.php?status=$cutoffmessage");
  exit;
}

$query = "SELECT * FROM thalilist where id = '" . $_SESSION['thaliid'] . "'";
$values = mysqli_fetch_assoc(mysqli_query($link, $query));

if ($values['hardstop'] == 1) exit;

mysqli_query($link, "UPDATE thalilist set Active='1' WHERE id = '" . $_SESSION['thaliid'] . "'") or die(mysqli_error($link));
mysqli_query($link, "UPDATE thalilist set Thali_start_date='" . $today . "' WHERE id = '" . $values['id'] . "'") or die(mysqli_error($link));


mysqli_query($link, "update change_table set processed = 1 where userid = '" . $_SESSION['thaliid'] . "' and `Operation` in ('Start Thali','Stop Thali') and processed = 0") or die(mysqli_error($link));
mysqli_query($link, "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES ('" . $values['Thali'] . "','" . $_SESSION['thaliid'] . "', 'Start Thali','" . $today . "')") or die(mysqli_error($link));

$status = 'Start Thali Successful';
header("Location: index.php?status=$status");

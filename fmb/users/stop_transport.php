<?php
include('connection.php');
include('getHijriDate.php');

$today = getTodayDateHijri();
session_start();

if (is_null($_SESSION['fromLogin'])) {

 //send them back
   header("Location: login.php");
}
$update = mysqli_query($link,"UPDATE thalilist set Transporter='Pick Up' WHERE id = '".$_SESSION['thaliid']."'") or die(mysqli_error($link));

mysqli_query($link,"update change_table set processed = 1 where userid = '".$_SESSION['thaliid']."' and `Operation` in ('Start Transport','Stop Transport') and processed = 0") or die(mysqli_error($link));
mysqli_query($link,"INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`) VALUES ('".$_SESSION['thali']."','".$_SESSION['thaliid']."', 'Stop Transport','" . $today . "')") or die(mysqli_error($link));

$status = "Pick Up request submitted";
header("Location: index.php?status=$status");
?>
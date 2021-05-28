<?php
include('connection.php');
include('_authCheck.php');
extract($_POST);

$update_query = "UPDATE thalilist SET
sector = '". mysqli_real_escape_string($link,$sector)."',
subsector = '".mysqli_real_escape_string($link,$subsector)."',
Transporter = '".mysqli_real_escape_string($link, $transporter)."'
WHERE Thali = '".mysqli_real_escape_string($link, $Thali)."'";

$result = mysqli_query($link,$update_query) or die(mysqli_error($link));

header("Location: pendingactions.php");
?>
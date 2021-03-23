<?php
include('_authCheck.php');
include('getHijriDate.php');

$today = getTodayDateHijri();
$sql = "select id, (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending from thalilist WHERE Thali = '" . $_POST['Thaliid'] . "'";
$result = mysqli_query($link, $sql) or die(mysqli_error($link));
$name = mysqli_fetch_assoc($result);

 mysqli_query($link,"INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`) VALUES ('" . $_POST['Thaliid'] . "','".$name['id']."', 'Stop Permanent','" . $today . "')") or die(mysqli_error($link));
 mysqli_query($link,"UPDATE thalilist set Active='2', `old_thali` = `Thali`, `Thali` = NULL WHERE id = '".$name['id']."'") or die(mysqli_error($link));
 if ($_POST['clear'] == "true") {
	mysqli_query($link,"UPDATE thalilist set yearly_hub=yearly_hub - '".$name['Total_Pending']."' WHERE id = '".$name['id']."'") or die(mysqli_error($link));
 }
 mysqli_query($link,"update change_table set processed = 1 where userid = '" . $name['id'] . "' and `Operation` in ('New Thali') and processed = 0") or die(mysqli_error($link));
?> 
<?php
include('connection.php');
include('_authCheck.php');
include('getHijriDate.php');

$today = getTodayDateHijri();

$sql = "select id from thalilist WHERE thali = '" . $_POST['thaali_id'] . "'";
$result = mysqli_query($link, $sql) or die(mysqli_error($link));
$name = mysqli_fetch_assoc($result);

if (isset($_POST['hardstop']) && $_POST['hardstop'] == 1) {
	mysqli_query($link, "UPDATE thalilist set Active='" . $_POST['active'] . "',hardstop='" . $_POST['hardstop'] . "',hardstop_comment='" . $_POST['hardstopcomment'] . "' WHERE id = " . $name['id']) or die(mysqli_error($link));
} else {
	mysqli_query($link, "UPDATE thalilist set Active='" . $_POST['active'] . "' WHERE id = " . $name['id']) or die(mysqli_error($link));
}

mysqli_query($link, "update change_table set processed = 1 where userid = '" . $name['id'] . "' and `Operation` in ('Start Thali','Stop Thali','Start Transport','Stop Transport') and processed = 0") or die(mysqli_error($link));

if ($_POST['active'] == 0) {
	mysqli_query($link, "UPDATE thalilist set Thali_stop_date='" . $today . "' WHERE id = " . $name['id']) or die(mysqli_error($link));
	mysqli_query($link, "INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`) VALUES ('" . $_POST['thaali_id'] . "','" . $name['id'] . "', 'Stop Thali','" . $today . "')") or die(mysqli_error($link));
} else {
	mysqli_query($link, "UPDATE thalilist set Thali_start_date='" . $today . "', hardstop = 0, hardstop_comment = '' WHERE id= " . $name['id']) or die(mysqli_error($link));
	mysqli_query($link, "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES ('" . $_POST['thaali_id'] . "','" . $name['id'] . "', 'Start Thali','" . $today . "')") or die(mysqli_error($link));
}
echo "success";

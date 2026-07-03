<?php
include('connection.php');
include('_authCheck.php');
include('getHijriDate.php');


$thali = $_POST['id'];
$clearhub = true;

if (isset($_POST['action']) && $_POST['action'] == 'stop_permanant') {

		$today = getTodayDateHijri();
		$sql = "select id, (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending from thalilist WHERE id = '" . $thali . "'";
		$result = mysqli_query($link, $sql) or die(mysqli_error($link));
		$name = mysqli_fetch_assoc($result);

		mysqli_query($link, "INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`) VALUES ('" . $thali . "','" . $name['id'] . "', 'Stop Permanent','" . $today . "')") or die(mysqli_error($link));
		mysqli_query($link, "UPDATE thalilist set Active = '0', hardstop = '1 ' WHERE id = '" . $name['id'] . "'") or die(mysqli_error($link));
		if ($clearhub == "true") {
			mysqli_query($link, "UPDATE thalilist set yearly_hub=yearly_hub - '" . $name['Total_Pending'] . "' WHERE id = '" . $name['id'] . "'") or die(mysqli_error($link));
		}
		mysqli_query($link, "update change_table set processed = 1 where userid = '" . $name['id'] . "' and `Operation` in ('New Thali') and processed = 0") or die(mysqli_error($link));

		header("Location: /fmb/users/thalisearch.php?thalino=" . $_POST['thalino'] . "&tiffinno=" . $_POST['tiffinno'] . "&general=" . $_POST['general'] . "&year=" . $_POST['year'] . "&action=spermanant");
		exit;
}

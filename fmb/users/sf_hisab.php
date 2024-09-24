<?php
include('connection.php');
error_reporting(0);
if($_POST)
{
	$date = $_POST['date1'];
	$_POST= array_filter(array_map('array_filter', $_POST));
	
	foreach($_POST as $key=>$value )
    {
    	mysqli_query($link,"INSERT INTO sf_hisab (`date`, `items`,`quantity`,`amount`,`type`) VALUES ('$date','" . $value[0] . "','" . $value[1] . "','" . $value[2] . "','Dr')") or die(mysqli_error($link));
    }

header("Location: /users/_daily_hisab_entry.php");
}
?>
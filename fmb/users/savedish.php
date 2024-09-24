<?php
include('connection.php');

if($_POST)
{
mysqli_query($link,"INSERT INTO daily_hisab (`date`,`dish_with_roti`,`dish_with_rice`,`thalicount`) VALUES ('" . $_POST['date1'] . "', '" . $_POST['dishroti'] . "','" . $_POST['dishrice'] . "','" . $_POST['thalicount'] . "')") or die(mysqli_error($link));

 header("Location: /users/_daily_hisab_entry.php");

}
?>
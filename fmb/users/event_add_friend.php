<?php
include('connection.php');
$sql= "Select * from thalilist where ITS_No='".$_POST['its']."' AND Active in ('0','1')";
$result = mysqli_query($link,$sql);

if (mysqli_num_rows($result) == 0) {
	mysqli_query($link, "INSERT INTO event_response (`reference_id`,`thaliid`,`eventid`,`response`,`its`,`name`,`mobile`) VALUES ('".$_POST['reference_id']."','0','".$_POST['eventid']."','yes','".$_POST['its']."','".$_POST['name']."','".$_POST['mobile']."')") or die(mysqli_error($link));
	echo "<SCRIPT LANGUAGE='JavaScript'>
    window.alert('Registered Successfully')
    window.location.href='events.php';
    </SCRIPT>";
} else {
	echo "<SCRIPT LANGUAGE='JavaScript'>
    window.alert('The friend you are trying to add is already taking thali and so needs to login to his account and register his confirmation.')
    window.location.href='events.php';
    </SCRIPT>";
}


?>
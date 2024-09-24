<?php
function isResponseReceived($eventid)
{
	include('connection.php');
	$sql = "select * from event_response where eventid='".$eventid."' and thaliid = '".$_SESSION['thaliid']."'";
	$result= mysqli_query($link,$sql);
	if (mysqli_num_rows($result) > 0)
		return true;
	else
		return false;
}
function getResponse($eventid)
{
	include('connection.php');
	$sql = "select * from event_response where eventid='".$eventid."' and thaliid = '".$_SESSION['thaliid']."'";
	$result= mysqli_query($link,$sql);
	if (mysqli_num_rows($result) > 0)
		return mysqli_fetch_assoc($result);
}
?>
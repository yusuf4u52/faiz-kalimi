<?php
include('../users/connection.php');
session_start();

if(isset($_GET['option']))
{
	$huboptions = array(
						1 => '21000',
						2 => '24000',
						3 => '27000',
						4 => '53000'
							);
	$hub_amount = $huboptions[$_GET['option']];
	$update = mysqli_query($link,"UPDATE thalilist set yearly_hub='".$hub_amount."' WHERE Email_id = '".$_SESSION['mail']."'");
}

echo ("<SCRIPT LANGUAGE='JavaScript'>
    window.alert('Data Submitted successfully. Please visit FAIZ to activate your thali.')
    window.location.href='index.php';
    </SCRIPT>");
?>
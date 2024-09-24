<?php
include('connection.php');
include('_authCheck.php');

if(isset($_GET['option']))
{
	$huboptions = array(
						1 => '24000',
						2 => '25000',
						3 => '26000',
						4 => '27000',
						5 => '53000'
							);
	$hub_amount = $huboptions[$_GET['option']];
} else if(isset($_POST['other_takhmeen'])) {
	$hub_amount = (int)$_POST['other_takhmeen'];
	if($hub_amount < 24000) {
		header("Location: selectyearlyhub.php?message=error"); exit;	
	}	
}

if(isset($hub_amount)){
	$thali_details = mysqli_fetch_assoc(mysqli_query($link,"SELECT musaid FROM thalilist WHERE Email_ID = '".$_SESSION['email']."'"));
	$musaid_name = !empty($thali_details['musaid']) ? $thali_details['musaid'] : 'mustafamnr@gmail.com';
	$update = mysqli_query($link,"UPDATE thalilist set yearly_hub='$hub_amount', musaid='$musaid_name' WHERE Email_id = '".$_SESSION['email']."'") or die(mysqli_error($link));
}

header("Location: index.php");

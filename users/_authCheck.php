<?php
include('connection.php');

// check if user didn't hit this page directly and is coming from login page
session_start();
if (!isset($_SESSION['fromLogin'])) {
 	header("Location: login.php");
 	exit;
}

// check if user has right to access the page
$rights = array(
	"musaid" => array(
		"musaid.php",
		"_stop_thali_admin.php"
	),
	"admin" => array(
		"musaid.php",
		"admin_scripts.php",
		"stop_permanant.php",
		"thalisearch.php",
		"pendingactions.php",
		"_stop_thali_admin.php"
	),
	"all" => array(
		"index.php",
		"hoobHistory.php",
		"events.php",
		"update_details.php",
		"selectyearlyhub.php",
		"selectyearlyhub_action.php")
);	
// fetch user role
$sql = mysqli_query($link,"SELECT role from users where email='".$_SESSION['email']."'");

$requet_path = basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);

if ($row = mysqli_fetch_assoc($sql)) {
	$_SESSION['role'] = $row['role'];
	if($row['role'] !== 'superadmin'){
		if (!in_array($requet_path, $rights[$row['role']]) && !in_array($requet_path, $rights['all'])) {
			header("Location: index.php");
		}
	}
} else if(!in_array($requet_path, $rights['all'])){
	echo "You are not an authorized user.";
	header("Location: index.php");
}
?>
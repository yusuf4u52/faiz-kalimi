<?php
require_once('connection.php');

// check if user didn't hit this page directly and is coming from login page
if (!isset($_SESSION)) {
	session_start();
}

if (!isset($_SESSION['fromLogin'])) {
	header("Location: /fmb/index.php");
	exit;
}

// check if user has right to access the page
$rights = array(
	"musaid" => array(
		"/fmb/users/musaid.php",
		"/fmb/users/_stop_thali_admin.php"
	),
	"admin" => array(
		"/fmb/users/musaid.php",
		"/fmb/users/admin_scripts.php",
		"/fmb/users/stop_permanant.php",
		"/fmb/users/thalisearch.php",
		"/fmb/users/userstart.php",
		"/fmb/users/userstop.php",
		"/fmb/users/foodlist.php",
		"/fmb/users/savefood.php",
		"/fmb/users/menulist.php",
		"/fmb/users/savemenu.php",
		"/fmb/users/usermenu.php",
		"/fmb/users/userfeedmenu.php",
		"/fmb/users/rotimaker.php",
		"/fmb/users/savermaker.php",
		"/fmb/users/rotidistribute.php",
		"/fmb/users/saverdistribute.php",
		"/fmb/users/rotirecieved.php",
		"/fmb/users/saverrecieved.php",
		"/fmb/users/rotireport.php",
		"/fmb/users/rotiimport.php",
		"/fmb/users/transporter.php",
		"/fmb/users/savertransporters.php",
		"/fmb/users/transporterthalicount.php",
		"/fmb/users/savethalicount.php",
		"/fmb/users/transporterpayment.php",
		"/fmb/users/pendingactions.php",
		"/fmb/users/_stop_thali_admin.php",
		"/fmb/sms/index.php"
	),
	"staff" => array(
		"/fmb/users/thalisearch.php",
		"/fmb/users/_payhoob.php",
		"/fmb/users/rotipayment.php",
	),
	"all" => array(
		"/fmb/users/index.php",
		"/fmb/users/stopthali.php",
		"/fmb/users/stop_dates.php",
		"/fmb/users/viewmenu.php",
		"/fmb/users/changemenu.php",
		"/fmb/users/hoobHistory.php",
		"/fmb/users/events.php",
		"/fmb/users/hoob_details.php",
		"/fmb/users/thali_details.php",
		"/fmb/users/update_details.php",
		"/fmb/users/selectyearlyhub.php",
		"/fmb/users/selectyearlyhub_action.php"
	)
);

// fetch user role
$sql = mysqli_query($link, "SELECT role from users where email='" . $_SESSION['email'] . "'");

$requet_path = strtok($_SERVER["REQUEST_URI"], '?');
if ($row = mysqli_fetch_assoc($sql)) {
	$_SESSION['role'] = $row['role'];
	if ($row['role'] !== 'superadmin') {
		if (!in_array($requet_path, $rights[$row['role']]) && !in_array($requet_path, $rights['all'])) {
			header("Location: /fmb/users/index.php");
			//exit;
		}
	}
} else if (!in_array($requet_path, $rights['all'])) {
	echo "You are not an authorized user.";
	header("Location: /fmb/users/index.php");
	//exit;
}
?>
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
		"/fmb/users/special/friday.php",
		"/fmb/users/special/barnamaj.php",
		"/fmb/users/special/stopall.php",
		"/fmb/users/menu/food.php",
		"/fmb/users/menu/savefood.php",
		"/fmb/users/menu/list.php",
		"/fmb/users/menu/savelist.php",
		"/fmb/users/menu/edited.php",
		"/fmb/users/menu/feedback.php",
		"/fmb/users/roti/maker.php",
		"/fmb/users/roti/savemaker.php",
		"/fmb/users/roti/distribute.php",
		"/fmb/users/roti/savedistribute.php",
		"/fmb/users/roti/recieved.php",
		"/fmb/users/roti/saverecieved.php",
		"/fmb/users/roti/report.php",
		"/fmb/users/roti/import.php",
		"/fmb/users/transporter/list.php",
		"/fmb/users/transporter/savelist.php",
		"/fmb/users/transporter/activethali.php",
		"/fmb/users/transporter/inactivethali.php",
		"/fmb/users/transporter/thalicount.php",
		"/fmb/users/transporter/report.php",
		"/fmb/users/pendingactions.php",
		"/fmb/users/_stop_thali_admin.php",
		"/fmb/users/uploadreciept.php",
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
		"/fmb/users/hub_details.php",
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

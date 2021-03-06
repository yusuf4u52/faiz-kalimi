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
			"/fmb/users/musaid.php",
			"/fmb/users/_stop_thali_admin.php"
		),
		"admin" => array(
			"/fmb/users/musaid.php",
			"/fmb/users/admin_scripts.php",
			"/fmb/users/stop_permanant.php",
			"/fmb/users/thalisearch.php",
			"/fmb/users/pendingactions.php",
			"/fmb/users/_stop_thali_admin.php",
			"/fmb/sms/index.php"
		),
		"all" => array(
			"/fmb/users/index.php",
			"/fmb/users/hoobHistory.php",
			"/fmb/users/events.php",
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
			}
		}
	} else if (!in_array($requet_path, $rights['all'])) {
		echo "You are not an authorized user.";
		header("Location: /fmb/users/index.php");
	}
	?>
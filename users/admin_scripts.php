<?php
include('_authCheck.php');
include('_bottomJS.php');
include('../registration/call_api.php');

if (isset($_GET['update'])) {
    CallAPIForAll();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Scripts for Admin</title>
	<?php include('_head.php'); ?>
</head>
<body>
	<?php include('_nav.php'); ?>
	<a href="integrity_check.php" class="btn btn-info" role="button">Receipts Integrity</a>
	<a href="../monthchange/month_change.php" class="btn btn-info" role="button">Year Change</a>
	<a href="admin_scripts.php?update=true" class="btn btn-info" role="button">Update From ITS</a>
</body>
</html>
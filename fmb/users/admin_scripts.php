<?php
include('../registration/call_api.php');
include('header.php');
include('navbar.php');

if (isset($_GET['update'])) {
    CallAPIForAll();
}
?>

<div class="card">
    <div class="card-body text-center">
		<a href="integrity_check.php" class="btn btn-light m-1" role="button">Receipts Integrity</a>
		<a href="../monthchange/month_change.php" class="btn btn-light m-1" role="button">Year Change</a>
		<a href="admin_scripts.php?update=true" class="btn btn-light m-1" role="button">Update From ITS</a>
	</div>
</div>
		
<?php include('footer.php'); ?>
<?php
include('../registration/call_api.php');
include('header.php');
include('navbar.php');
require_once('helpers.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    CallAPIForAll();
}
?>

<div class="card">
    <div class="card-body text-center">
		<a href="integrity_check.php" class="btn btn-light m-1" role="button">Receipts Integrity</a>
		<a href="../monthchange/month_change.php" class="btn btn-light m-1" role="button">Year Change</a>
		<form method="post" style="display:inline;">
			<button type="submit" name="update" value="true" class="btn btn-light m-1">Update From ITS</button>
		</form>
	</div>
</div>
		
<?php include('footer.php'); ?>

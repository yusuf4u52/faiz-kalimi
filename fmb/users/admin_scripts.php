<?php
include('../registration/call_api.php');
include('header.php');
include('navbar.php');

if (isset($_GET['update'])) {
    CallAPIForAll();
}
?>
<div class="fmb-content mt-5 text-center">
	<div class="container">
        <div class="row">
            <div class="col-12">
				<a href="integrity_check.php" class="btn btn-light m-1" role="button">Receipts Integrity</a>
				<a href="../monthchange/month_change.php" class="btn btn-light m-1" role="button">Year Change</a>
				<a href="admin_scripts.php?update=true" class="btn btn-light m-1" role="button">Update From ITS</a>
			</div>
		</div>
	</div>
</div>

<?php include('footer.php'); ?>
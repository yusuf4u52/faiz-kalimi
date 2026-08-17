<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');

$miqaatslist = [];
if (!empty($values['yearly_hub'])) {
    $sql = db_query($link, "SELECT miqat_date, miqat_description FROM sms_date");
    $miqaatslist = mysqli_fetch_all($sql, MYSQLI_ASSOC);
}
?>

<div class="card">
	<div class="card-body">
		<h2 class="mb-3">Hub Details</h2>
		<div class="Payment-details text-center mb-3">
			<img class="img-fluid mx-auto d-block mb-3" src="/testfmb/assets/img/fmb-account.avif" alt="Faiz ul Mawaidil Burhaniyah (Kalimi Mohalla) Acoount" width="900" height="548" />
			<h5 class="mb-3 text-center">Your total outstanding amount is</h5>
			<h1 class="mb-3 text-center" style="color:#198754;"><i class="bi bi-currency-rupee"></i><?php echo e((string) ($values['Total_Pending'] ?? 0)); ?></h1>
			<a class="btn btn-light btn-lg mb-3" href="upi://pay?pa=dbjt-fmb-kalimi@ybl&pn=D B J T TRUST K M POONA - FMB&cu=INR" id="__UPI_BUTTON__">Pay</a>
		</div>
		<h6 class="mb-3">The niyaaz amount will be payable throughout the year on the following miqaats. If possible
			do contribute the whole amount in Lailatul Qadr</h6>
		<ol>
			<?php foreach ($miqaatslist as $miqaat) { ?>
				<li><?php echo e($miqaat['miqat_description']); ?> on <strong><?php echo e(date('d F Y', strtotime($miqaat['miqat_date']))); ?></strong></li>
			<?php } ?>
		</ol>
	</div>
</div>

<?php include('footer.php'); ?>

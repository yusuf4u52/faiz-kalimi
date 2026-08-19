<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');

$transporters = db_query($link, "SELECT * FROM transporters WHERE `Name` = ?", "s", [$values['Transporter'] ?? '']);
$transvalues = $transporters->fetch_assoc();
?>

<div class="card">
	<div class="card-body">
		<h2 class="mb-3">Thali Details</h2>
		<ul class="list-group list-group-flush">
			<li class="list-group-item">
				<div class="fw-bold">Sabeel Number</div>
				<?php echo e($values['Thali']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Tiffin Number</div>
				<?php echo e($values['tiffinno']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Thali Type</div>
				<?php echo e($values['thalisize']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">HOF ITS No</div>
				<?php echo e($values['ITS_No']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Name</div>
				<?php echo e($values['NAME']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Mobile Number</div>
				<?php echo e($values['CONTACT']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Email Address</div>
				<a href="mailto:<?php echo e($values['Email_ID']); ?>"><?php echo e($values['Email_ID']); ?></a> <?php if (!empty($values['SEmail_ID'])) : ?>| <a
					href="mailto:<?php echo e($values['SEmail_ID']); ?>"><?php echo e($values['SEmail_ID']); ?></a> <?php endif; ?>
			</li>
			<?php if (!empty($musaid_details)) { ?>
				<li class="list-group-item">
					<div class="fw-bold">Masool</div>
					<?php echo e($musaid_details['username']); ?> | <a
						href="https://wa.me/+91<?php echo e($musaid_details['mobile']); ?>"><?php echo e($musaid_details['mobile']); ?></a>
				</li>
			<?php } ?>
			<li class="list-group-item">
				<div class="fw-bold">Previous Year Takhmeen</div>
				<?php echo '₹ ' . e((string) $values['previous_hub']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Previous Due</div>
				<?php echo '₹ ' . e((string) $values['Previous_Due']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Current Year Takhmeen</div>
				<?php echo '₹ ' . e((string) $values['yearly_hub']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Zabihat Niyat</div>
				<?php echo e($values['Zabihat']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Hub Pending</div>
				₹<?php echo e((string) ($values['Total_Pending'] + $values['Paid'])); ?> -
				₹<?php echo e((string) $values['Paid']); ?> = ₹<?php echo e((string) $values['Total_Pending']); ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Is Active?</div>
				<p class="list-group-item-text">
					<?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Transporter</div>
				<p class="list-group-item-text">
					<?php echo e($values['Transporter']); ?> <?php if (!empty($transvalues['Mobile'])) : ?>| <a
						href="https://wa.me/+91<?php echo e($transvalues['Mobile']); ?>"><?php echo e($transvalues['Mobile']); ?></a> <?php endif; ?>
				</p>
			</li>
			<li class="list-group-item">
				<div class="fw-bold">Full Address</div>
				<p class="list-group-item-text"><?php echo e($values['wingflat']); ?>, <?php echo e($values['society']); ?>, <?php echo e($values['Full_Address']); ?></p>
			</li>

			<?php
			if ($values['Active'] == 1) {
			?>
				<li class="list-group-item">
					<div class="fw-bold">Start Date</div>
					<p class="list-group-item-text hijridate"><?php echo e($values['Thali_Start_Date']); ?></p>
				</li>

			<?php
			} else {
			?>
				<li class="list-group-item">
					<div class="fw-bold">Stop Date</div>
					<p class="list-group-item-text hijridate"><?php echo e($values['Thali_Stop_Date']); ?></p>
				</li>
			<?php } ?>
		</ul>
	</div>
</div>

<?php include('footer.php'); ?>

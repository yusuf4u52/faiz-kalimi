<?php
include('header.php');
include('navbar.php');
?>

<div class="card">
	<div class="card-body">
		<h2 class="mb-3">Inactive Thali</h2>
		<?php
		try {
			$stop_thali = db_query(
				$link,
				"SELECT * FROM `thalilist` WHERE `Active` = 0 AND `hardstop` != 1 AND `Transporter` COLLATE utf8mb4_unicode_ci LIKE CONCAT('%', ?, '%') ORDER BY `thalisize`, `tiffinno` ASC",
				"s",
				[$_SESSION['transporter']]
			);
		} catch (RuntimeException $e) {
			error_log('[stop_thali.php] ' . $e->getMessage());
			$stop_thali = null;
		}
		$i = 0;
		if ($stop_thali === null) { ?>
			<h4 class="text-center mt-5 text-danger">Sorry, something went wrong loading this list. Please try again in a moment.</h4>
		<?php } elseif ($stop_thali->num_rows > 0) { ?>
			<div class="table-responsive">
				<table id="transporterlist" class="table table-striped table-hover">
					<thead>
						<tr>
							<th>Sr. No</th>
							<th>Tiffin No</th>
							<th>Tiffin Size</th>
							<th>Flat/House</th>
							<th>Society</th>
							<th>Contact</th>
							<th>Whatsapp</th>
							<th>Name</th>
						</tr>
					</thead>
					<tbody>
						<?php while ($stop_list = mysqli_fetch_assoc($stop_thali)) { ?>
							<tr>
								<td><?php echo ++$i; ?></td>
								<td><?php echo e((string) $stop_list['tiffinno']); ?></td>
								<td><?php echo e((string) $stop_list['thalisize']); ?></td>
								<td><?php echo e((string) $stop_list['wingflat']); ?></td>
								<td><?php echo e((string) $stop_list['society']); ?></td>
								<td><a href="tel:<?php echo e((string) $stop_list['CONTACT']); ?>"><?php echo e((string) $stop_list['CONTACT']); ?></a></td>
								<td><a href="https://wa.me/91<?php echo e((string) $stop_list['WhatsApp']); ?>" target="_blank"><?php echo e((string) $stop_list['WhatsApp']); ?></a></td>
								<td class="text-capitalize"><?php echo e(strtolower((string) $stop_list['NAME'])); ?></td>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<th>Sr. No</th>
							<th>Tiffin No</th>
							<th>Tiffin Size</th>
							<th>Flat/House</th>
							<th>Society</th>
							<th>Contact</th>
							<th>Whatsapp</th>
							<th>Name</th>
						</tr>
					</tfoot>
				</table>
			</div>
		<?php } else {
			echo '<h4 class="text-center mt-5">No thali is stopped.</h4>';
		} ?>
	</div>
</div>

<?php include('footer.php'); ?>
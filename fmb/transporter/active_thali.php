<?php
include('header.php');
include('navbar.php');
?>

<div class="card">
	<div class="card-body">
		<h2 class="mb-3">Active Thali</h2>
		<?php
		try {
			$start_thali = db_query(
				$link,
				"SELECT * FROM `thalilist` WHERE `Active` = 1 AND `hardstop` != 1 AND `Transporter` LIKE CONCAT('%', ?, '%') ORDER BY `thalisize`, `tiffinno` ASC",
				"s",
				[$_SESSION['transporter']]
			);
		} catch (RuntimeException $e) {
			error_log(
				'[active_thali.php] database=' . (getenv('FMB_DB_NAME') ?: 'u378605509_kalimi')
				. ' errno=' . mysqli_errno($link)
				. ' error=' . $e->getMessage()
			);
			$start_thali = null;
		}
		$i = 0;
		if ($start_thali === null) { ?>
			<h4 class="text-center mt-5 text-danger">Sorry, something went wrong loading this list. Please try again in a moment.</h4>
		<?php } elseif ($start_thali->num_rows > 0) { ?>
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
						<?php while ($start_list = mysqli_fetch_assoc($start_thali)) { ?>
							<tr>
								<td><?php echo ++$i; ?></td>
								<td><?php echo e((string) $start_list['tiffinno']); ?></td>
								<td><?php echo e((string) $start_list['thalisize']); ?></td>
								<td><?php echo e((string) $start_list['wingflat']); ?></td>
								<td><?php echo e((string) $start_list['society']); ?></td>
								<td><a href="tel:<?php echo e((string) $start_list['CONTACT']); ?>"><?php echo e((string) $start_list['CONTACT']); ?></a></td>
								<td><a href="https://wa.me/91<?php echo e((string) $start_list['WhatsApp']); ?>" target="_blank"><?php echo e((string) $start_list['WhatsApp']); ?></a></td>
								<td class="text-capitalize"><?php echo e(strtolower((string) $start_list['NAME'])); ?></td>
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
			echo '<h4 class="text-center mt-5">No thali is started.</h4>';
		} ?>
	</div>
</div>

<?php include('footer.php'); ?>
<?php
include('header.php');
include('navbar.php');
?>

<div class="card">
	<div class="card-body">
		<h2 class="mb-3">Stop Thali</h2>
		<?php $stop_thali = mysqli_query($link, "SELECT * FROM thalilist WHERE Active = 0 AND hardstop != 1 AND Transporter LIke '%" . $_SESSION['transporter'] . "%' ORDER BY Transporter ASC");
		$i=0;if ($stop_thali->num_rows > 0) { ?>
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
								<td><?php echo $stop_list['tiffinno']; ?></td>
								<td><?php echo $stop_list['thalisize']; ?></td>
								<td><?php echo $stop_list['wingflat']; ?></td>
								<td><?php echo $stop_list['society']; ?></td>
								<td><a href="tel:<?php echo $stop_list['CONTACT']; ?>"><?php echo $stop_list['CONTACT']; ?></a></td>
								<td><a href="https://wa.me/91<?php echo $stop_list['WhatsApp']; ?>" target="_blank"><?php echo $stop_list['WhatsApp']; ?></a></td>
								<td class="text-capitalize"><?php echo strtolower($stop_list['NAME']); ?></td>
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
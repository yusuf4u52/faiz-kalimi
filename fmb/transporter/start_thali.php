<?php
include('header.php');
include('navbar.php');
?>

<div class="card">
    <div class="card-body">
		<h2 class="mb-3">Start Thali</h2>
		<?php $start_thali = mysqli_query($link, "SELECT * FROM thalilist WHERE Active = 1 AND Transporter LIke '%".$_SESSION['transporter']."%' ORDER BY tiffinno ASC");
		if($start_thali->num_rows > 0) { ?>
			<div class="table-responsive">
				<table id="thali" class="table table-striped table-hover">
					<thead>
						<tr>
							<th>Tiffin No</th>
							<th>Tiffin Size</th>
							<th>Name</th>
							<th>Contact</th>
							<th>Address</th>
						</tr>
					</thead>
					<tbody>
						<?php while ($start_list = mysqli_fetch_assoc($start_thali)) { ?>
							<tr>
								<td><?php echo $start_list['tiffinno']; ?></td>
								<td><?php echo $start_list['thalisize']; ?></td>
								<td class="text-capitalize"><?php echo strtolower($start_list['NAME']); ?></td>
								<td><?php echo $start_list['CONTACT']; ?></td>
								<td><?php echo $start_list['wingflat'] . ' ' . $start_list['society']; ?></td>
							</tr>
						<?php } ?>
					</tbody>
					<tfoot>
						<tr>
							<th>Tiffin No</th>
							<th>Tiffin Size</th>
							<th>Name</th>
							<th>Contact</th>
							<th>Address</th>
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
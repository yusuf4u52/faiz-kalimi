<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');

$result = db_query($link, "SELECT * FROM thalilist WHERE Transporter IS NULL AND Active = 1");
$result_new_thali = db_query($link, "SELECT * FROM thalilist WHERE Thali IS NULL AND Active IS NULL");

$transporter_list = [];
$result1 = db_query($link, "SELECT DISTINCT(Transporter) AS Name FROM thalilist WHERE Transporter IS NOT NULL");
while ($values1 = mysqli_fetch_assoc($result1)) {
    $transporter_list[] = $values1['Name'];
}

$thalisize_list = [];
$thalisize_result = db_query($link, "SELECT DISTINCT(thalisize) AS size FROM thalilist WHERE thalisize IS NOT NULL");
while ($thalisize_values = mysqli_fetch_assoc($thalisize_result)) {
    $thalisize_list[] = $thalisize_values['size'];
}

$sector_list = [];
$sector_result = db_query($link, "SELECT DISTINCT(sector) FROM `thalilist` WHERE sector IS NOT NULL ORDER BY sector");
while ($sector_value = mysqli_fetch_assoc($sector_result)) {
    $sector_list[] = $sector_value['sector'];
}

$subsector_list = [];
$subsector_result = db_query($link, "SELECT DISTINCT(subsector) FROM `thalilist` WHERE subsector IS NOT NULL ORDER BY subsector");
while ($subsector_value = mysqli_fetch_assoc($subsector_result)) {
    $subsector_list[] = $subsector_value['subsector'];
}
?>

<div class="card">
	<div class="card-body">
		<div class="transporter">
			<h2 class="mb-3">Transporter request</h2>
			<div class="table-responsive">
				<table class="table table-striped display" width="100%">
					<thead>
						<tr>
							<th>Sabeel No</th>
							<th>Thali No</th>
							<th>Thali Size</th>
							<th>Transporter</th>
							<th>Society</th>
							<th>Name</th>
							<th>Active</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						while ($values = mysqli_fetch_assoc($result)) {
						?>

							<tr>
								<form action='savetransporter.php' method='post'>
									<td>
										<?php echo e($values['Thali']); ?>
										<input type="hidden" name="Thali" value="<?php echo e($values['Thali']); ?>">
									</td>
									<td>
										<input type="text" name="tiffinno" value="<?php echo e($values['tiffinno']); ?>" required>
									</td>
									<td>
										<select class="form-select form-select-sm" name="thalisize" required>
											<option value=''>Select Thalisize</option>
											<?php foreach ($thalisize_list as $tsize) { ?>
												<option value='<?php echo e($tsize); ?>' <?php echo ($tsize == $values['thalisize']) ? 'selected' : ''; ?>>
													<?php echo e($tsize); ?>
												</option>
											<?php } ?>
										</select>
									</td>
									<td>
										<?php if ($values['yearly_hub'] != "0") { ?>
											<select class='transporter form-select form-select-sm' name='transporter' required>
												<option value=''>Select Transporter</option>
												<?php foreach ($transporter_list as $tname) { ?>
													<option value='<?php echo e($tname); ?>' <?php echo ($tname == $values['Transporter']) ? 'selected' : ''; ?>>
														<?php echo e($tname); ?>
													</option>
												<?php } ?>
											</select>
										<?php } ?>
									</td>
									<td><?php echo e($values['society']); ?></td>
									<td><?php echo e($values['NAME']); ?></td>
									<td><?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></td>
									<td><button type="submit" class="btn btn-light btn-sm">Submit</button>
									</td>
								</form>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="new-thali mt-5">
			<h2 class="mb-3">New Thali</h2>
			<?php
			$sql = db_query($link, "
SELECT (t1.Thali +1) AS gap_starts_at, (SELECT MIN( t3.Thali )-1 FROM thalilist t3 WHERE t3.Thali > t1.Thali) AS gap_ends_at FROM thalilist t1 WHERE NOT  EXISTS ( SELECT t2.Thali FROM thalilist t2 WHERE t2.Thali = t1.Thali +1 ) HAVING gap_ends_at IS NOT NULL  LIMIT 0 , 30");
			$row = mysqli_fetch_row($sql);
			$plusone = $row[0] ?? null;
			echo "Thali No. :: " . e((string) $plusone) . "  can be given";
			?>
			<div class="table-responsive">
				<table class="table table-striped display" width="100%">
					<thead>
						<tr>
							<th>Sabeel No</th>
							<th>Thali No</th>
							<th>Thali Size</th>
							<th>Hub</th>
							<th>Sector</th>
							<th>Transporter</th>
							<th>Full Address</th>
							<th>Name</th>
							<th>Mobile</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody>
						<?php
						while ($values = mysqli_fetch_assoc($result_new_thali)) {
						?>
							<tr>
								<form action='activatethali.php' method='post'>
									<input type='hidden' value='<?php echo e($values['id']); ?>' name='id'>
									<input type='hidden' value='<?php echo e($values['NAME']); ?>' name='name'>
									<?php /* BUG FIX: activatethali.php and reject.php both read
									       $_POST['email'] to send a welcome/rejection email, but this
									       form never actually submitted an email field, so that email
									       could never have been sent. */ ?>
									<input type='hidden' value='<?php echo e($values['Email_ID'] ?? ''); ?>' name='email'>
									<td>
										<input class="form-control form-control-sm" type='text' name='sabeelno' required='required'>
									</td>
									<td>
										<input class="form-control form-control-sm" type='text' name="thalino" required='required'>
									</td>
									<td>
										<select class="form-select form-select-sm" name="thalisize" required>
											<option value="">Select Thali Size</option>
											<option value="Mini">Mini</option>
											<option value="Small">Small</option>
											<option value="Medium">Medium</option>
											<option value="Large">Large</option>
										</select>
									</td>
									<td><input class="form-control form-control-sm" type='number' name="hub" required='required' value="<?php echo e((string) $values['yearly_hub']); ?>"></td>
									<td>
										<select class='sector form-select form-select-sm' name='sector' required>
											<option value=''>Select Sector</option>
											<?php foreach ($sector_list as $sector_name) { ?>
												<option value='<?php echo e($sector_name); ?>' <?php echo ($sector_name == $values['sector']) ? 'selected' : ''; ?>>
													<?php echo e($sector_name); ?>
												</option>
											<?php } ?>
										</select>
									</td>
									<td>
										<select class='transporter form-select form-select-sm' name='transporter' required>
											<option value=''>Select Transporter</option>
											<?php foreach ($transporter_list as $tname) { ?>
												<option value='<?php echo e($tname); ?>' <?php echo ($tname == $values['Transporter']) ? 'selected' : ''; ?>>
													<?php echo e($tname); ?>
												</option>
											<?php } ?>
										</select>
									</td>
									<td><?php echo e($values['wingflat'] . ', ' . $values['society'] . ', ' . $values['Full_Address']); ?></td>
									<td><?php echo e($values['NAME']); ?></td>
									<td><?php echo e($values['CONTACT']); ?></td>
									<td><button type="submit"
											class="btn btn-light btn-sm me-2 mb-2">Activate</button>
										<button class="btn btn-light btn-sm mb-2" type="submit"
											formaction="/fmb/users/reject.php">Reject</button>
									</td>
								</form>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
		</div><!-- /example -->
	</div>
</div>

<?php include('footer.php'); ?>

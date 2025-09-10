<?php
include('header.php');
include('navbar.php');

$query = "SELECT * FROM thalilist";
$query_new_transporter = $query . " WHERE Transporter is null and Active=1";
$result = mysqli_query($link, $query_new_transporter);

$query_new_thali = $query . " WHERE Thali is null and Active is null";
$result_new_thali = mysqli_query($link, $query_new_thali);

$transporter_list = array();
$query = "SELECT Distinct(Transporter) as Name FROM thalilist where Transporter is not NULL";
$result1 = mysqli_query($link, $query);
while ($values1 = mysqli_fetch_assoc($result1)) {
	$transporter_list[] = $values1['Name'];
}

$sector_list = array();
$sector_query = "SELECT DISTINCT(sector) FROM `thalilist` WHERE sector IS NOT NULL order by sector";
$sector_result = mysqli_query($link, $sector_query);
while ($sector_value = mysqli_fetch_assoc($sector_result)) {
	$sector_list[] = $sector_value['sector'];
}

$subsector_list = array();
$subsector_query = "SELECT DISTINCT(subsector) FROM `thalilist` WHERE subsector IS NOT NULL order by subsector";
$subsector_result = mysqli_query($link, $subsector_query);
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
							<th>Thali No</th>
							<th>Transporter</th>
							<th>Sector</th>
							<th>Subsector</th>
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
										<?php echo $values['Thali']; ?>
										<input type="hidden" name="Thali"
											value="<?php echo $values['Thali']; ?>">
									</td>
									<td>
										<?php
										if ($values['yearly_hub'] != "0") {
											?>
											<select class='transporter form-select form-select-sm'
												name='transporter' required>
												<option value=''>Select</option>
												<?php
												foreach ($transporter_list as $tname) {
													?>
													<option value='<?php echo $tname; ?>' <?php echo ($tname == $values['Transporter']) ? 'selected' : ''; ?>>
														<?php echo $tname; ?>
													</option>
													<?php
												}
												?>
											</select>
										<?php } ?>
									</td>
									<td>
										<select class='sector form-select form-select-sm' name='sector'
											required>
											<option value=''>Select</option>
											<?php
											foreach ($sector_list as $sector_name) {
												?>
												<option value='<?php echo $sector_name; ?>' <?php echo ($sector_name == $values['sector']) ? 'selected' : ''; ?>>
													<?php echo $sector_name; ?>
												</option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<select class='subsector form-select form-select-sm'
											name='subsector' required>
											<option value=''>Select</option>
											<?php
											foreach ($subsector_list as $subsector_name) {
												?>
												<option value='<?php echo $subsector_name; ?>' <?php echo ($subsector_name == $values['subsector']) ? 'selected' : ''; ?>><?php echo $subsector_name; ?>
												</option>
												<?php
											}
											?>
										</select>
									</td>
									<td><?php echo $values['society']; ?></td>
									<td><?php echo $values['NAME']; ?></td>
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
			$sql = mysqli_query($link, "
SELECT (t1.Thali +1) AS gap_starts_at, (SELECT MIN( t3.Thali )-1 FROM thalilist t3 WHERE t3.Thali > t1.Thali) AS gap_ends_at FROM thalilist t1 WHERE NOT  EXISTS ( SELECT t2.Thali FROM thalilist t2 WHERE t2.Thali = t1.Thali +1 ) HAVING gap_ends_at IS NOT NULL  LIMIT 0 , 30");
			$row = mysqli_fetch_row($sql);
			$plusone = $row[0];
			echo "Thali No. :: $plusone  can be given";
			?>
			<div class="table-responsive">
				<table class="table table-striped display" width="100%">
					<thead>
						<tr>
							<th>Thali No</th>
							<th>Transporter</th>
							<th>Musaid</th>
							<th>Hub</th>
							<th>Address</th>
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
									<input type='hidden' value='<?php echo $values['Email_ID']; ?>'
										name='email'>
									<input type='hidden' value='<?php echo $values['id']; ?>' name='id'>
									<input type='hidden' value='<?php echo $values['NAME']; ?>' name='name'>
									<input type='hidden' value='<?php echo $values['CONTACT']; ?>'
										name='contact'>
									<input type='hidden' value='<?php echo $values['Full_Address']; ?>'
										name='address'>
									<input type='hidden' value='<?php echo $values['Transporter']; ?>'
										name='trasnporter'>
									<td>
										<input class="form-control form-control-sm" type='text' size=8
											name='thalino' class='' required='required'>
									</td>
									<td>
										<select class="form-select form-select-sm" name="transporter"
											required='required'>
											<option value=''>Select</option>
											<?php
											foreach ($transporter_list as $tname) {
												?>
												<option value='<?php echo $tname; ?>'><?php echo $tname; ?>
												</option>
												<?php
											}
											?>
										</select>
									</td>
									<td>
										<select class="form-select form-select-sm" name="musaid"
											required='required'>
											<option value=''>Select</option>
											<?php
											$musaid_list = mysqli_query($link, "SELECT username, email FROM users");
											while ($musaid = mysqli_fetch_assoc($musaid_list)) {
												?>
												<option value='<?php echo $musaid['email']; ?>'>
													<?php echo $musaid['username']; ?>
												</option>
												<?php
											}
											?>
										</select>
									</td>
									<td><input class="form-control form-control-sm" type='text' name="hub"
											size=8 required='required'
											value="<?php echo $values['yearly_hub']; ?>"></td>
									</td>
									<td><?php echo $values['Full_Address']; ?></td>
									<td><?php echo $values['NAME']; ?></td>
									<td><?php echo $values['CONTACT']; ?></td>
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
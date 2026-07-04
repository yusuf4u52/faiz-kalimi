<?php
include('header.php');
include('navbar.php');

$current_year = mysqli_fetch_assoc(mysqli_query($link, "SELECT value FROM settings where `key`='current_year'"));
$previous_year = ((int) $current_year['value']) - 1;

$previous_thalilist = "thalilist_" . $previous_year;
$previous_receipts = "receipts_" . $previous_year;

$max_days = mysqli_fetch_row(mysqli_query($link, "SELECT MAX(thalicount) as max FROM `thalilist`"));
$val = mysqli_query($link, "SELECT MAX(thalicount) as max FROM `$previous_thalilist`");
if ($val !== FALSE) {
	$max_days_previous = mysqli_fetch_row($val);
} else {
	$max_days_previous = 1;
}


if (isset($_SESSION['role']) && ($_SESSION['role'] === 'superadmin' || $_SESSION['role'] === 'admin')) {
	$musaid_list = mysqli_fetch_all(mysqli_query($link, "SELECT `id`,`email`,`username` FROM `users` WHERE `role` in ('musaid','admin','superadmin')"), MYSQLI_ASSOC);
} else {
	$musaid_list = array(
		array(
			'id' => 0,
			'username' => $_SESSION['email'],
			'email' => $_SESSION['email']
		)
	);
}
?>

<div class="accordion" id="accordionMusaid">
	<?php foreach ($musaid_list as $musaid) {
		$result = mysqli_query($link, "SELECT * FROM thalilist where Previous_Due > 3 AND Transporter IS NOT NULL AND musaid='" . $musaid['email'] . "' AND hardstop !=1 order by `Paid %`");
		$thali_details = mysqli_fetch_all($result, MYSQLI_ASSOC);
		$musaid_thali_count = count($thali_details);
		if ($musaid_thali_count > 0) { ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading<?php echo $musaid['id']; ?>">
					<button class="accordion-button <?php if (count($musaid_list) !== 1)
														echo "collapsed"; ?>" type="button" data-bs-toggle="collapse"
						data-bs-target="#collapse<?php echo $musaid['id']; ?>" aria-expanded="true"
						aria-controls="collapse<?php echo $musaid['id']; ?>">
						<?php echo $musaid['username']; ?> - (<?php echo $musaid_thali_count; ?>)
					</button>
				</h2>
				<div id="collapse<?php echo $musaid['id']; ?>"
					class="accordion-collapse collapse <?php if (count($musaid_list) == 1)
															echo "show"; ?>"
					data-bs-parent="#accordionMusaid">
					<div class="accordion-body">
						<div class="table-responsive">
							<table class="table table-striped table-bordered display" style="width:100%">
								<thead>
									<tr>
										<th scope="col">Sabeel No</th>
										<th scope="col">Thali No</th>
										<th scope="col">Action</th>
										<th scope="col">Thali Size</th>
										<th scope="col">Sabeel Type</th>
										<th scope="col">Name</th>
										<th scope="col">Previous Due</th>
										<th scope="col">Previous Hub</th>
										<th scope="col">Current Hub</th>
										<th scope="col">Pending</th>
										<th scope="col">Paid %</th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ($thali_details as $values) { ?>
										<tr>
											<form method="post">
												<input type='hidden' value='<?php echo $values['Thali']; ?>' name='Thali'>
												<td>
													<?php echo $values['Thali']; ?>
													&nbsp;
													<a data-bs-toggle="modal" href="#details-<?php echo $values['Thali']; ?>">
														<img src="/fmb/assets/img/view.avif" style="width:20px;height:20px;">
													</a>
												</td>
												<td><?php echo $values['tiffinno']; ?></td>
												<td>
													<?php
													$msg = "*Salaam " . $values['NAME'] . "*,\n\n";
													$msg .= "*Final Reminder - Previous Year FMB Hoob Pending*\n\n";
													$msg .= "Aapna ghare *Faiz ul Mawaid il Burhaniyah* ni barakat pohchi rahi che. Aapni previous year ni hoob baki che, je ni tafseel niche aapi che.\n\n";
													$msg .= "*Sabil No:* " . $values['Thali'] . "\n";
													$msg .= "*ITS No:* " . $values['ITS_No'] . "\n";
													$msg .= "*Pending Hoob:* ₹" . number_format($values['Previous_Due']) . "\n\n";
													$msg .= "Aap si adab sathe iltemas che ke aap aa pending hoob *Syedi Fakhruddin Shaheed (RA) na Urus Mubarak (11 July)* sudhi ada kari ne FMB khidmat team ne yaari aapiye.\n\n";
													$msg .= "Agar koi sabab 11 July sudhi pending hoob clear nahi thay, to *13 July Monday* thali si home delivery mawqoof rakhva ma aavshe ane aapne thali *Faiz* mathi collect karvi padse.\n\n";
													$msg .= "Agar aap aa hoob pehla thi ada kari chuka ho, to payment ni receipt ya transfer nu screenshot hamne mokli aapsho taake hame apna records update kari shakay.\n\n";
													$msg .= "Was Salaam,\n";
													$msg .= "*FMB Khidmat Team*";

													?>
													<a href="https://wa.me/91<?php echo $values['WhatsApp']; ?>?text=<?php echo urlencode($msg); ?>" target="_blank">
														WhatsApp
													</a>
												</td>
												<td><?php echo $values['thalisize']; ?></td>
												<td><?php echo $values['sabeelType'] ?></td>
												<td><?php echo $values['NAME']; ?></td>
												<td><?php echo $values['Previous_Due']; ?></td>
												<td><?php echo $values['previous_hub']; ?></td>
												<td><?php echo $values['yearly_hub']; ?></td>
												<td><?php echo $values['Total_Pending']; ?></td>
												<td><?php echo $values['Paid %']; ?></td>
											</form>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<?php
						foreach ($thali_details as $values) {
							include('_thali_details_musaid.php');
						}
						?>
					</div>
				</div>
			</div>
	<?php }
	} ?>
</div>

<?php include('footer.php'); ?>

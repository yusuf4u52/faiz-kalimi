<?php
include('header.php');
include('navbar.php');
require_once('helpers.php');

// NOTE: these are used by _thali_details_musaid.php, included per-thali
// further down — don't remove them even though they look unused here.
$current_year = mysqli_fetch_assoc(db_query($link, "SELECT value FROM settings WHERE `key` = 'current_year'"));
$previous_year = ((int) $current_year['value']) - 1;

$previous_thalilist = "thalilist_" . $previous_year;
$previous_receipts = "receipts_" . $previous_year;

$max_days = mysqli_fetch_row(db_query($link, "SELECT MAX(thalicount) as max FROM `thalilist`"));

// Table name interpolation here is unavoidable (table names can't be bound
// as query parameters), but $previous_year is cast to int above, so this
// can only ever be `thalilist_<integer>` — not attacker-influenced.
$val = mysqli_query($link, "SELECT MAX(thalicount) as max FROM `$previous_thalilist`");
$max_days_previous = ($val !== false) ? mysqli_fetch_row($val) : [1];

if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['superadmin', 'admin'], true)) {
    $musaid_list = mysqli_fetch_all(
        db_query($link, "SELECT `id`, `email`, `username` FROM `users` WHERE `role` IN ('musaid', 'admin', 'superadmin')"),
        MYSQLI_ASSOC
    );
} else {
    $musaid_list = [
        [
            'id' => 0,
            'username' => $_SESSION['email'],
            'email' => $_SESSION['email'],
        ],
    ];
}
?>

<div class="accordion" id="accordionMusaid">
	<?php foreach ($musaid_list as $musaid) {
        $result = db_query(
            $link,
            "SELECT * FROM thalilist WHERE (Previous_Due + yearly_hub - Paid) >= yearly_hub AND yearly_hub > 10 AND Transporter IS NOT NULL AND musaid = ? AND hardstop != 1 ORDER BY `Paid %`",
            "s",
            [$musaid['email']]
        );
        $thali_details = mysqli_fetch_all($result, MYSQLI_ASSOC);
        $musaid_thali_count = count($thali_details);
        if ($musaid_thali_count > 0) { ?>
			<div class="accordion-item">
				<h2 class="accordion-header" id="heading<?php echo (int) $musaid['id']; ?>">
					<button class="accordion-button <?php if (count($musaid_list) !== 1)
													echo "collapsed"; ?>" type="button" data-bs-toggle="collapse"
						data-bs-target="#collapse<?php echo (int) $musaid['id']; ?>" aria-expanded="true"
						aria-controls="collapse<?php echo (int) $musaid['id']; ?>">
						<?php echo e($musaid['username']); ?> - (<?php echo $musaid_thali_count; ?>)
					</button>
				</h2>
				<div id="collapse<?php echo (int) $musaid['id']; ?>"
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
										<th scope="col">Contact</th>
										<th scope="col">Whatsapp</th>
										<th scope="col">Thali Size</th>
										<th scope="col">Sabeel Type</th>
										<th scope="col">Transporter</th>
										<th scope="col">Name</th>
										<!--<th scope="col">Previous Due</th>
										<th scope="col">Previous Hub</th>
										<th scope="col">Current Hub</th>-->
										<th scope="col">Pending Hoob</th>
										<!-- <th scope="col">Paid %</th>-->
									</tr>
								</thead>
								<tbody>
									<?php
                                    foreach ($thali_details as $values) { ?>
										<tr>
											<form method="post">
												<input type='hidden' value='<?php echo e($values['Thali']); ?>' name='Thali'>
												<td>
													<?php echo e($values['Thali']); ?>
													&nbsp;
													<!--<a data-bs-toggle="modal" href="#details-<?php echo e($values['Thali']); ?>">
														<img src="/fmb/assets/img/view.avif" style="width:20px;height:20px;">
													</a>-->
												</td>
												<td><?php echo e($values['tiffinno']); ?></td>
												<td><a href="tel:<?php echo e($values['CONTACT']); ?>">Call</a></td>
												<td>
													<?php
                                                    $msg = "*Salaam " . $values['NAME'] . "*,\n\n";
                                                    $msg .= "*Reminder - FMB Hoob Pending*\n\n";
                                                    $msg .= "Aapna ghare *Faiz ul Mawaid il Burhaniyah* ni barakat pohchi rahi che. Aapni fmb ni hoob baki che, je ni tafseel niche aapi che.\n\n";
                                                    $msg .= "*Sabil No:* " . $values['Thali'] . "\n";
                                                    $msg .= "*ITS No:* " . $values['ITS_No'] . "\n";
                                                    $msg .= "*Pending Hoob:* ₹" . number_format((float) $values['Total_Pending']) . "\n\n";
                                                    $msg .= "FMB ni hoob ada kerva waste *6th Miqaat - Chelum Imam Husain (AS)* gujri chuko che. Aap si adab sathe iltemas che ke aap aa pending hoob jaldi si ada kari ne FMB khidmat team ne yaari aapiye. Timely payment si Faiz ane sagli khidmat nu nizam barabar chale che.\n\n";
                                                    $msg .= "Agar aap aa hoob pehla thi ada kari chuka ho, to payment ni receipt ya transfer nu screenshot hamne mokli aapsho taake hame apna records update kari shakay.\n\n";
                                                    $msg .= "Was Salaam,\n";
                                                    $msg .= "*FMB Khidmat Team*";
                                                    ?>
													<a href="https://wa.me/91<?php echo e($values['WhatsApp']); ?>?text=<?php echo urlencode($msg); ?>" target="_blank">
														WhatsApp
													</a>
												</td>
												<td><?php echo e($values['thalisize']); ?></td>
												<td><?php echo e($values['sabeelType']); ?></td>
												<td><?php echo e($values['Transporter']); ?></td>
												<td><?php echo e($values['NAME']); ?></td>
												<!--<td><?php echo e((string) $values['Previous_Due']); ?></td>
												<td><?php echo e((string) $values['previous_hub']); ?></td>
												<td><?php echo e((string) $values['yearly_hub']); ?></td>-->
												<td><strong><?php echo e((string) $values['Total_Pending']); ?></strong></td>
												<!-- <td><?php echo e((string) $values['Paid %']); ?></td>-->
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

<?php
$val = mysqli_query($link, "SELECT id, Thali, NAME, CONTACT, yearly_hub, Total_Pending, Previous_Due, Paid, thalicount, WhatsApp FROM $previous_thalilist where Thali='" . $values['Thali'] . "'");
if ($val !== FALSE) {
    $previous_values = mysqli_fetch_assoc($val);
} else {
    $previous_values = null;
}
?>

<div class="modal fade" id="details-<?php echo $values['Thali']; ?>" tabindex="-1"
    aria-labelledby="details-<?php echo $values['Thali']; ?>-Label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
				<h4 class="modal-title fs-5">Details - Thali# <?php echo $values['Thali']; ?> <?php echo $values['NAME']; ?></h4>
                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
				<div class="accordion" id="accordion<?php echo $values['Thali']; ?>Details">
					<div class="accordion-item">
						<h2 class="accordion-header" id="heading<?php echo $values['Thali']; ?>">
							<button class="accordion-button" type="button" data-bs-toggle="collapse"
								data-bs-target="#collapse-<?php echo $values['Thali']; ?>" aria-expanded="true"
								aria-controls="collapse<?php echo $values['Thali']; ?>">
								Thali Details
							</button>
						</h2>
						<div id="collapse-<?php echo $values['Thali']; ?>"
							class="accordion-collapse collapse show"
							data-bs-parent="#accordion<?php echo $values['Thali']; ?>Details">
							<div class="accordion-body">
								 <ul class="list-group list-group-flush">
									<li class="list-group-item">
										<div class="fw-bold">ITS No</div>
										<?php echo $values['ITS_No']; ?>
									</li>
									<li class="list-group-item">
										<div class="fw-bold">Contact</div>
										<?php echo $values['CONTACT']; ?>
									</li>
								</ul>
							</div>
						</div>
					</div>

					<?php if (!is_null($previous_values)) { ?>
						<div class="accordion-item">
							<h2 class="accordion-header" id="heading<?php echo $values['Thali']; ?><?php echo $previous_year; ?>">
								<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
									data-bs-target="#collapse-<?php echo $values['Thali']; ?><?php echo $previous_year; ?>" aria-expanded="true"
									aria-controls="collapse<?php echo $values['Thali']; ?><?php echo $previous_year; ?>">
									Year <?php echo $previous_year; ?>
								</button>
							</h2>
							<div id="collapse-<?php echo $values['Thali']; ?><?php echo $previous_year; ?>"
								class="accordion-collapse collapse"
								data-bs-parent="#accordion<?php echo $values['Thali']; ?>Details">
								<div class="accordion-body">
									 <ul class="list-group list-group-flush">
										<li class="list-group-item">
											<div class="fw-bold">Hub Pending (Yearly Takhmeen + Previous
														Due - Paid = Total Pending)</div>
												<?php echo $previous_values['yearly_hub']; ?> +
														<?php echo $previous_values['Previous_Due']; ?> -
														<?php echo $previous_values['Paid']; ?> =
														<?php echo $previous_values['Total_Pending']; ?>
										</li>
										<li class="list-group-item">
											<div class="fw-bold">Thali Delivered</div>
											<?php echo round($previous_values['thalicount'] * 100 / $max_days_previous[0]); ?>%
													of days
										</li>
										<li class="list-group-item">
											<div class="table-responsive">
												<table class="table table-striped table-bordered display" style="width:100%">
													<thead>
														<tr>
															<th>Receipt No</th>
															<th>Amount</th>
															<th>Date</th>
														</tr>
													</thead>
													<tbody>
														<?php
														$query = "SELECT r.* FROM $previous_receipts r, $previous_thalilist t WHERE r.userid = t.id and t.id ='" . $previous_values['id'] . "' ORDER BY Date ASC";
														$result = mysqli_query($link, $query);
														while ($row = mysqli_fetch_assoc($result)) {
															foreach ($row as $key => $value) {
																$row[$key] = stripslashes($value);
															}
															echo "<tr>";
															echo "<td>" . nl2br($row['Receipt_No']) . "</td>";
															echo "<td>" . nl2br($row['Amount']) . "</td>";
															echo "<td class=\"hijridate\">" . nl2br($row['Date']) . "</td>";
															echo "</tr>";
														}
														?>
													</tbody>
												</table>
											</div>
										</li>
									</ul>
								</div>
							</div>
						</div>
					<?php } ?>
					
					<div class="accordion-item">
						<h2 class="accordion-header" id="heading<?php echo $values['Thali']; ?><?php echo $current_year['value']; ?>">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
								data-bs-target="#collapse-<?php echo $values['Thali']; ?><?php echo $current_year['value']; ?>" aria-expanded="true"
								aria-controls="collapse<?php echo $values['Thali']; ?><?php echo $current_year['value']; ?>">
								Year <?php echo $current_year['value']; ?>
							</button>
						</h2>
						<div id="collapse-<?php echo $values['Thali']; ?><?php echo $current_year['value']; ?>"
							class="accordion-collapse collapse"
							data-bs-parent="#accordion<?php echo $values['Thali']; ?>Details">
							<div class="accordion-body">
								<ul class="list-group list-group-flush">
									<li class="list-group-item">
										<div class="fw-bold">Hub Pending (Yearly Takhmeen + Previous
											Due - Paid = Total Pending)</div>
										<?php echo $values['yearly_hub']; ?> +
											<?php echo $values['Previous_Due']; ?> - <?php echo $values['Paid']; ?> =
											<?php echo $values['Total_Pending']; ?>
									</li>
									<li class="list-group-item">
										<div class="fw-bold">Thali Delivered</div>
										<?php echo round($values['thalicount'] * 100 / $max_days[0]); ?>% of days
									</li>
									<li class="list-group-item">
										<div class="table-responsive">
											<table class="table table-striped table-bordered display" style="width:100%">
												<thead>
													<tr>
														<th>Receipt No</th>
														<th>Amount</th>
														<th>Date</th>
													</tr>
												</thead>
												<tbody>
													<?php
													$query = "SELECT r.* FROM receipts r, thalilist t WHERE r.userid = t.id and t.id ='" . $values['id'] . "' ORDER BY Date ASC";
													$result = mysqli_query($link, $query);
													while ($row = mysqli_fetch_assoc($result)) {
														foreach ($row as $key => $value) {
															$row[$key] = stripslashes($value);
														}
														echo "<tr>";
														echo "<td>" . nl2br($row['Receipt_No']) . "</td>";
														echo "<td>" . nl2br($row['Amount']) . "</td>";
														echo "<td class=\"hijridate\">" . nl2br($row['Date']) . "</td>";
														echo "</tr>";
													}
													?>
												</tbody>
											</table>
										</div>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
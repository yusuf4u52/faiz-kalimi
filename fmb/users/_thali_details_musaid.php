<?php
// Table name interpolation here is unavoidable (table names can't be bound
// as query parameters); $previous_thalilist is built from an int-cast year
// in musaid.php, not from request input.
$stmt = mysqli_prepare($link, "SELECT id, Thali, NAME, CONTACT, yearly_hub, Total_Pending, Previous_Due, Paid, thalicount, WhatsApp FROM `$previous_thalilist` WHERE Thali = ?");
if ($stmt !== false) {
    mysqli_stmt_bind_param($stmt, "s", $values['Thali']);
    mysqli_stmt_execute($stmt);
    $previous_values = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
} else {
    $previous_values = null;
}
?>

<div class="modal fade" id="details-<?php echo e($values['Thali']); ?>" tabindex="-1"
	aria-labelledby="details-<?php echo e($values['Thali']); ?>-Label" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title fs-5">Details - Thali# <?php echo e($values['Thali']); ?> <?php echo e($values['NAME']); ?></h4>
				<button type="button" class="btn ms-auto" data-bs-dismiss="modal"
					aria-label="Close"><i class="bi bi-x-lg"></i></button>
			</div>
			<div class="modal-body">
				<div class="accordion" id="accordion<?php echo e($values['Thali']); ?>Details">
					<div class="accordion-item">
						<h2 class="accordion-header" id="heading<?php echo e($values['Thali']); ?>">
							<button class="accordion-button" type="button" data-bs-toggle="collapse"
								data-bs-target="#collapse-<?php echo e($values['Thali']); ?>" aria-expanded="true"
								aria-controls="collapse<?php echo e($values['Thali']); ?>">
								Thali Details
							</button>
						</h2>
						<div id="collapse-<?php echo e($values['Thali']); ?>"
							class="accordion-collapse collapse show"
							data-bs-parent="#accordion<?php echo e($values['Thali']); ?>Details">
							<div class="accordion-body">
								<ul class="list-group list-group-flush">
									<li class="list-group-item">
										<div class="fw-bold">ITS No</div>
										<?php echo e($values['ITS_No']); ?>
									</li>
									<li class="list-group-item">
										<div class="fw-bold">Contact</div>
										<?php echo e($values['CONTACT']); ?>
									</li>
								</ul>
							</div>
						</div>
					</div>

					<?php if ($previous_values !== null) { ?>
						<div class="accordion-item">
							<h2 class="accordion-header" id="heading<?php echo e($values['Thali']); ?><?php echo (int) $previous_year; ?>">
								<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
									data-bs-target="#collapse-<?php echo e($values['Thali']); ?><?php echo (int) $previous_year; ?>" aria-expanded="true"
									aria-controls="collapse<?php echo e($values['Thali']); ?><?php echo (int) $previous_year; ?>">
									Year <?php echo (int) $previous_year; ?>
								</button>
							</h2>
							<div id="collapse-<?php echo e($values['Thali']); ?><?php echo (int) $previous_year; ?>"
								class="accordion-collapse collapse"
								data-bs-parent="#accordion<?php echo e($values['Thali']); ?>Details">
								<div class="accordion-body">
									<ul class="list-group list-group-flush">
										<li class="list-group-item">
											<div class="fw-bold">Hub Pending (Yearly Takhmeen + Previous
												Due - Paid = Total Pending)</div>
											<?php echo e((string) $previous_values['yearly_hub']); ?> +
											<?php echo e((string) $previous_values['Previous_Due']); ?> -
											<?php echo e((string) $previous_values['Paid']); ?> =
											<?php echo e((string) $previous_values['Total_Pending']); ?>
										</li>
										<li class="list-group-item">
											<div class="fw-bold">Thali Delivered</div>
											<?php echo (($max_days_previous[0] ?? 0) > 0) ? round($previous_values['thalicount'] * 100 / $max_days_previous[0]) . '%' : '0%'; ?> of days
										</li>
										<li class="list-group-item">
											<div class="table-responsive">
												<table class="table table-striped table-bordered display" style="width:100%">
													<thead>
														<tr>
															<th>Receipt No</th>
															<th>Amount</th>
															<th>Date</th>
															<th>Takmeem Year</th>
														</tr>
													</thead>
													<tbody>
														<?php
                                                        // Table name interpolation unavoidable — same as above, not
                                                        // attacker-influenced.
                                                        $stmt = mysqli_prepare(
                                                            $link,
                                                            "SELECT r.* FROM `$previous_receipts` r, `$previous_thalilist` t WHERE r.userid = t.id AND t.id = ? ORDER BY r.Date ASC"
                                                        );
                                                        mysqli_stmt_bind_param($stmt, "s", $previous_values['id']);
                                                        mysqli_stmt_execute($stmt);
                                                        $result = mysqli_stmt_get_result($stmt);
                                                        while ($row = mysqli_fetch_assoc($result)) {
                                                            echo "<tr>";
                                                            echo "<td>" . nl2br(e($row['Receipt_No'])) . "</td>";
                                                            echo "<td>" . nl2br(e((string) $row['Amount'])) . "</td>";
                                                            echo "<td>" . e(date('d M Y', strtotime($row['Date']))) . "</td>";
                                                            echo "<td>" . nl2br(e($row['takmeem_year'])) . "</td>";
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
						<h2 class="accordion-header" id="heading<?php echo e($values['Thali']); ?><?php echo e((string) $current_year['value']); ?>">
							<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
								data-bs-target="#collapse-<?php echo e($values['Thali']); ?><?php echo e((string) $current_year['value']); ?>" aria-expanded="true"
								aria-controls="collapse<?php echo e($values['Thali']); ?><?php echo e((string) $current_year['value']); ?>">
								Year <?php echo e((string) $current_year['value']); ?>
							</button>
						</h2>
						<div id="collapse-<?php echo e($values['Thali']); ?><?php echo e((string) $current_year['value']); ?>"
							class="accordion-collapse collapse"
							data-bs-parent="#accordion<?php echo e($values['Thali']); ?>Details">
							<div class="accordion-body">
								<ul class="list-group list-group-flush">
									<li class="list-group-item">
										<div class="fw-bold">Hub Pending (Yearly Takhmeen + Previous
											Due - Paid = Total Pending)</div>
										<?php echo e((string) $values['yearly_hub']); ?> +
										<?php echo e((string) $values['Previous_Due']); ?> - <?php echo e((string) $values['Paid']); ?> =
										<?php echo e((string) $values['Total_Pending']); ?>
									</li>
									<li class="list-group-item">
										<div class="fw-bold">Thali Delivered</div>
										<?php echo (($max_days[0] ?? 0) > 0) ? round($values['thalicount'] * 100 / $max_days[0]) . '%' : '0%'; ?> of days
									</li>
									<li class="list-group-item">
										<div class="table-responsive">
											<table class="table table-striped table-bordered display" style="width:100%">
												<thead>
													<tr>
														<th>Receipt No</th>
														<th>Amount</th>
														<th>Date</th>
														<th>Takhmem Year</th>
													</tr>
												</thead>
												<tbody>
													<?php
                                                    $result = db_query(
                                                        $link,
                                                        "SELECT r.* FROM receipts r, thalilist t WHERE r.userid = t.id AND t.id = ? ORDER BY r.Date ASC",
                                                        "s",
                                                        [$values['id']]
                                                    );
                                                    while ($row = mysqli_fetch_assoc($result)) {
                                                        echo "<tr>";
                                                        echo "<td>" . nl2br(e($row['Receipt_No'])) . "</td>";
                                                        echo "<td>" . nl2br(e((string) $row['Amount'])) . "</td>";
                                                        echo "<td>" . e(date('d M Y', strtotime($row['Date']))) . "</td>";
                                                        echo "<td>" . nl2br(e($row['takmeem_year'])) . "</td>";
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

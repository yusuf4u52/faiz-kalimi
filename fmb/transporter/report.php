<?php
include('header.php');
include('navbar.php');

$currentMonth = date('Y-m');
$requestedMonth = $_GET['payment_month'] ?? null;
$formatValid = $requestedMonth !== null
    && preg_match('/^\d{4}-\d{2}$/', $requestedMonth)
    && DateTime::createFromFormat('Y-m', $requestedMonth) !== false;

if ($requestedMonth === null) {
    $paymentMonth = $currentMonth;
    $noticeMessage = null;
} elseif (!$formatValid) {
    $paymentMonth = $currentMonth;
    $noticeMessage = 'Please choose a valid month. Showing the current month instead.';
} elseif ($requestedMonth > $currentMonth) {
    $paymentMonth = $currentMonth;
    $noticeMessage = "Future months aren't available yet. Showing the current month instead.";
} else {
    $paymentMonth = $requestedMonth;
    $noticeMessage = null;
}

$prevMonth = date('Y-m', strtotime($paymentMonth . '-01 -1 month'));
$nextMonth = date('Y-m', strtotime($paymentMonth . '-01 +1 month'));
$canGoNext = $nextMonth <= $currentMonth;
$transporterName = (string) ($_SESSION['transporter'] ?? '');
$transporterId = $_SESSION['transporterid'] ?? null;

// Rate is per-transporter, stored on the transporters table, so the ₹/thali
// figure below reflects whatever that transporter is actually charging
// rather than a value hardcoded into this page.
$ratePerThali = null;
if ($transporterId !== null) {
    try {
        $rateResult = db_query($link, "SELECT `rate_per_thali` FROM `transporters` WHERE `id` = ? LIMIT 1", "i", [$transporterId]);
        if ($rateResult->num_rows > 0) {
            $ratePerThali = (float) $rateResult->fetch_assoc()['rate_per_thali'];
        }
    } catch (RuntimeException $e) {
        error_log('[report.php] ' . $e->getMessage());
    }
}
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-1">Daily Thali Report</h2>
                <p class="text-muted text-capitalize mb-3">
                    <?php echo e(strtolower($transporterName)); ?> &middot; <?php echo e(date('F Y', strtotime($paymentMonth . '-01'))); ?>
                    <?php if ($ratePerThali !== null) { ?>
                        &middot; Rate: &#8377;<?php echo e(number_format($ratePerThali, 2)); ?> / thali
                    <?php } ?>
                </p>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="btn-group" role="group" aria-label="Month navigation">
                    <a class="btn btn-outline-secondary" href="?payment_month=<?php echo e($prevMonth); ?>">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                    <a class="btn btn-outline-secondary<?php echo ($paymentMonth === $currentMonth) ? ' active disabled' : ''; ?>" href="?payment_month=<?php echo e($currentMonth); ?>">
                        Current Month
                    </a>
                    <?php if ($canGoNext) { ?>
                        <a class="btn btn-outline-secondary" href="?payment_month=<?php echo e($nextMonth); ?>">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php } else { ?>
                        <span class="btn btn-outline-secondary disabled">
                            Next <i class="bi bi-chevron-right"></i>
                        </span>
                    <?php } ?>
                </div>

                <form id="rotipayment" class="d-flex align-items-center gap-2" method="GET" autocomplete="off">
                    <label for="payment_month" class="control-label mb-0">Jump to month</label>
                    <input type="month" class="form-control" name="payment_month" id="payment_month" max="<?php echo e($currentMonth); ?>" value="<?php echo e($paymentMonth); ?>">
                    <button class="btn btn-light" type="submit" name="search">Go</button>
                </form>
            </div>
        </div>

        <?php if ($noticeMessage !== null) { ?>
            <div class="alert alert-danger" role="alert">
                <?php echo e($noticeMessage); ?>
            </div>
        <?php } ?>

        <?php if ($transporterName === '') { ?>
            <div class="alert alert-danger" role="alert">
                Unable to determine your transporter account. Please log in again.
            </div>
        <?php } else {
            $startDate = $paymentMonth . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            try {
                $daily_count = db_query(
                    $link,
                    "SELECT `date`, SUM(`mini`) as total_mini, SUM(`small`) as total_small,
                            SUM(`medium`) as total_medium, SUM(`large`) as total_large,
                            SUM(`friday`) as total_friday, SUM(`barnamaj`) as total_barnamaj, SUM(`roti`) as total_roti, SUM(`count`) as total_count
                     FROM `transporter_daily_count`
                     WHERE `name` = ? AND `date` BETWEEN ? AND ?
                     GROUP BY `date`
                     ORDER BY `date` ASC",
                    "sss",
                    [$transporterName, $startDate, $endDate]
                );
            } catch (RuntimeException $e) {
                error_log('[report.php] ' . $e->getMessage());
                $daily_count = null;
            }

            if ($daily_count === null) { ?>
                <div class="alert alert-danger" role="alert">
                    Sorry, something went wrong loading the report. Please try again in a moment.
                </div>
            <?php } elseif ($daily_count->num_rows > 0) {
                $totals = ['mini' => 0, 'small' => 0, 'medium' => 0, 'large' => 0, 'friday' => 0, 'barnamaj' => 0, 'count' => 0];
                $rows = [];
                while ($row = mysqli_fetch_assoc($daily_count)) {
                    $rows[] = $row;
                    $totals['mini'] += (int) $row['total_mini'];
                    $totals['small'] += (int) $row['total_small'];
                    $totals['medium'] += (int) $row['total_medium'];
                    $totals['large'] += (int) $row['total_large'];
                    $totals['friday'] += (int) $row['total_friday'];
                    $totals['barnamaj'] += (int) $row['total_barnamaj'];
                    $totals['roti'] += (int) $row['total_roti'];
                    $totals['count'] += (int) $row['total_count'];
                }
                mysqli_free_result($daily_count);
                $totalAmount = $ratePerThali !== null ? $totals['count'] * $ratePerThali : null; ?>

                <div class="row mb-3">
                    <div class="col-12 col-md-4 mb-2">
                        <div class="card bg-light">
                            <div class="card-body py-3">
                                <div class="text-muted small">Total Thalis This Month</div>
                                <div class="fs-4 fw-bold"><?php echo (int) $totals['count']; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-2">
                        <div class="card bg-light">
                            <div class="card-body py-3">
                                <div class="text-muted small">Rate per Thali</div>
                                <div class="fs-4 fw-bold">
                                    <?php echo $ratePerThali !== null ? '&#8377;' . e(number_format($ratePerThali, 2)) : 'Not set'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-2">
                        <div class="card bg-light">
                            <div class="card-body py-3">
                                <div class="text-muted small">Total Amount</div>
                                <div class="fs-4 fw-bold">
                                    <?php echo $totalAmount !== null ? '&#8377;' . e(number_format($totalAmount, 2)) : 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($ratePerThali === null) { ?>
                    <div class="alert alert-warning" role="alert">
                        No rate is configured for your transporter account, so the amount can't be calculated. Please contact the Jamaat office to have it set.
                    </div>
                <?php } ?>

                <div class="table-responsive mb-3">
                    <table class="table table-striped table-hover display">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Mini</th>
                                <th>Small</th>
                                <th>Medium</th>
                                <th>Large</th>
                                <th>Friday</th>
                                <th>Barnamaj</th>
                                <th>Roti</th>
                                <th>Thali</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row) { ?>
                                <tr>
                                    <td data-order="<?php echo e($row['date']); ?>"><?php echo e(date('d M Y (l)', strtotime($row['date']))); ?></td>
                                    <td><?php echo (int) $row['total_mini']; ?></td>
                                    <td><?php echo (int) $row['total_small']; ?></td>
                                    <td><?php echo (int) $row['total_medium']; ?></td>
                                    <td><?php echo (int) $row['total_large']; ?></td>
                                    <td><?php echo (int) $row['total_friday']; ?></td>
                                    <td><?php echo (int) $row['total_barnamaj']; ?></td>
                                    <td><?php echo (int) $row['total_roti']; ?></td>
                                    <td><?php echo (int) $row['total_count']; ?></td>
                                    <td>
                                        <?php
                                        if ($ratePerThali !== null) {
                                            echo '&#8377;' . e(number_format($row['total_count'] * $ratePerThali, 2));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td><?php echo $totals['mini']; ?></td>
                                <td><?php echo $totals['small']; ?></td>
                                <td><?php echo $totals['medium']; ?></td>
                                <td><?php echo $totals['large']; ?></td>
                                <td><?php echo $totals['friday']; ?></td>
                                <td><?php echo $totals['barnamaj']; ?></td>
                                <td><?php echo $totals['roti']; ?></td>
                                <td><?php echo $totals['count']; ?></td>
                                <td><?php echo $totalAmount !== null ? '&#8377;' . e(number_format($totalAmount, 2)) : 'N/A'; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php } else { ?>
                <div class="alert alert-info" role="alert">
                    No thali deliveries recorded for <?php echo e(date('F Y', strtotime($paymentMonth . '-01'))); ?>.
                </div>
            <?php }
        } ?>
    </div>
</div>

<?php include('footer.php'); ?>
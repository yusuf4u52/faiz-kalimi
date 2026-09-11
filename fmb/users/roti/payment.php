<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');
include('../getHijriDate.php');

$weekValue = (string) ($_GET['week_date'] ?? date('o-\\WW'));
$isValidWeek = preg_match('/^\d{4}-W(0[1-9]|[1-4]\d|5[0-3])$/', $weekValue) === 1;
$weekDate = date('Y-m-d');
if ($isValidWeek) {
    [$weekYear, $weekNumber] = array_map('intval', explode('-W', $weekValue));
    $weekDateObject = new DateTime();
    $weekDateObject->setISODate($weekYear, $weekNumber, 1);
    $weekDate = $weekDateObject->format('Y-m-d');
}

$selectedMakerRaw = (string) ($_GET['maker_id'] ?? '');
$selectedMakerId = filter_var($selectedMakerRaw, FILTER_VALIDATE_INT);
$selectedMakerId = $selectedMakerId !== false && $selectedMakerId > 0 ? $selectedMakerId : null;

$makers = [];
$reportError = false;

try {
    $makersResult = db_query($link, "SELECT `id`, `code`, `full_name` FROM fmb_roti_maker ORDER BY `full_name` ASC");
    $makers = mysqli_fetch_all($makersResult, MYSQLI_ASSOC);
    mysqli_free_result($makersResult);
} catch (RuntimeException $e) {
    error_log('[users/roti/payment.php] ' . $e->getMessage());
    $reportError = true;
}

$payment = null;
if (!$reportError && $isValidWeek) {
    if ($selectedMakerId !== null) {
        $payment = build_maker_daily_payment($link, $selectedMakerId, $weekDate);
    } else {
        $payment = build_week_payment($link, $weekDate);
    }
}

?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">
                    Roti Maker Payment Report
                    <?php if ($payment) { ?>
                        for <?php echo e(date('d-m-Y', strtotime($payment['from'])) . ' to ' . date('d-m-Y', strtotime($payment['to']))); ?>
                    <?php } ?>
                </h2>
            </div>
        </div>

        <?php if (!$isValidWeek) { ?>
            <div class="alert alert-danger" role="alert">Please choose a valid week date.</div>
        <?php } ?>
        <?php if ($reportError) { ?>
            <div class="alert alert-danger" role="alert">Could not load the Roti Maker list. Please try again.</div>
        <?php } ?>

        <form id="rotipayment" class="form-horizontal" method="GET" action="<?php echo e($_SERVER['PHP_SELF']); ?>" autocomplete="off">
            <div class="mb-3 row align-items-center">
                <label for="maker_id" class="col-md-2 col-form-label">Roti Maker</label>
                <div class="col-md-3 mb-2 mb-md-0">
                    <select class="form-select" name="maker_id" id="maker_id">
                        <option value="">All Roti Makers</option>
                        <?php foreach ($makers as $maker) { ?>
                            <option value="<?php echo e((string) $maker['id']); ?>"<?php echo $selectedMakerId === (int) $maker['id'] ? ' selected' : ''; ?>>
                                <?php echo e($maker['full_name']); ?> (<?php echo e($maker['code']); ?>)
                            </option>
                        <?php } ?>
                    </select>
                </div>
                 <label for="report_period" class="col-md-3 col-form-label">Report Week</label>
                <div class="col-md-2 mb-2 mb-md-0">
                    <input type="week" class="form-control" name="week_date" id="report_period" value="<?php echo e($weekValue); ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-light w-100" type="submit" name="search">Filter</button>
                </div>
            </div>
        </form>

        <?php if ($payment && count($payment['rows']) > 0) {
            $totalRoti = 0;
            $totalGross = 0.0;
            $totalFaiz = 0.0;
            $totalPayout = 0.0;
            foreach ($payment['rows'] as $row) {
                $totalRoti += $row['total_roti'];
                $totalGross += $row['gross_payout'];
                $totalFaiz += $row['faiz_contribution'];
                $totalPayout += $row['total_payout'];
            }
        ?>
            <div class="row mb-3">
                <div class="col-12 col-md-3 mb-2">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">Total Roti Made (Week: <?php echo e(date('d-m-Y', strtotime($payment['from']))); ?> to <?php echo e(date('d-m-Y', strtotime($payment['to']))); ?>)</div>
                            <div class="fs-4 fw-bold"><?php echo (int) $totalRoti; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">Rate per Roti</div>
                            <div class="fs-4 fw-bold">&#8377;5</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">Faiz Deduction</div>
                            <div class="fs-4 fw-bold">&#8377;<?php echo e(number_format($totalFaiz, 2)); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3 mb-2">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">Net Amount Payable</div>
                            <div class="fs-4 fw-bold">&#8377;<?php echo e(number_format($totalPayout, 2)); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table id="paymentreport" class="table table-striped table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <?php if ($selectedMakerId !== null) { ?>
                                <th>Date</th>
                            <?php } else { ?>
                                <th>Code</th>
                                <th>Roti Maker</th>
                                <th>Mobile No.</th>
                                <th>Bank Details</th>
                            <?php } ?>
                            <th>Total Roti Made</th>
                            <th>Rate / Roti</th>
                            <th>Gross Amount</th>
                            <th>Faiz Deduction</th>
                            <th>Net Payable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment['rows'] as $row) {
                            // Skip Sundays when viewing weekly report by maker
                            if ($selectedMakerId !== null) {
                                $dayOfWeek = date('w', strtotime($row['date']));
                                if ($dayOfWeek == 0) { // 0 = Sunday
                                    continue;
                                }
                            }
                        ?>
                            <tr>
                                <?php if ($selectedMakerId !== null) { ?>
                                    <td><?php echo e(date('d M Y (l)', strtotime($row['date']))); ?></td>
                                <?php } else { ?>
                                    <td><?php echo e($row['code']); ?></td>
                                    <td><?php echo e($row['full_name']); ?></td>
                                    <td><?php echo e($row['mobile_no']); ?></td>
                                    <td>
                                        <?php foreach (preg_split('/\r\n|\r|\n/', (string) $row['bank_details']) as $line) { ?>
                                            <?php if (trim($line) !== '') { ?>
                                                <div><?php echo e($line); ?></div>
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                <?php } ?>
                                <td><?php echo (int) $row['total_roti']; ?></td>
                                <td>&#8377;<?php echo e(number_format($payment['amount_per_roti'], 2)); ?></td>
                                <td>&#8377;<?php echo e(number_format($row['gross_payout'], 2)); ?></td>
                                <td>&#8377;<?php echo e(number_format($row['faiz_contribution'], 2)); ?></td>
                                <td>&#8377;<?php echo e(number_format($row['total_payout'], 2)); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="<?php echo $selectedMakerId !== null ? '1' : '4'; ?>">Grand Total</td>
                            <td><?php echo (int) $totalRoti; ?></td>
                            <td>-</td>
                            <td>&#8377;<?php echo e(number_format($totalGross, 2)); ?></td>
                            <td>&#8377;<?php echo e(number_format($totalFaiz, 2)); ?></td>
                            <td>&#8377;<?php echo e(number_format($totalPayout, 2)); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php } elseif ($payment) { ?>
            <div class="alert alert-danger" role="alert">
                No roti-received data found for the selected week<?php echo $selectedMakerId !== null ? ' and Roti Maker' : ''; ?>.
            </div>
        <?php } ?>
    </div>
</div>

<?php include('../footer.php'); ?>

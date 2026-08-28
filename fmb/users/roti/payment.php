<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');
include('../getHijriDate.php');

$view = ($_GET['view'] ?? 'month') === 'week' ? 'week' : 'month';
$monthValue = (string) ($_GET['month_date'] ?? date('Y-m'));
$weekValue = (string) ($_GET['week_date'] ?? date('o-\\WW'));
$isValidMonth = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthValue) === 1;
$isValidWeek = preg_match('/^\d{4}-W(0[1-9]|[1-4]\d|5[0-3])$/', $weekValue) === 1;
$anchor = $isValidMonth ? $monthValue . '-01' : date('Y-m-d');
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
if (!$reportError && ($view === 'month' ? $isValidMonth : $isValidWeek)) {
    if ($selectedMakerId !== null) {
        $payment = $view === 'month'
            ? build_maker_weekly_payment($link, $selectedMakerId, $anchor)
            : build_maker_daily_payment($link, $selectedMakerId, $weekDate);
    } else {
        $payment = $view === 'month'
            ? build_month_payment($link, $anchor)
            : build_week_payment($link, $weekDate);
    }
    if ($view === 'month') {
        $payment['month_label'] = date('F Y', strtotime($payment['from']));
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
                        for <?php echo e($view === 'month' ? $payment['month_label'] : $payment['from'] . ' to ' . $payment['to']); ?>
                    <?php } ?>
                </h2>
            </div>
        </div>

        <?php if (($view === 'month' && !$isValidMonth) || ($view === 'week' && !$isValidWeek)) { ?>
            <div class="alert alert-danger" role="alert">Please choose a valid <?php echo $view === 'month' ? 'month date' : 'week date'; ?>.</div>
        <?php } ?>
        <?php if ($reportError) { ?>
            <div class="alert alert-danger" role="alert">Could not load the Roti Maker list. Please try again.</div>
        <?php } ?>

        <form id="rotipayment" class="form-horizontal" method="GET" action="<?php echo e($_SERVER['PHP_SELF']); ?>" autocomplete="off">
            <div class="mb-3 row align-items-center">
                <input type="hidden" name="view" value="<?php echo e($view); ?>">
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
                 <label for="report_period" class="col-md-3 col-form-label">Report <?php echo $view === 'month' ? 'Month' : 'Week'; ?></label>
                <div class="col-md-2 mb-2 mb-md-0">
                    <?php if ($view === 'month') { ?>
                        <input type="month" class="form-control" name="month_date" id="report_period" value="<?php echo e($monthValue); ?>">
                    <?php } else { ?>
                        <input type="week" class="form-control" name="week_date" id="report_period" value="<?php echo e($weekValue); ?>">
                    <?php } ?>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-light w-100" type="submit" name="search">Filter</button>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-md-10 offset-md-2">
                    <a class="btn btn-outline-secondary btn-sm" href="?view=<?php echo $view === 'month' ? 'week' : 'month'; ?>&maker_id=<?php echo e((string) ($selectedMakerId ?? '')); ?>">Switch to <?php echo $view === 'month' ? 'Week' : 'Month'; ?> Report</a>
                </div>
            </div>
        </form>

        <?php if ($payment && count($payment['rows']) > 0) {
            $totalRoti = 0;
            $totalPayout = 0.0;
            foreach ($payment['rows'] as $row) {
                $totalRoti += $row['total_roti'];
                $totalPayout += $row['total_payout'];
            }
        ?>
            <div class="row mb-3">
                <div class="col-12 col-md-4 mb-2">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">Total Roti Made (<?php echo $view === 'week' ? 'Week' : 'Month'; ?>: <?php echo e($payment['from']); ?> to <?php echo e($payment['to']); ?>)</div>
                            <div class="fs-4 fw-bold"><?php echo (int) $totalRoti; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-2">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">Rate per Roti</div>
                            <div class="fs-4 fw-bold">&#8377;5</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-2">
                    <div class="card bg-light">
                        <div class="card-body py-3">
                            <div class="text-muted small">Total Amount Payable</div>
                            <div class="fs-4 fw-bold">&#8377;<?php echo e(number_format($totalPayout, 2)); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-striped table-hover display" style="width:100%">
                    <thead>
                        <tr>
                            <?php if ($selectedMakerId !== null) { ?>
                                <?php if ($view === 'week') { ?>
                                    <th>Date</th>
                                <?php } else { ?>
                                    <th>Week</th>
                                <?php } ?>
                            <?php } else { ?>
                                <th>Code</th>
                                <th>Roti Maker</th>
                                <th>Mobile No.</th>
                                <th>Bank Details</th>
                            <?php } ?>
                            <th>Total Roti Made</th>
                            <th>Rate / Roti</th>
                            <th>Total Amount Payable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment['rows'] as $row) { ?>
                            <tr>
                                <?php if ($selectedMakerId !== null) { ?>
                                    <td><?php echo e($view === 'week'
                                        ? date('d M Y (l)', strtotime($row['date']))
                                        : date('d M Y', strtotime($row['week_start'])) . ' to ' . date('d M Y', strtotime($row['week_end']))); ?></td>
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
                                <td>&#8377;<?php echo e(number_format($row['total_payout'], 2)); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="<?php echo $selectedMakerId !== null ? '1' : '4'; ?>">Grand Total</td>
                            <td><?php echo (int) $totalRoti; ?></td>
                            <td>-</td>
                            <td>&#8377;<?php echo e(number_format($totalPayout, 2)); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php } elseif ($payment) { ?>
            <div class="alert alert-danger" role="alert">
                No roti-received data found for the selected <?php echo $view === 'week' ? 'week' : 'month'; ?><?php echo $selectedMakerId !== null ? ' and Roti Maker' : ''; ?>.
            </div>
        <?php } ?>
    </div>
</div>

<?php include('../footer.php'); ?>

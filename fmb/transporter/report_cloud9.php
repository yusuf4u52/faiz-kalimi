<?php
require_once('../users/connection.php');
require_once('../users/helpers.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$transporterName = (string) ($_SESSION['transporter'] ?? '');
$transporterEmail = (string) ($_SESSION['email'] ?? '');
$transporterResult = db_query($link, "SELECT `Name` FROM `transporters` WHERE `Email` = ? LIMIT 1", 's', [$transporterEmail]);
$authenticatedTransporter = $transporterResult->num_rows > 0
    ? (string) $transporterResult->fetch_assoc()['Name']
    : '';
mysqli_free_result($transporterResult);

if (stripos($authenticatedTransporter, 'Murtaza') !== 0) {
    header('Location: /fmb/transporter/report.php');
    exit;
}
$transporterName = $authenticatedTransporter;

include('header.php');
include('navbar.php');

$currentMonth = date('Y-m');
$requestedMonth = (string) ($_GET['payment_month'] ?? $currentMonth);
$monthDate = DateTime::createFromFormat('!Y-m', $requestedMonth);
$isValidMonth = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requestedMonth) === 1
    && $monthDate !== false
    && $monthDate->format('Y-m') === $requestedMonth;
$paymentMonth = $isValidMonth ? $requestedMonth : $currentMonth;
$startDate = $paymentMonth . '-01';
$nextMonth = date('Y-m-d', strtotime($startDate . ' +1 month'));
$ratePerThali = 12.0;
$rows = [];
$reportError = false;

try {
    $result = db_query(
        $link,
        "SELECT dc.`date`,
                SUM(dc.`mini`) AS total_mini, SUM(dc.`small`) AS total_small,
                SUM(dc.`medium`) AS total_medium, SUM(dc.`large`) AS total_large,
                SUM(dc.`roti`) AS total_roti, SUM(dc.`friday`) AS total_friday,
                SUM(dc.`barnamaj`) AS total_barnamaj, SUM(dc.`count`) AS total_count
         FROM `transporter_daily_count` dc
         WHERE LOWER(REPLACE(TRIM(dc.`name`), ' ', '')) = 'murtaza(cloud9)'
           AND dc.`date` >= ? AND dc.`date` < ?
         GROUP BY dc.`date`
         ORDER BY dc.`date` ASC",
        'ss',
        [$startDate, $nextMonth]
    );
    while ($row = mysqli_fetch_assoc($result)) {
        $row['amount'] = (int) $row['total_count'] * $ratePerThali;
        $rows[] = $row;
    }
    mysqli_free_result($result);
} catch (RuntimeException $e) {
    error_log('[report_cloud9.php] ' . $e->getMessage());
    $reportError = true;
}

$totals = ['mini' => 0, 'small' => 0, 'medium' => 0, 'large' => 0, 'roti' => 0, 'friday' => 0, 'barnamaj' => 0, 'count' => 0, 'amount' => 0.0];
foreach ($rows as $row) {
    foreach (['mini', 'small', 'medium', 'large', 'roti', 'friday', 'barnamaj', 'count'] as $key) {
        $totals[$key] += (int) $row['total_' . $key];
    }
    $totals['amount'] += $row['amount'];
}
$prevMonth = date('Y-m', strtotime($startDate . ' -1 month'));
$nextAvailableMonth = date('Y-m', strtotime($startDate . ' +1 month'));
$canGoNext = $nextAvailableMonth <= $currentMonth;
?>

<div class="card">
    <div class="card-body">
        <h2 class="mb-1">Murtaza (Cloud 9) Report</h2>
        <p class="text-muted mb-3"><?php echo e(date('F Y', strtotime($startDate))); ?> &middot; Rate: &#8377;<?php echo e(number_format($ratePerThali, 2)); ?> / thali</p>

        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="btn-group" role="group" aria-label="Month navigation">
                    <a class="btn btn-outline-secondary" href="?payment_month=<?php echo e($prevMonth); ?>">Previous</a>
                    <a class="btn btn-outline-secondary<?php echo $paymentMonth === $currentMonth ? ' active disabled' : ''; ?>" href="?payment_month=<?php echo e($currentMonth); ?>">Current Month</a>
                    <?php if ($canGoNext) { ?><a class="btn btn-outline-secondary" href="?payment_month=<?php echo e($nextAvailableMonth); ?>">Next</a><?php } ?>
                </div>
                <form class="d-flex align-items-center gap-2" method="GET" autocomplete="off">
                    <label for="payment_month" class="mb-0">Jump to month</label>
                    <input type="month" class="form-control" name="payment_month" id="payment_month" max="<?php echo e($currentMonth); ?>" value="<?php echo e($paymentMonth); ?>">
                    <button class="btn btn-light" type="submit">Go</button>
                </form>
            </div>
        </div>

        <?php if (!$isValidMonth) { ?><div class="alert alert-danger" role="alert">Please choose a valid month.</div><?php } ?>
        <?php if ($reportError) { ?>
            <div class="alert alert-danger" role="alert">Sorry, something went wrong loading the report.</div>
        <?php } elseif (count($rows) > 0) { ?>
            <div class="row mb-3">
                <div class="col-12 col-md-4 mb-2"><div class="card bg-light"><div class="card-body py-3"><div class="text-muted small">Total Thaali</div><div class="fs-4 fw-bold"><?php echo $totals['count']; ?></div></div></div></div>
                <div class="col-12 col-md-4 mb-2"><div class="card bg-light"><div class="card-body py-3"><div class="text-muted small">Rate per Thaali</div><div class="fs-4 fw-bold">&#8377;<?php echo e(number_format($ratePerThali, 2)); ?></div></div></div></div>
                <div class="col-12 col-md-4 mb-2"><div class="card bg-light"><div class="card-body py-3"><div class="text-muted small">Total Amount</div><div class="fs-4 fw-bold">&#8377;<?php echo e(number_format($totals['amount'], 2)); ?></div></div></div></div>
            </div>
            <div class="table-responsive mb-3">
                <table class="table table-striped table-hover display">
                    <thead><tr><th>Date</th><th>Mini</th><th>Small</th><th>Medium</th><th>Large</th><th>Roti</th><th>Friday</th><th>Barnamaj</th><th>Thali</th><th>Amount</th></tr></thead>
                    <tbody><?php foreach ($rows as $row) { ?><tr>
                        <td data-order="<?php echo e($row['date']); ?>"><?php echo e(date('d M Y (l)', strtotime($row['date']))); ?></td>
                        <td><?php echo (int) $row['total_mini']; ?></td><td><?php echo (int) $row['total_small']; ?></td><td><?php echo (int) $row['total_medium']; ?></td><td><?php echo (int) $row['total_large']; ?></td><td><?php echo (int) $row['total_roti']; ?></td><td><?php echo (int) $row['total_friday']; ?></td><td><?php echo (int) $row['total_barnamaj']; ?></td><td><?php echo (int) $row['total_count']; ?></td><td>&#8377;<?php echo e(number_format($row['amount'], 2)); ?></td>
                    </tr><?php } ?></tbody>
                    <tfoot><tr class="fw-bold"><td>Total</td><td><?php echo $totals['mini']; ?></td><td><?php echo $totals['small']; ?></td><td><?php echo $totals['medium']; ?></td><td><?php echo $totals['large']; ?></td><td><?php echo $totals['roti']; ?></td><td><?php echo $totals['friday']; ?></td><td><?php echo $totals['barnamaj']; ?></td><td><?php echo $totals['count']; ?></td><td>&#8377;<?php echo e(number_format($totals['amount'], 2)); ?></td></tr></tfoot>
                </table>
            </div>
        <?php } else { ?><div class="alert alert-info" role="alert">No Cloud 9 thali deliveries recorded for <?php echo e(date('F Y', strtotime($startDate))); ?>.</div><?php } ?>
    </div>
</div>

<?php include('footer.php'); ?>

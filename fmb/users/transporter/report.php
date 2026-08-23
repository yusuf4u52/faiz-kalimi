<?php
include('../header.php');
include('../navbar.php');
require_once('../helpers.php');

$currentMonth = date('Y-m');
$requestedMonth = (string) ($_GET['payment_month'] ?? $currentMonth);
$monthDate = DateTime::createFromFormat('!Y-m', $requestedMonth);
$isValidMonth = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requestedMonth) === 1
    && $monthDate !== false
    && $monthDate->format('Y-m') === $requestedMonth;
$paymentMonth = $isValidMonth ? $requestedMonth : $currentMonth;
$selectedTransporter = (string) ($_GET['transporter_id'] ?? '');
$selectedTransporterId = filter_var($selectedTransporter, FILTER_VALIDATE_INT);
$selectedTransporterId = $selectedTransporterId !== false && $selectedTransporterId > 0
    ? $selectedTransporterId
    : null;
$isCloud9Alias = $selectedTransporter === 'murtaza_cloud9';
$transporters = [];
$rows = [];
$reportError = false;
$hasCloud9Transporter = false;
$isDailyReport = false;

try {
    // This table has no active/status column, so every transporter row is active.
    $transporterResult = db_query(
        $link,
        "SELECT `id`, `Name`, `rate_per_thali`
         FROM `transporters`
         ORDER BY `Name` ASC"
    );
    while ($transporter = mysqli_fetch_assoc($transporterResult)) {
        $transporters[] = $transporter;
    }
    mysqli_free_result($transporterResult);

    $hasCloud9Transporter = (bool) array_filter($transporters, static fn(array $transporter): bool => strcasecmp((string) $transporter['Name'], 'Murtaza (Cloud 9)') === 0 || strcasecmp((string) $transporter['Name'], 'Murtaza(Cloud 9)') === 0);
    if (!$hasCloud9Transporter) {
        $transporters[] = ['id' => 'murtaza_cloud9', 'Name' => 'Murtaza (Cloud 9)', 'rate_per_thali' => 12];
    }
} catch (RuntimeException $e) {
    error_log('[users/transporter/report.php] ' . $e->getMessage());
    $reportError = true;
}
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">Transporter Payment Report for <?php echo e(date('F Y', strtotime($paymentMonth . '-01'))); ?></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if (!$isValidMonth) { ?>
                    <div class="alert alert-danger" role="alert">Please choose a valid month.</div>
                <?php } ?>
                <form id="rotipayment" class="form-horizontal" method="GET"
                    action="<?php echo e($_SERVER['PHP_SELF']); ?>" autocomplete="off">
                    <div class="mb-3 row">
                        <label for="transporter_id" class="col-md-2 col-form-label">Transporter</label>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <select class="form-select" name="transporter_id" id="transporter_id">
                                <option value="">All Transporters</option>
                                <?php foreach ($transporters as $transporter) { ?>
                                    <?php $transporterOptionId = (string) $transporter['id']; ?>
                                    <option value="<?php echo e($transporterOptionId); ?>"<?php echo $selectedTransporter === $transporterOptionId ? ' selected' : ''; ?>>
                                        <?php echo e((string) $transporter['Name']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <label for="payment_month" class="col-md-2 col-form-label">Payment Month</label>
                        <div class="col-md-3 mb-2 mb-md-0">
                            <input type="month" class="form-control" name="payment_month" id="payment_month" max="<?php echo e($currentMonth); ?>" value="<?php echo e($paymentMonth); ?>">
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-light w-100" type="submit" name="search">Filter</button>
                        </div>
                    </div>
                </form>
                <?php if (!$reportError && $isValidMonth) {
                    $startDate = $paymentMonth . '-01';
                    $nextMonth = date('Y-m-d', strtotime($startDate . ' +1 month'));
                                        $isDailyReport = $selectedTransporterId !== null || $isCloud9Alias;
                                        if ($isCloud9Alias) {
                                                $query = "SELECT dc.`date`, 'Murtaza (Cloud 9)' AS transporter_name, 12 AS rate_per_thali,
                                                                    SUM(dc.`mini`) AS total_mini, SUM(dc.`small`) AS total_small,
                                                                    SUM(dc.`medium`) AS total_medium, SUM(dc.`large`) AS total_large,
                                                                    SUM(dc.`friday`) AS total_friday, SUM(dc.`roti`) AS total_roti,
                                                                    SUM(dc.`barnamaj`) AS total_barnamaj, SUM(dc.`count`) AS total_count
                                                     FROM `transporter_daily_count` dc
                                                       WHERE LOWER(REPLACE(TRIM(dc.`name`), ' ', '')) = 'murtaza(cloud9)'
                                                         AND dc.`date` >= ? AND dc.`date` < ?
                                                     GROUP BY dc.`date`
                                                     ORDER BY dc.`date` ASC";
                                        } elseif ($isDailyReport) {
                                                $query = "SELECT dc.`date`, t.`Name` AS transporter_name,
                                  CASE WHEN t.`Name` = 'Murtaza (Cloud 9)' THEN 12 ELSE t.`rate_per_thali` END AS rate_per_thali,
                                  SUM(dc.`mini`) AS total_mini, SUM(dc.`small`) AS total_small,
                                  SUM(dc.`medium`) AS total_medium, SUM(dc.`large`) AS total_large,
                                  SUM(dc.`friday`) AS total_friday, SUM(dc.`roti`) AS total_roti,
                                  SUM(dc.`barnamaj`) AS total_barnamaj, SUM(dc.`count`) AS total_count
                           FROM `transporters` t
                                                     INNER JOIN `transporter_daily_count` dc
                                                         ON LOWER(REPLACE(TRIM(dc.`name`), ' ', '')) = LOWER(REPLACE(TRIM(t.`Name`), ' ', ''))
                           WHERE t.`id` = ? AND dc.`date` >= ? AND dc.`date` < ?
                           GROUP BY dc.`date`, t.`id`, t.`Name`, t.`rate_per_thali`
                                    ORDER BY dc.`date` ASC";
                          } else {
                                $query = "SELECT t.`Name` AS transporter_name,
                                     CASE WHEN t.`Name` = 'Murtaza (Cloud 9)' THEN 12 ELSE t.`rate_per_thali` END AS rate_per_thali,
                                     COALESCE(SUM(dc.`mini`), 0) AS total_mini,
                                     COALESCE(SUM(dc.`small`), 0) AS total_small,
                                     COALESCE(SUM(dc.`medium`), 0) AS total_medium,
                                     COALESCE(SUM(dc.`large`), 0) AS total_large,
                                     COALESCE(SUM(dc.`friday`), 0) AS total_friday,
                                     COALESCE(SUM(dc.`roti`), 0) AS total_roti,
                                     COALESCE(SUM(dc.`barnamaj`), 0) AS total_barnamaj,
                                     COALESCE(SUM(dc.`count`), 0) AS total_count
                              FROM `transporters` t
                              LEFT JOIN `transporter_daily_count` dc
                                ON LOWER(REPLACE(TRIM(dc.`name`), ' ', '')) = LOWER(REPLACE(TRIM(t.`Name`), ' ', ''))
                                                             AND dc.`date` >= ? AND dc.`date` < ?
                              GROUP BY t.`id`, t.`Name`, t.`rate_per_thali` ORDER BY t.`Name` ASC";
                    }
                    if ($isCloud9Alias) {
                        $types = 'ss';
                        $params = [$startDate, $nextMonth];
                    } elseif ($isDailyReport) {
                        $types = 'iss';
                        $params = [$selectedTransporterId, $startDate, $nextMonth];
                    } else {
                        $types = 'ss';
                        $params = [$startDate, $nextMonth];
                    }

                    try {
                        $result = db_query($link, $query, $types, $params);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $row['base_payment'] = (float) $row['total_count'] * (float) ($row['rate_per_thali'] ?? 0);
                            $row['net_payable'] = $row['base_payment'];
                            $rows[] = $row;
                        }
                        mysqli_free_result($result);

                        if ($selectedTransporterId === null && !$isCloud9Alias && !$hasCloud9Transporter) {
                            $cloud9Result = db_query(
                                $link,
                                "SELECT COALESCE(SUM(`mini`), 0) AS total_mini,
                                        COALESCE(SUM(`small`), 0) AS total_small,
                                        COALESCE(SUM(`medium`), 0) AS total_medium,
                                        COALESCE(SUM(`large`), 0) AS total_large,
                                        COALESCE(SUM(`friday`), 0) AS total_friday,
                                        COALESCE(SUM(`roti`), 0) AS total_roti,
                                        COALESCE(SUM(`barnamaj`), 0) AS total_barnamaj,
                                        COALESCE(SUM(`count`), 0) AS total_count
                                 FROM `transporter_daily_count`
                                 WHERE LOWER(REPLACE(TRIM(`name`), ' ', '')) = 'murtaza(cloud9)'
                                   AND `date` >= ? AND `date` < ?",
                                'ss',
                                [$startDate, $nextMonth]
                            );
                            $cloud9Row = mysqli_fetch_assoc($cloud9Result);
                            mysqli_free_result($cloud9Result);
                            $cloud9Row['transporter_name'] = 'Murtaza (Cloud 9)';
                            $cloud9Row['rate_per_thali'] = 12;
                            $cloud9Row['base_payment'] = (float) $cloud9Row['total_count'] * 12;
                            $cloud9Row['net_payable'] = $cloud9Row['base_payment'];
                            $rows[] = $cloud9Row;
                        }
                    } catch (RuntimeException $e) {
                        error_log('[users/transporter/report.php] ' . $e->getMessage());
                        $reportError = true;
                    }
                }
                if ($reportError) { ?>
                    <div class="alert alert-danger" role="alert">Sorry, something went wrong loading the report.</div>
                <?php } elseif ($isValidMonth && count($rows) > 0) {
                    $totals = ['mini' => 0, 'small' => 0, 'medium' => 0, 'large' => 0, 'friday' => 0, 'roti' => 0, 'barnamaj' => 0, 'count' => 0, 'base_payment' => 0.0, 'net_payable' => 0.0];
                    foreach ($rows as $row) {
                        foreach (['mini', 'small', 'medium', 'large', 'friday', 'roti', 'barnamaj', 'count'] as $key) {
                            $totals[$key] += (int) $row['total_' . $key];
                        }
                        $totals['base_payment'] += $row['base_payment'];
                        $totals['net_payable'] += $row['net_payable'];
                    } ?>
                        <div class="row mb-3">
                            <?php if ($isDailyReport) { ?>
                                <div class="col-12 col-md-4 mb-2">
                                    <div class="card bg-light">
                                        <div class="card-body py-3">
                                            <div class="text-muted small">Total Thaali</div>
                                            <div class="fs-4 fw-bold"><?php echo (int) $totals['count']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <div class="card bg-light">
                                        <div class="card-body py-3">
                                            <div class="text-muted small">Rate per Thaali</div>
                                            <div class="fs-4 fw-bold">&#8377;<?php echo e(number_format((float) ($rows[0]['rate_per_thali'] ?? 0), 2)); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <div class="card bg-light">
                                        <div class="card-body py-3">
                                            <div class="text-muted small">Net Payable</div>
                                            <div class="fs-4 fw-bold">&#8377;<?php echo e(number_format($totals['net_payable'], 2)); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="col-12 col-md-6 mb-2">
                                    <div class="card bg-light">
                                        <div class="card-body py-3">
                                            <div class="text-muted small">Total Thaali</div>
                                            <div class="fs-4 fw-bold"><?php echo (int) $totals['count']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 mb-2">
                                    <div class="card bg-light">
                                        <div class="card-body py-3">
                                            <div class="text-muted small">Total Net Payable</div>
                                            <div class="fs-4 fw-bold">&#8377;<?php echo e(number_format($totals['net_payable'], 2)); ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="table-responsive mb-3">
                            <table class="table table-striped table-hover display">
                                <thead>
                                    <tr>
                                        <?php if ($isDailyReport) { ?>
                                            <th>Date</th>
                                        <?php } else { ?>
                                            <th>Transporter</th>
                                            <th>Month/Year</th>
                                        <?php } ?>
                                        <th>Mini</th>
                                        <th>Small</th>
                                        <th>Medium</th>
                                        <th>Large</th>
                                        <th>Friday</th>
                                        <th>Roti</th>
                                        <th>Barnamaj</th>
                                        <th>Total Thaali</th>
                                        <th>Rate</th>
                                        <th>Net Payable</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row) { ?>
                                        <tr>
                                            <?php if ($isDailyReport) { ?>
                                                <td data-order="<?php echo e($row['date']); ?>"><?php echo e(date('d M Y (l)', strtotime($row['date']))); ?></td>
                                            <?php } else { ?>
                                                <td><?php echo e($row['transporter_name']); ?></td>
                                                <td><?php echo e(date('F Y', strtotime($paymentMonth . '-01'))); ?></td>
                                            <?php } ?>
                                            <td><?php echo (int) $row['total_mini']; ?></td>
                                            <td><?php echo (int) $row['total_small']; ?></td>
                                            <td><?php echo (int) $row['total_medium']; ?></td>
                                            <td><?php echo (int) $row['total_large']; ?></td>
                                            <td><?php echo (int) $row['total_friday']; ?></td>
                                            <td><?php echo (int) $row['total_roti']; ?></td>
                                            <td><?php echo (int) $row['total_barnamaj']; ?></td>
                                            <td><?php echo (int) $row['total_count']; ?></td>
                                            <td>&#8377;<?php echo e(number_format((float) $row['rate_per_thali'], 2)); ?></td>
                                            <td>&#8377;<?php echo e(number_format($row['net_payable'], 2)); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td>Total</td>
                                        <?php if (!$isDailyReport) { ?>
                                            <td><?php echo e(date('F Y', strtotime($paymentMonth . '-01'))); ?></td>
                                        <?php } ?>
                                        <td><?php echo $totals['mini']; ?></td>
                                        <td><?php echo $totals['small']; ?></td>
                                        <td><?php echo $totals['medium']; ?></td>
                                        <td><?php echo $totals['large']; ?></td>
                                        <td><?php echo $totals['friday']; ?></td>
                                        <td><?php echo $totals['roti']; ?></td>
                                        <td><?php echo $totals['barnamaj']; ?></td>
                                        <td><?php echo $totals['count']; ?></td>
                                        <td>-</td>
                                        <td>&#8377;<?php echo e(number_format($totals['net_payable'], 2)); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                <?php } elseif ($isValidMonth) { ?>
                        <div class="alert alert-danger" role="alert">
                            No transporter data found for the selected month.
                        </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

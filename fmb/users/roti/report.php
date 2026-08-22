<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');
include('../getHijriDate.php');

/**
 * Replicate FMB_1446H_Roti_Management.xlsx: for each maker, an opening
 * atto/oil/packets balance, then one block of columns per Mon-Fri week
 * (daily roti received + weekly totals), then a month-end summary with
 * a pending balance carried forward.
 *
 * Ratios (read directly out of the workbook's formulas):
 *   1 KG atto per 40 roti, 1 Ltr oil per 400 roti, 1 packet-unit per 1000 roti, ₹2 per roti.
 *
 * Note: the source spreadsheet's own "Pending Pkts" formula sums the
 * *oil*-required columns instead of the *packets*-required columns
 * (a copy-paste bug). This report uses the correct formula instead.
 */

$anchor = $_GET['month_date'] ?? date('Y-m-d');
if (!DateTime::createFromFormat('Y-m-d', $anchor)) {
    $anchor = date('Y-m-d');
}

$range = get_hijri_month_range($anchor);
$from = $range['start'];
$to = $range['end'];
$hijriLabel = getHijriDate($anchor);
$hijriYearMonth = substr($hijriLabel, 0, 7);

$weeks = get_weekday_blocks($from, $to);

$makersResult = db_query($link, "SELECT id, code, full_name, mobile_no FROM fmb_roti_maker ORDER BY `full_name` ASC");
$makers = mysqli_fetch_all($makersResult, MYSQLI_ASSOC);
mysqli_free_result($makersResult);

// Pull all roti-received figures for the period in one query, indexed by [maker_id][date].
$recievedByMakerDate = [];
$recievedResult = db_query(
    $link,
    "SELECT maker_id, recieved_date, SUM(roti_recieved) AS roti
     FROM fmb_roti_recieved
     WHERE recieved_date BETWEEN ? AND ? AND roti_status = 'recieved'
     GROUP BY maker_id, recieved_date",
    "ss",
    [$from, $to]
);
while ($row = mysqli_fetch_assoc($recievedResult)) {
    $recievedByMakerDate[(int) $row['maker_id']][$row['recieved_date']] = (int) $row['roti'];
}
mysqli_free_result($recievedResult);

// Pull all distribution figures for the period in one query, indexed by [maker_id][date].
$givenByMakerDate = [];
$givenResult = db_query(
    $link,
    "SELECT maker_id, distribution_date,
            SUM(flour_distributed) AS atto_given,
            SUM(oil_distributed) AS oil_given,
            SUM(packets_distributed) AS pkts_given
     FROM fmb_roti_distribution
     WHERE distribution_date BETWEEN ? AND ?
     GROUP BY maker_id, distribution_date",
    "ss",
    [$from, $to]
);
while ($row = mysqli_fetch_assoc($givenResult)) {
    $givenByMakerDate[(int) $row['maker_id']][$row['distribution_date']] = [
        'atto' => (float) $row['atto_given'],
        'oil' => (float) $row['oil_given'],
        'pkts' => (float) ($row['pkts_given'] ?? 0),
    ];
}
mysqli_free_result($givenResult);

/**
 * The outstanding atto/oil/packets balance for every maker as of (just
 * before) $periodStart, using the same running-ledger logic as
 * savedistribute.php: take each maker's most recent distribution record
 * before the period, then subtract whatever was required for roti received
 * between that record's date and the period start.
 *
 * Uses a window function (ROW_NUMBER() OVER PARTITION BY, MySQL 8.0+) to
 * find each maker's latest prior record in one pass, instead of the
 * previous approach of two queries per maker in a PHP loop — this used to
 * be 2×N queries for N makers and is now a single query for all of them.
 *
 * @return array<int, array{atto: float, oil: float, pkts: float}> keyed by maker_id
 */
function get_opening_balances(mysqli $link, string $periodStart): array
{
    $result = db_query(
        $link,
        "WITH latest_prior AS (
            SELECT maker_id, distribution_date, flour_distributed, flour_left,
                   oil_distributed, oil_left, packets_distributed, packets_left,
                   ROW_NUMBER() OVER (PARTITION BY maker_id ORDER BY distribution_date DESC) AS rn
            FROM fmb_roti_distribution
            WHERE distribution_date < ?
         )
         SELECT
            lp.maker_id,
            lp.flour_distributed, lp.flour_left,
            lp.oil_distributed, lp.oil_left,
            lp.packets_distributed, lp.packets_left,
            COALESCE(SUM(r.roti_recieved), 0) AS interim_roti
         FROM latest_prior lp
         LEFT JOIN fmb_roti_recieved r
            ON r.maker_id = lp.maker_id
            AND r.recieved_date BETWEEN lp.distribution_date AND ?
            AND r.roti_status = 'recieved'
         WHERE lp.rn = 1
         GROUP BY lp.maker_id, lp.flour_distributed, lp.flour_left,
                  lp.oil_distributed, lp.oil_left, lp.packets_distributed, lp.packets_left",
        "ss",
        [$periodStart, $periodStart]
    );

    $balances = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $totalRoti = (float) $row['interim_roti'];
        $baseAtto = (float) $row['flour_distributed'] + (float) $row['flour_left'];
        $baseOil = (float) $row['oil_distributed'] + (float) $row['oil_left'];
        $basePkts = (float) ($row['packets_distributed'] ?? 0) + (float) ($row['packets_left'] ?? 0);

        $balances[(int) $row['maker_id']] = [
            'atto' => $baseAtto - $totalRoti * ATTO_PER_ROTI,
            'oil' => $baseOil - $totalRoti * OIL_PER_ROTI,
            'pkts' => $basePkts - $totalRoti * PKTS_PER_ROTI,
        ];
    }
    mysqli_free_result($result);

    return $balances;
}

$openingBalances = get_opening_balances($link, $from);

// Build one row of computed data per maker.
$reportRows = [];
foreach ($makers as $maker) {
    $makerId = (int) $maker['id'];
    $opening = $openingBalances[$makerId] ?? ['atto' => 0.0, 'oil' => 0.0, 'pkts' => 0.0];

    $weekBlocks = [];
    $totalRotiMade = 0;
    $totalAttoGiven = 0.0;
    $totalOilGiven = 0.0;
    $totalPktsGiven = 0.0;
    $totalAttoRequired = 0.0;
    $totalOilRequired = 0.0;
    $totalPktsRequired = 0.0;

    foreach ($weeks as $weekDates) {
        $days = [];
        $weekRoti = 0;
        $weekAttoGiven = 0.0;
        $weekOilGiven = 0.0;
        $weekPktsGiven = 0.0;

        foreach ($weekDates as $date) {
            $roti = $recievedByMakerDate[$makerId][$date] ?? 0;
            $days[] = $roti;
            $weekRoti += $roti;

            $given = $givenByMakerDate[$makerId][$date] ?? null;
            if ($given !== null) {
                $weekAttoGiven += $given['atto'];
                $weekOilGiven += $given['oil'];
                $weekPktsGiven += $given['pkts'];
            }
        }

        $weekAttoRequired = $weekRoti * ATTO_PER_ROTI;
        $weekOilRequired = $weekRoti * OIL_PER_ROTI;
        $weekPktsRequired = $weekRoti * PKTS_PER_ROTI;

        $weekBlocks[] = [
            'dates' => $weekDates,
            'days' => $days,
            'total_roti' => $weekRoti,
            'total_amt' => $weekRoti * AMOUNT_PER_ROTI,
            'atto_required' => $weekAttoRequired,
            'atto_given' => $weekAttoGiven,
            'oil_required' => $weekOilRequired,
            'oil_given' => $weekOilGiven,
            'pkts_required' => $weekPktsRequired,
            'pkts_given' => $weekPktsGiven,
        ];

        $totalRotiMade += $weekRoti;
        $totalAttoGiven += $weekAttoGiven;
        $totalOilGiven += $weekOilGiven;
        $totalPktsGiven += $weekPktsGiven;
        $totalAttoRequired += $weekAttoRequired;
        $totalOilRequired += $weekOilRequired;
        $totalPktsRequired += $weekPktsRequired;
    }

    $reportRows[] = [
        'maker' => $maker,
        'opening' => $opening,
        'weeks' => $weekBlocks,
        'total_roti_made' => $totalRotiMade,
        'total_amt' => $totalRotiMade * AMOUNT_PER_ROTI,
        'total_atto_given' => $totalAttoGiven,
        'total_oil_given' => $totalOilGiven,
        'total_pkts_given' => $totalPktsGiven,
        'pending_atto' => ($opening['atto'] + $totalAttoGiven) - $totalAttoRequired,
        'pending_oil' => ($opening['oil'] + $totalOilGiven) - $totalOilRequired,
        'pending_pkts' => ($opening['pkts'] + $totalPktsGiven) - $totalPktsRequired,
    ];
}

$summaryColCount = 8; // Total Roti, Total Amt, Atto Req, Atto Given, Oil Req, Oil Given, Pkts Req, Pkts Given
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-12">
                <h2 class="mb-3">Roti Matrix Report</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <form id="rotimatrix" class="form-horizontal my-3" method="GET"
                    action="<?php echo e($_SERVER['PHP_SELF']); ?>" autocomplete="off">
                    <div class="mb-3 row">
                        <label for="month_date" class="col-3 control-label">Any date in the Hijri month</label>
                        <div class="col-4">
                            <input type="date" class="form-control" name="month_date" id="month_date"
                                value="<?php echo e($anchor); ?>">
                        </div>
                        <div class="col-3">
                            <button class="btn btn-light" type="submit" name="search">Generate</button>
                        </div>
                    </div>
                </form>
                <p class="text-muted">
                    Hijri month <strong><?php echo e($hijriYearMonth); ?></strong>
                    &mdash; Gregorian range <strong><?php echo e(date('d M Y', strtotime($from))); ?></strong> to
                    <strong><?php echo e(date('d M Y', strtotime($to))); ?></strong>
                    (<?php echo count($weeks); ?> week<?php echo count($weeks) === 1 ? '' : 's'; ?>)
                </p>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th rowspan="2">Sr. No.</th>
                                <th rowspan="2">Name</th>
                                <th rowspan="2">Code</th>
                                <th rowspan="2">Mob No.</th>
                                <th rowspan="2">Opening<br>Atto</th>
                                <th rowspan="2">Opening<br>Oil</th>
                                <th rowspan="2">Opening<br>Pkts</th>
                                <?php foreach ($weeks as $i => $weekDates) { ?>
                                    <th colspan="<?php echo count($weekDates) + $summaryColCount; ?>" class="text-center">
                                        Week <?php echo $i + 1; ?>:
                                        <?php echo e(date('d M', strtotime($weekDates[0]))); ?> -
                                        <?php echo e(date('d M', strtotime(end($weekDates)))); ?>
                                    </th>
                                <?php } ?>
                                <th colspan="8" class="text-center">Month Summary</th>
                            </tr>
                            <tr>
                                <?php foreach ($weeks as $weekDates) {
                                    foreach ($weekDates as $date) {
                                        echo '<th>' . e(date('D d', strtotime($date))) . '</th>';
                                    }
                                    echo '<th>Total Roti</th><th>Total Amt</th>';
                                    echo '<th>Atto Req (KG)</th><th>Atto Given</th>';
                                    echo '<th>Oil Req (L)</th><th>Oil Given</th>';
                                    echo '<th>Pkts Req</th><th>Pkts Given</th>';
                                } ?>
                                <th>Total Roti Made</th>
                                <th>Total Amt (₹2/Roti)</th>
                                <th>Total Atto Given</th>
                                <th>Total Oil Given</th>
                                <th>Total Pkts Given</th>
                                <th>Pending Atto</th>
                                <th>Pending Oil</th>
                                <th>Pending Pkts</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportRows as $i => $r) {
                                $maker = $r['maker']; ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo e($maker['full_name']); ?></td>
                                    <td><?php echo e($maker['code']); ?></td>
                                    <td><?php echo e($maker['mobile_no']); ?></td>
                                    <td><?php echo round($r['opening']['atto'], 3); ?></td>
                                    <td><?php echo round($r['opening']['oil'], 3); ?></td>
                                    <td><?php echo round($r['opening']['pkts'], 3); ?></td>
                                    <?php foreach ($r['weeks'] as $week) {
                                        foreach ($week['days'] as $roti) {
                                            echo '<td>' . (int) $roti . '</td>';
                                        }
                                        echo '<td>' . (int) $week['total_roti'] . '</td>';
                                        echo '<td>' . (int) $week['total_amt'] . '</td>';
                                        echo '<td>' . round($week['atto_required'], 3) . '</td>';
                                        echo '<td>' . round($week['atto_given'], 3) . '</td>';
                                        echo '<td>' . round($week['oil_required'], 3) . '</td>';
                                        echo '<td>' . round($week['oil_given'], 3) . '</td>';
                                        echo '<td>' . round($week['pkts_required'], 3) . '</td>';
                                        echo '<td>' . round($week['pkts_given'], 3) . '</td>';
                                    } ?>
                                    <td><strong><?php echo (int) $r['total_roti_made']; ?></strong></td>
                                    <td><strong><?php echo (int) $r['total_amt']; ?></strong></td>
                                    <td><?php echo round($r['total_atto_given'], 3); ?></td>
                                    <td><?php echo round($r['total_oil_given'], 3); ?></td>
                                    <td><?php echo round($r['total_pkts_given'], 3); ?></td>
                                    <td class="<?php echo $r['pending_atto'] > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo round($r['pending_atto'], 3); ?></td>
                                    <td class="<?php echo $r['pending_oil'] > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo round($r['pending_oil'], 3); ?></td>
                                    <td class="<?php echo $r['pending_pkts'] > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo round($r['pending_pkts'], 3); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted small">
                    Positive "Pending" figures mean more atto/oil/packets are owed to the maker than they've been given
                    for the roti required this month; negative means they're currently holding a surplus.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

<?php
/**
 * Roti-module-specific helpers. Generic helpers (e, db_query,
 * current_user_email, build_in_clause, ...) live in the single shared
 * users/helpers.php — this file just adds this module's own conversion
 * ratios and the weekly-sheet / month-payment calculation & persistence
 * functions on top of it.
 *
 * Model: one calendar week (Monday-Sunday) is the unit of data entry.
 * fmb_roti_distribution has (at most) one row per maker per week, keyed by
 * that week's Monday as `distribution_date`. Its `flour_left` / `oil_left`
 * columns hold that week's *Opening* Atta/Oil balance; `flour_distributed` /
 * `oil_distributed` hold that week's *Given* Atta/Oil. fmb_roti_recieved is
 * unchanged — one row per maker per day.
 */
require_once __DIR__ . '/../helpers.php';

/**
 * Emails allowed to use the bulk XLSX import and manually override
 * flour/oil "left" balances. Currently unused now that Opening is directly
 * editable by anyone with access to the sheet, but kept in case you want to
 * gate something (e.g. editing already-closed past weeks) later.
 */
const ROTI_PRIVILEGED_EMAILS = [
    'tinwalaabizer@gmail.com',
    'moizlife@gmail.com',
    'hussainbarnagarwala14@gmail.com',
    'gheewalamf@gmail.com',
];

/** Is the currently logged-in user allowed to do privileged roti-module actions? */
function is_roti_privileged_user(): bool
{
    $email = $_SESSION['email'] ?? null;
    return $email !== null && in_array($email, ROTI_PRIVILEGED_EMAILS, true);
}

// --- Conversion ratios & rate, taken from the FMB_1446H_Roti_Management.xlsx formulas ---
const ATTO_PER_ROTI = 1 / 40;  // 40 roti = 1 KG atta
const OIL_PER_ROTI = 1 / 400;  // 400 roti = 1 Ltr oil
const AMOUNT_PER_ROTI = 5;     // ₹5 per roti

/**
 * Determine the Gregorian start/end dates of the Hijri month containing
 * $anchorDate, by walking outward day-by-day with getHijriDate() until the
 * Hijri year-month prefix changes. Cheap since Hijri months are ~29-30 days.
 *
 * Requires getHijriDate.php (users/getHijriDate.php) to already be included.
 * Used only by the Month-End Payment Report (roti/payment.php) — the weekly
 * sheet no longer needs to know about Hijri month boundaries at all.
 *
 * @return array{start: string, end: string}
 */
function get_hijri_month_range(string $anchorDate): array
{
    $anchorHijri = substr(getHijriDate($anchorDate), 0, 7); // 'YYYY-MM'

    $start = new DateTime($anchorDate);
    while (substr(getHijriDate($start->format('Y-m-d')), 0, 7) === $anchorHijri) {
        $start->modify('-1 day');
    }
    $start->modify('+1 day');

    $end = new DateTime($anchorDate);
    while (substr(getHijriDate($end->format('Y-m-d')), 0, 7) === $anchorHijri) {
        $end->modify('+1 day');
    }
    $end->modify('-1 day');

    return ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')];
}

/** The Monday (ISO weekday 1) of the calendar week containing $date. */
function week_start_monday(string $date): string
{
    $dt = new DateTime($date);
    $isoDow = (int) $dt->format('N'); // 1 (Mon) .. 7 (Sun)
    if ($isoDow > 1) {
        $dt->modify('-' . ($isoDow - 1) . ' days');
    }
    return $dt->format('Y-m-d');
}

/** The 7 dates (Monday..Sunday) of the week starting $weekStart. */
function week_dates(string $weekStart): array
{
    $dates = [];
    $dt = new DateTime($weekStart);
    for ($i = 0; $i < 7; $i++) {
        $dates[] = $dt->format('Y-m-d');
        $dt->modify('+1 day');
    }
    return $dates;
}

/**
 * The Opening Atta/Oil balance to show for $makerId's week starting
 * $weekStart:
 *  - If a distribution row already exists for exactly this week, its own
 *    `flour_left` / `oil_left` columns are the opening balance calculated
 *    when the week was last saved.
 *  - Otherwise (the first time this week is opened), suggest a sensible
 *    default by carrying forward from the most recent earlier week: that
 *    week's (Opening + Given) minus whatever the roti actually received
 *    between that week and this one required. This is only ever a
 *    *starting value* shown in the input — nothing is written to the
 *    database until the admin actually saves this week.
 *
 * @return array{atta: float, oil: float}
 */
function get_week_opening(mysqli $link, int $makerId, string $weekStart): array
{
    $existing = db_query(
        $link,
        "SELECT `flour_left`, `oil_left` FROM fmb_roti_distribution WHERE `maker_id` = ? AND `distribution_date` = ? LIMIT 1",
        "is",
        [$makerId, $weekStart]
    );
    if ($existing->num_rows > 0) {
        $row = mysqli_fetch_assoc($existing);
        return ['atta' => (float) $row['flour_left'], 'oil' => (float) $row['oil_left']];
    }

    $prior = db_query(
        $link,
        "SELECT `distribution_date`, `flour_distributed`, `flour_left`, `oil_distributed`, `oil_left`
         FROM fmb_roti_distribution WHERE `maker_id` = ? AND `distribution_date` < ? ORDER BY `distribution_date` DESC LIMIT 1",
        "is",
        [$makerId, $weekStart]
    );
    if ($prior->num_rows === 0) {
        return ['atta' => 0.0, 'oil' => 0.0];
    }
    $priorRow = mysqli_fetch_assoc($prior);

    $interim = db_query(
        $link,
        "SELECT COALESCE(SUM(roti_recieved), 0) AS total_roti FROM fmb_roti_recieved
         WHERE `maker_id` = ? AND `recieved_date` >= ? AND `recieved_date` < ? AND `roti_status` = 'recieved'",
        "iss",
        [$makerId, $priorRow['distribution_date'], $weekStart]
    );
    $interimRow = mysqli_fetch_assoc($interim);
    $totalRoti = (float) $interimRow['total_roti'];

    return [
        'atta' => ((float) $priorRow['flour_distributed'] + (float) $priorRow['flour_left']) - $totalRoti * ATTO_PER_ROTI,
        'oil' => ((float) $priorRow['oil_distributed'] + (float) $priorRow['oil_left']) - $totalRoti * OIL_PER_ROTI,
    ];
}

/**
 * Save this week's Opening + Given Atta/Oil for one maker, exactly as
 * shown (and possibly edited) in the sheet. No carry-forward math happens
 * here — that only ever happens when *suggesting* a default value via
 * get_week_opening(). Once the admin has looked at a number (whether it's
 * the suggested default or their own correction) and it gets saved, it's
 * taken as-is.
 */
function upsert_week_distribution(
    mysqli $link,
    int $makerId,
    string $weekStart,
    float $openingAtta,
    float $givenAtta,
    float $openingOil,
    float $givenOil,
    string $by
): void {
    db_query(
        $link,
        "INSERT INTO fmb_roti_distribution
            (`maker_id`, `distribution_date`, `flour_distributed`, `flour_left`, `oil_distributed`, `oil_left`, `packets_distributed`, `packets_left`, `distributed_by`)
         VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?)
         ON DUPLICATE KEY UPDATE
            `flour_distributed` = VALUES(`flour_distributed`),
            `flour_left` = VALUES(`flour_left`),
            `oil_distributed` = VALUES(`oil_distributed`),
            `oil_left` = VALUES(`oil_left`),
            `distributed_by` = VALUES(`distributed_by`)",
        "isdddds",
        [$makerId, $weekStart, $givenAtta, $openingAtta, $givenOil, $openingOil, $by]
    );
}

/**
 * Recalculate saved opening balances after a historical edit. Each later
 * distribution row starts with the previous row's closing balance, less the
 * roti received between the two distribution dates.
 */
function propagate_week_openings(mysqli $link, int $makerId, string $fromWeekStart): void
{
    $priorResult = db_query(
        $link,
        "SELECT `distribution_date`, `flour_distributed`, `oil_distributed`, `flour_left`, `oil_left`
         FROM fmb_roti_distribution
         WHERE `maker_id` = ? AND `distribution_date` < ? ORDER BY `distribution_date` DESC LIMIT 1",
        "is",
        [$makerId, $fromWeekStart]
    );
    $priorRows = mysqli_fetch_all($priorResult, MYSQLI_ASSOC);
    mysqli_free_result($priorResult);

    $rowsResult = db_query(
        $link,
        "SELECT `distribution_date`, `flour_distributed`, `oil_distributed`, `flour_left`, `oil_left`
         FROM fmb_roti_distribution
         WHERE `maker_id` = ? AND `distribution_date` >= ? ORDER BY `distribution_date` ASC",
        "is",
        [$makerId, $fromWeekStart]
    );
    $distributionRows = mysqli_fetch_all($rowsResult, MYSQLI_ASSOC);
    mysqli_free_result($rowsResult);
    if (!empty($priorRows)) {
        array_unshift($distributionRows, $priorRows[0]);
    }

    if (count($distributionRows) < 2) {
        return;
    }

    for ($index = 1, $count = count($distributionRows); $index < $count; $index++) {
        $prior = $distributionRows[$index - 1];
        $current = $distributionRows[$index];
        if ($current['distribution_date'] === $fromWeekStart) {
            continue;
        }
        $interim = db_query(
            $link,
            "SELECT COALESCE(SUM(`roti_recieved`), 0) AS total_roti
             FROM fmb_roti_recieved
             WHERE `maker_id` = ? AND `recieved_date` >= ? AND `recieved_date` < ? AND `roti_status` = 'recieved'",
            "iss",
            [$makerId, $prior['distribution_date'], $current['distribution_date']]
        );
        $interimRow = mysqli_fetch_assoc($interim);
        mysqli_free_result($interim);
        $totalRoti = (float) ($interimRow['total_roti'] ?? 0);
        $openingAtta = ((float) $prior['flour_left'] + (float) $prior['flour_distributed']) - $totalRoti * ATTO_PER_ROTI;
        $openingOil = ((float) $prior['oil_left'] + (float) $prior['oil_distributed']) - $totalRoti * OIL_PER_ROTI;

        db_query(
            $link,
            "UPDATE fmb_roti_distribution SET `flour_left` = ?, `oil_left` = ? WHERE `maker_id` = ? AND `distribution_date` = ?",
            "ddis",
            [$openingAtta, $openingOil, $makerId, $current['distribution_date']]
        );
        $distributionRows[$index]['flour_left'] = $openingAtta;
        $distributionRows[$index]['oil_left'] = $openingOil;
    }
}

/**
 * Upsert one maker/date roti-received row.
 *
 * @return array{was_update: bool}
 */
function upsert_roti_received(mysqli $link, int $makerId, string $recievedDate, int $rotiRecieved, string $rotiStatus, string $recievedBy): array
{
    db_query(
        $link,
        "INSERT INTO fmb_roti_recieved (`maker_id`, `recieved_date`, `roti_recieved`, `roti_status`, `recieved_by`) VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            `roti_recieved` = VALUES(`roti_recieved`),
            `roti_status` = VALUES(`roti_status`),
            `recieved_by` = VALUES(`recieved_by`)",
        "isiss",
        [$makerId, $recievedDate, $rotiRecieved, $rotiStatus, $recievedBy]
    );

    return ['was_update' => mysqli_affected_rows($link) > 1];
}

/**
 * Build the full weekly sheet data for every Roti Maker for the week
 * starting $weekStart (a Monday): opening/given Atta & Oil, each day's
 * roti received, and every computed figure (Total Roti, Total Amt, Atta/Oil
 * Required, and the Closing balance — which doubles as the suggested
 * Opening balance for *next* week).
 */
function build_week_matrix(mysqli $link, string $weekStart): array
{
    $dates = week_dates($weekStart);
    $weekEnd = end($dates);

    $makersResult = db_query($link, "SELECT `id`, `code`, `full_name`, `mobile_no` FROM fmb_roti_maker ORDER BY `full_name` ASC");
    $makers = mysqli_fetch_all($makersResult, MYSQLI_ASSOC);
    mysqli_free_result($makersResult);

    $receivedByMakerDate = [];
    $recResult = db_query(
        $link,
        "SELECT `maker_id`, `recieved_date`, SUM(`roti_recieved`) AS roti FROM fmb_roti_recieved
         WHERE `recieved_date` BETWEEN ? AND ? AND `roti_status` = 'recieved' GROUP BY `maker_id`, `recieved_date`",
        "ss",
        [$weekStart, $weekEnd]
    );
    while ($row = mysqli_fetch_assoc($recResult)) {
        $receivedByMakerDate[(int) $row['maker_id']][$row['recieved_date']] = (int) $row['roti'];
    }
    mysqli_free_result($recResult);

    $givenByMaker = [];
    $givenResult = db_query(
        $link,
        "SELECT `maker_id`, `flour_distributed`, `oil_distributed` FROM fmb_roti_distribution WHERE `distribution_date` = ?",
        "s",
        [$weekStart]
    );
    while ($row = mysqli_fetch_assoc($givenResult)) {
        $givenByMaker[(int) $row['maker_id']] = ['atta' => (float) $row['flour_distributed'], 'oil' => (float) $row['oil_distributed']];
    }
    mysqli_free_result($givenResult);

    $rows = [];
    foreach ($makers as $maker) {
        $makerId = (int) $maker['id'];
        $opening = get_week_opening($link, $makerId, $weekStart);
        $given = $givenByMaker[$makerId] ?? ['atta' => 0.0, 'oil' => 0.0];

        $daily = [];
        $totalRoti = 0;
        foreach ($dates as $date) {
            $roti = $receivedByMakerDate[$makerId][$date] ?? 0;
            $daily[] = $roti;
            $totalRoti += $roti;
        }

        $attaRequired = $totalRoti * ATTO_PER_ROTI;
        $oilRequired = $totalRoti * OIL_PER_ROTI;

        $rows[] = [
            'maker_id' => $makerId,
            'code' => $maker['code'],
            'full_name' => $maker['full_name'],
            'mobile_no' => $maker['mobile_no'],
            'opening_atta' => $opening['atta'],
            'opening_oil' => $opening['oil'],
            'given_atta' => $given['atta'],
            'given_oil' => $given['oil'],
            'daily' => $daily,
            'total_roti' => $totalRoti,
            'total_amt' => $totalRoti * AMOUNT_PER_ROTI,
            'atta_required' => $attaRequired,
            'oil_required' => $oilRequired,
            'closing_atta' => $opening['atta'] + $given['atta'] - $attaRequired,
            'closing_oil' => $opening['oil'] + $given['oil'] - $oilRequired,
        ];
    }

    return [
        'week_start' => $weekStart,
        'week_end' => $weekEnd,
        'dates' => $dates,
        'hijri_label' => getHijriDate($weekStart) . ' – ' . getHijriDate($weekEnd),
        'hijri_month_year' => preg_replace('/^\d+\s+/', '', getHijriFullDate($weekStart)),
        'amount_per_roti' => AMOUNT_PER_ROTI,
        'rows' => $rows,
    ];
}

/**
 * Build the Month-End Payment Report: total roti received per maker across
 * the Gregorian month containing $anchorDate, the resulting payout at ₹5/roti,
 * and each maker's contact + bank details for making the payment. This is
 * read-only and doesn't touch fmb_roti_distribution at all — only the
 * roti-received figures matter for payment.
 */
function build_month_payment(mysqli $link, string $anchorDate): array
{
    $monthStart = new DateTime($anchorDate);
    $monthStart->modify('first day of this month');
    $from = $monthStart->format('Y-m-d');
    $to = $monthStart->format('Y-m-t');

    $makersResult = db_query($link, "SELECT `id`, `code`, `full_name`, `mobile_no`, `bank_details` FROM fmb_roti_maker ORDER BY `full_name` ASC");
    $makers = mysqli_fetch_all($makersResult, MYSQLI_ASSOC);
    mysqli_free_result($makersResult);

    $rotiByMaker = [];
    $result = db_query(
        $link,
        "SELECT `maker_id`, COALESCE(SUM(`roti_recieved`), 0) AS total_roti FROM fmb_roti_recieved
         WHERE `recieved_date` BETWEEN ? AND ? AND `roti_status` = 'recieved' GROUP BY `maker_id`",
        "ss",
        [$from, $to]
    );
    while ($row = mysqli_fetch_assoc($result)) {
        $rotiByMaker[(int) $row['maker_id']] = (int) $row['total_roti'];
    }
    mysqli_free_result($result);

    $rows = [];
    foreach ($makers as $maker) {
        $totalRoti = $rotiByMaker[(int) $maker['id']] ?? 0;
        $rows[] = [
            'maker_id' => (int) $maker['id'],
            'code' => $maker['code'],
            'full_name' => $maker['full_name'],
            'mobile_no' => $maker['mobile_no'],
            'bank_details' => $maker['bank_details'],
            'total_roti' => $totalRoti,
            'total_payout' => $totalRoti * AMOUNT_PER_ROTI,
        ];
    }

    return [
        'from' => $from,
        'to' => $to,
        'month_label' => $monthStart->format('F Y'),
        'amount_per_roti' => AMOUNT_PER_ROTI,
        'rows' => $rows,
    ];
}

/** Build the payment report for one Monday-Sunday calendar week. */
function build_week_payment(mysqli $link, string $weekDate): array
{
    $from = week_start_monday($weekDate);
    $to = (new DateTime($from))->modify('+6 days')->format('Y-m-d');

    $makersResult = db_query($link, "SELECT `id`, `code`, `full_name`, `mobile_no`, `bank_details` FROM fmb_roti_maker ORDER BY `full_name` ASC");
    $makers = mysqli_fetch_all($makersResult, MYSQLI_ASSOC);
    mysqli_free_result($makersResult);

    $rotiByMaker = [];
    $result = db_query(
        $link,
        "SELECT `maker_id`, COALESCE(SUM(`roti_recieved`), 0) AS total_roti FROM `fmb_roti_recieved`
         WHERE `recieved_date` BETWEEN ? AND ? AND `roti_status` = 'recieved' GROUP BY `maker_id`",
        "ss",
        [$from, $to]
    );
    while ($row = mysqli_fetch_assoc($result)) {
        $rotiByMaker[(int) $row['maker_id']] = (int) $row['total_roti'];
    }
    mysqli_free_result($result);

    $rows = [];
    foreach ($makers as $maker) {
        $totalRoti = $rotiByMaker[(int) $maker['id']] ?? 0;
        $rows[] = [
            'maker_id' => (int) $maker['id'],
            'code' => $maker['code'],
            'full_name' => $maker['full_name'],
            'mobile_no' => $maker['mobile_no'],
            'bank_details' => $maker['bank_details'],
            'total_roti' => $totalRoti,
            'total_payout' => $totalRoti * AMOUNT_PER_ROTI,
        ];
    }

    return [
        'from' => $from,
        'to' => $to,
        'hijri_year_month' => getHijriDate($from),
        'amount_per_roti' => AMOUNT_PER_ROTI,
        'rows' => $rows,
    ];
}

/** Build day-by-day payment rows for one selected maker and week. */
function build_maker_daily_payment(mysqli $link, int $makerId, string $weekDate): array
{
    $from = week_start_monday($weekDate);
    $dates = week_dates($from);
    $to = end($dates);
    $result = db_query(
        $link,
        "SELECT `recieved_date`, COALESCE(SUM(`roti_recieved`), 0) AS total_roti
         FROM `fmb_roti_recieved`
         WHERE `maker_id` = ? AND `recieved_date` BETWEEN ? AND ? AND `roti_status` = 'recieved'
         GROUP BY `recieved_date` ORDER BY `recieved_date` ASC",
        "iss",
        [$makerId, $from, $to]
    );
    $rotiByDate = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rotiByDate[$row['recieved_date']] = (int) $row['total_roti'];
    }
    mysqli_free_result($result);

    $rows = [];
    foreach ($dates as $date) {
        $totalRoti = $rotiByDate[$date] ?? 0;
        $rows[] = ['date' => $date, 'total_roti' => $totalRoti, 'total_payout' => $totalRoti * AMOUNT_PER_ROTI];
    }
    return ['from' => $from, 'to' => $to, 'amount_per_roti' => AMOUNT_PER_ROTI, 'rows' => $rows];
}

/** Build week-by-week payment rows for one selected maker and Gregorian month. */
function build_maker_weekly_payment(mysqli $link, int $makerId, string $monthDate): array
{
    $monthStart = new DateTime($monthDate);
    $monthStart->modify('first day of this month');
    $from = $monthStart->format('Y-m-d');
    $to = $monthStart->format('Y-m-t');
    $result = db_query(
        $link,
        "SELECT `recieved_date`, COALESCE(SUM(`roti_recieved`), 0) AS total_roti
         FROM `fmb_roti_recieved`
         WHERE `maker_id` = ? AND `recieved_date` BETWEEN ? AND ? AND `roti_status` = 'recieved'
         GROUP BY `recieved_date` ORDER BY `recieved_date` ASC",
        "iss",
        [$makerId, $from, $to]
    );
    $rotiByWeek = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $weekStart = week_start_monday($row['recieved_date']);
        $rotiByWeek[$weekStart] = ($rotiByWeek[$weekStart] ?? 0) + (int) $row['total_roti'];
    }
    mysqli_free_result($result);

    $rows = [];
    $weekStart = week_start_monday($from);
    while ($weekStart <= $to) {
        $weekEnd = (new DateTime($weekStart))->modify('+6 days')->format('Y-m-d');
        $totalRoti = $rotiByWeek[$weekStart] ?? 0;
        $rows[] = ['week_start' => max($weekStart, $from), 'week_end' => min($weekEnd, $to), 'total_roti' => $totalRoti, 'total_payout' => $totalRoti * AMOUNT_PER_ROTI];
        $weekStart = (new DateTime($weekStart))->modify('+7 days')->format('Y-m-d');
    }
    return ['from' => $from, 'to' => $to, 'amount_per_roti' => AMOUNT_PER_ROTI, 'rows' => $rows];
}

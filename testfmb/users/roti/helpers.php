<?php
/**
 * Roti-module-specific helpers. Generic helpers (e, db_query,
 * current_user_email, build_in_clause, ...) now live in the single
 * shared users/helpers.php — this file just adds this module's own
 * conversion ratios and date-block logic on top of it.
 */
require_once __DIR__ . '/../helpers.php';

/**
 * Emails allowed to use the bulk XLSX import and manually override
 * flour/oil/packet "left" balances.
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

// --- Conversion ratios, taken from the FMB_1446H_Roti_Management.xlsx formulas ---
const ATTO_PER_ROTI = 1 / 40;      // 40 roti = 1 KG atto
const OIL_PER_ROTI = 1 / 400;      // 400 roti = 1 Ltr oil
const PKTS_PER_ROTI = 1 / 1000;    // 1000 roti = 1 packet-unit (4 roti/packet, 250 packets/unit)
const AMOUNT_PER_ROTI = 5;         // ₹5 per roti (kept as customized)

/**
 * Split an inclusive date range into blocks of up to 5 weekdays (Mon-Fri),
 * skipping weekends entirely — matching the weekly column blocks in the
 * source spreadsheet (e.g. 7-11 Safar, then 14-18 Safar, ...).
 *
 * @return array<int, array<int, string>> list of blocks, each a list of 'Y-m-d' dates
 */
function get_weekday_blocks(string $from, string $to): array
{
    $weekdays = [];
    $cursor = new DateTime($from);
    $end = new DateTime($to);

    while ($cursor <= $end) {
        $isoDow = (int) $cursor->format('N'); // 1 (Mon) .. 7 (Sun)
        if ($isoDow <= 5) {
            $weekdays[] = $cursor->format('Y-m-d');
        }
        $cursor->modify('+1 day');
    }

    return array_chunk($weekdays, 5);
}

/**
 * Determine the Gregorian start/end dates of the Hijri month containing
 * $anchorDate, by walking outward day-by-day with getHijriDate() until the
 * Hijri year-month prefix changes. Cheap since Hijri months are ~29-30 days.
 *
 * Requires getHijriDate.php (users/getHijriDate.php) to already be included.
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

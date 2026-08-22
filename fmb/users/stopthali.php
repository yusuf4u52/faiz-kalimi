<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');

function getAllDates(string $startingDate, string $endingDate): array
{
    $datesArray = [];
    $currentDate = strtotime($startingDate);
    $endingTs = strtotime($endingDate);

    for (; $currentDate <= $endingTs; $currentDate += 86400) {
        $datesArray[] = date('Y-m-d', $currentDate);
    }

    return $datesArray;
}

function parsePostedDate(mixed $value): ?string
{
    $value = trim((string) $value);
    foreach (['Y-m-d', 'm/d/Y', 'd/m/Y'] as $format) {
        $date = DateTime::createFromFormat('!' . $format, $value);
        $errors = DateTime::getLastErrors();
        if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
            return $date->format('Y-m-d');
        }
    }

    return null;
}

/**
 * Restore the Roti override for restarted dates when a thali has extra Roti.
 * Existing user_menu rows are custom choices and must not be changed.
 */
function restoreExtraRotiMenus(mysqli $link, string $thali, string $startDate, string $endDate): void
{
    $thaliResult = db_query($link, "SELECT `thalisize`, `extraRoti` FROM thalilist WHERE `id` = ? LIMIT 1", "i", [(int) $thali]);
    $thaliData = $thaliResult->fetch_assoc();
    if (!$thaliData || (int) $thaliData['extraRoti'] === 0) {
        return;
    }

    $existingDates = [];
    $existingMenus = db_query(
        $link,
        "SELECT `menu_date` FROM user_menu WHERE `thali` = ? AND `menu_date` BETWEEN ? AND ?",
        "sss",
        [$thali, $startDate, $endDate]
    );
    while ($existingMenu = mysqli_fetch_assoc($existingMenus)) {
        $existingDates[$existingMenu['menu_date']] = true;
    }

    $rotiSizeField = match (strtolower((string) $thaliData['thalisize'])) {
        'mini' => 'tqty',
        'medium' => 'mqty',
        'large' => 'lqty',
        default => 'sqty',
    };
    $menus = db_query(
        $link,
        "SELECT `menu_date`, `menu_item` FROM menu_list WHERE `menu_type` = 'thaali' AND `menu_date` BETWEEN ? AND ?",
        "ss",
        [$startDate, $endDate]
    );

    while ($menu = mysqli_fetch_assoc($menus)) {
        if (isset($existingDates[$menu['menu_date']])) {
            continue;
        }

        $menuItem = decode_menu_item($menu['menu_item']);
        if (strcasecmp(trim((string) ($menuItem['roti']['item'] ?? '')), 'Roti') !== 0) {
            continue;
        }

        $menuItem['roti']['qty'] = (int) ($menuItem['roti']['qty'] ?? $menuItem['roti'][$rotiSizeField] ?? 0)
            + (int) $thaliData['extraRoti'];

        db_query(
            $link,
            "INSERT INTO user_menu (`thali`, `menu_date`, `menu_item`) VALUES (?, ?, ?)",
            "sss",
            [$thali, $menu['menu_date'], encode_menu_item($menuItem)]
        );
    }
}

$action = $_POST['action'] ?? null;
$thali = $_POST['thali'] ?? null;
$startDate = parsePostedDate($_POST['start_date'] ?? null);
$endDate = parsePostedDate($_POST['end_date'] ?? null);

$selfServiceActions = ['stop_thali', 'start_thali', 'stop_date_thali'];
$adminActions = ['admin_stop_thali', 'admin_start_thali'];

if (!$action || !$thali || !$startDate || !$endDate) {
    header("Location: /fmb/users/index.php");
    exit;
}

// Self-service actions may only ever act on the caller's own thali — the
// `thali` field is a hidden form input, which is trivial to tamper with
// client-side. Without this check, any logged-in member could stop or
// start someone else's thali just by editing that field.
if (in_array($action, $selfServiceActions, true) && (string) $thali !== (string) ($_SESSION['thaliid'] ?? '')) {
    header("Location: /fmb/users/index.php");
    exit;
}

date_default_timezone_set('Asia/Kolkata');

if (in_array($action, ['stop_thali', 'stop_date_thali', 'start_thali'], true)) {
    $GivenDate = new DateTime($startDate . ' 17:00:00');
    $GivenDate->modify('-1 day');
    $GivenDate = $GivenDate->format('Y-m-d H:i:s');
    $CurrentDate = date('Y-m-d H:i:s');
}

if (in_array($action, ['admin_stop_thali', 'admin_start_thali'], true)) {
    $GivenDate = (new DateTime($startDate . ' 00:00:00'))->format('Y-m-d H:i:s');
    $CurrentDate = date('Y-m-d H:i:s');
}

if (in_array($action, ['stop_thali', 'stop_date_thali', 'admin_stop_thali'], true)) {
    if ($CurrentDate < $GivenDate) {
        $dates = getAllDates($startDate, $endDate);

        // Single multi-row upsert instead of one SELECT+INSERT/UPDATE per
        // date. Relies on the UNIQUE KEY (thali, stop_date) added in
        // schema-modernization.sql.
        $rowPlaceholders = implode(',', array_fill(0, count($dates), '(?, ?)'));
        $types = str_repeat('ss', count($dates));
        $params = [];
        foreach ($dates as $date) {
            $params[] = $thali;
            $params[] = $date;
        }
        db_query(
            $link,
            "INSERT INTO stop_thali (`thali`, `stop_date`) VALUES $rowPlaceholders
             ON DUPLICATE KEY UPDATE `stop_date` = VALUES(`stop_date`)",
            $types,
            $params
        );

        $resultAction = 'srange';
    } else {
        $resultAction = 'srsvp';
    }

    $sdate = $startDate;
    $edate = $endDate;

    if ($action === 'stop_thali') {
        header("Location: /fmb/users/index.php?action=" . urlencode($resultAction) . "&sdate=" . urlencode($sdate) . "&edate=" . urlencode($edate));
        exit;
    }
    if ($action === 'admin_stop_thali') {
        header("Location: /fmb/users/thalisearch.php?thalino=" . urlencode($_POST['thalino'] ?? '')
            . "&tiffinno=" . urlencode($_POST['tiffinno'] ?? '')
            . "&general=" . urlencode($_POST['general'] ?? '')
            . "&year=" . urlencode($_POST['year'] ?? '')
            . "&action=" . urlencode($resultAction) . "&sdate=" . urlencode($sdate) . "&edate=" . urlencode($edate));
        exit;
    }
    if ($action === 'stop_date_thali') {
        header("Location: /fmb/users/stop_dates.php?action=" . urlencode($resultAction) . "&sdate=" . urlencode($sdate) . "&edate=" . urlencode($edate));
        exit;
    }
}

if (in_array($action, ['start_thali', 'admin_start_thali'], true)) {
    if ($CurrentDate < $GivenDate) {
        db_query(
            $link,
            "DELETE FROM stop_thali WHERE `thali` = ? AND `stop_date` BETWEEN ? AND ?",
            "sss",
            [$thali, $startDate, $endDate]
        );
        restoreExtraRotiMenus($link, $thali, $startDate, $endDate);
        $resultAction = 'start';
    } else {
        $resultAction = 'srsvp';
    }

    $sdate = $startDate;
    $edate = $endDate;

    if ($action === 'start_thali') {
        header("Location: /fmb/users/stop_dates.php?action=" . urlencode($resultAction) . "&sdate=" . urlencode($sdate) . "&edate=" . urlencode($edate));
        exit;
    }
    if ($action === 'admin_start_thali') {
        header("Location: /fmb/users/thalisearch.php?thalino=" . urlencode($_POST['thalino'] ?? '')
            . "&tiffinno=" . urlencode($_POST['tiffinno'] ?? '')
            . "&general=" . urlencode($_POST['general'] ?? '')
            . "&year=" . urlencode($_POST['year'] ?? '')
            . "&action=start&sdate=" . urlencode($sdate) . "&edate=" . urlencode($edate));
        exit;
    }
}

header("Location: /fmb/users/index.php");
exit;

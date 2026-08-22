<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');

if (!isset($_POST['menu_id'], $_POST['thali']) || !ctype_digit((string) $_POST['menu_id'])) {
    header("Location: /fmb/users/index.php");
    exit;
}

$action = $_POST['action'] ?? null;
$thali = (string) $_POST['thali'];

// Self-service actions may only ever act on the caller's own thali.
if (in_array($action, ['change_menu', 'feedback_menu'], true) && $thali !== (string) ($_SESSION['thaliid'] ?? '')) {
    header("Location: /fmb/users/index.php");
    exit;
}

// The admin action edits an arbitrary thali by design, so it needs its own
// privilege check rather than relying on ownership.
if ($action === 'admin_change_menu' && !user_email_in(THALISEARCH_ACCESS_EMAILS)) {
    header("Location: /fmb/users/index.php");
    exit;
}

$menu_list = db_query($link, "SELECT `menu_date` FROM menu_list WHERE `id` = ?", "i", [(int) $_POST['menu_id']]);
if ($menu_list->num_rows === 0) {
    header("Location: /fmb/users/index.php");
    exit;
}
$menu_date = $menu_list->fetch_assoc()['menu_date'];

date_default_timezone_set('Asia/Kolkata');

if ($action === 'change_menu') {
    $GivenDate = new DateTime($menu_date . ' 17:00:00');
    $GivenDate->modify('-1 day');
    $GivenDate = $GivenDate->format('Y-m-d H:i:s');
    $CurrentDate = date('Y-m-d H:i:s');
}

if ($action === 'admin_change_menu') {
    $GivenDate = (new DateTime($menu_date . ' 23:00:00'))->format('Y-m-d H:i:s');
    $CurrentDate = date('Y-m-d H:i:s');
}

if (in_array($action, ['change_menu', 'admin_change_menu'], true) && !empty($CurrentDate) && !empty($GivenDate) && $CurrentDate < $GivenDate) {
    if (isset($_POST['status'])) {
        $menu_item = [];
        $change = 'no';
        $sstop = 'no';
        $tstop = 'no';
        $rstop = 'no';
        $rotiStop = 'no';

        $menu_item_result = db_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = ?", "s", [$menu_date]);
        if ($menu_item_result->num_rows > 0) {
            $menu_item = decode_menu_item($menu_item_result->fetch_assoc()['menu_item']);

            $existingUserMenuResult = db_query($link, "SELECT `menu_item` FROM user_menu WHERE `menu_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
            $existingUserMenu = $existingUserMenuResult->fetch_assoc() ?: null;
            $currentMenuItem = $existingUserMenu !== null ? decode_menu_item($existingUserMenu['menu_item']) : $menu_item;

            $thaliResult = db_query($link, "SELECT `thalisize`, `extraRoti` FROM thalilist WHERE `id` = ? LIMIT 1", "i", [(int) $thali]);
            $thaliData = $thaliResult->fetch_assoc() ?: [];
            $rotiSizeField = match (strtolower((string) ($thaliData['thalisize'] ?? ''))) {
                'mini' => 'tqty',
                'medium' => 'mqty',
                'large' => 'lqty',
                default => 'sqty',
            };
            $rotiQuantity = (int) ($menu_item['roti']['qty'] ?? $menu_item['roti'][$rotiSizeField] ?? 0);
            $rotiMaxQuantity = (int) ($menu_item['roti'][$rotiSizeField] ?? 0);
            if (strcasecmp(trim((string) ($menu_item['roti']['item'] ?? '')), 'Roti') === 0) {
                $rotiMaxQuantity += (int) ($thaliData['extraRoti'] ?? 0);
            }
            $rotiMaxQuantity = max(0, $rotiMaxQuantity);
            $rotiQuantity = min(max(0, $rotiQuantity), $rotiMaxQuantity);

            foreach (['sabji' => &$sstop, 'tarkari' => &$tstop, 'rice' => &$rstop, 'roti' => &$rotiStop] as $course => &$stopFlag) {
                if (!empty($menu_item[$course]['item'])) {
                    $postedQty = (int) ($_POST['menu_item'][$course]['qty'] ?? 0);
                    if ($course === 'roti') {
                        $postedQty = min(max(0, $postedQty), $rotiMaxQuantity);
                        $_POST['menu_item']['roti']['qty'] = $postedQty;
                    }
                    if ($postedQty === 0) {
                        $stopFlag = 'yes';
                        $change = 'yes';
                    } elseif ((int) ($currentMenuItem[$course]['qty'] ?? ($course === 'roti' ? $rotiQuantity : $menu_item[$course]['qty'] ?? 0)) !== $postedQty) {
                        // BUG FIX: this used to compare with strict !== against
                        // a raw (string) POST value, so an int(2) from the DB
                        // vs a string "2" from the form were never equal —
                        // $change ended up 'yes' on almost every submission
                        // regardless of whether the quantity actually changed.
                        $change = 'yes';
                    }
                } else {
                    $stopFlag = 'yes';
                }
            }
            unset($stopFlag);
        }

        $stop_thali = db_query($link, "SELECT id FROM stop_thali WHERE `stop_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
        $msg = null;
        if ($stop_thali->num_rows > 0) {
            db_query($link, "DELETE FROM stop_thali WHERE `stop_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
            $msg = 'start';
        }

        if ($sstop === 'yes' && $tstop === 'yes' && $rstop === 'yes') {
            $stop_thali = db_query($link, "SELECT id FROM stop_thali WHERE `stop_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
            if ($stop_thali->num_rows > 0) {
                db_query($link, "DELETE FROM user_menu WHERE `menu_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
                $resultAction = 'astop';
            } else {
                db_query(
                    $link,
                    "INSERT INTO `stop_thali` (`thali`, `stop_date`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `stop_date` = VALUES(`stop_date`)",
                    "ss",
                    [$thali, $menu_date]
                );
                db_query($link, "DELETE FROM user_menu WHERE `menu_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
                $resultAction = 'stop';
            }
        } elseif ($change === 'yes') {
            db_query(
                $link,
                "INSERT INTO `user_menu` (`thali`, `menu_date`, `menu_item`) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE `menu_item` = VALUES(`menu_item`)",
                "sss",
                [$thali, $menu_date, encode_menu_item($_POST['menu_item'] ?? [])]
            );
            $resultAction = ($msg === 'start') ? 'sedit' : 'edit';
        } elseif ($msg === 'start') {
            if ($existingUserMenu === null && !empty($menu_item['roti']['item'])
                && strcasecmp(trim((string) $menu_item['roti']['item']), 'Roti') === 0
                && (int) ($thaliData['extraRoti'] ?? 0) !== 0) {
                $restartMenuItem = $menu_item;
                $restartMenuItem['roti']['qty'] = $rotiQuantity;

                db_query(
                    $link,
                    "INSERT INTO `user_menu` (`thali`, `menu_date`, `menu_item`) VALUES (?, ?, ?)",
                    "sss",
                    [$thali, $menu_date, encode_menu_item($restartMenuItem)]
                );
            }

            $resultAction = 'snochange';
        } else {
            db_query($link, "DELETE FROM user_menu WHERE `menu_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
            $resultAction = ($msg === 'start') ? 'snochange' : 'nochange';
        }
    } else {
        $stop_thali = db_query($link, "SELECT id FROM stop_thali WHERE `stop_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
        if ($stop_thali->num_rows > 0) {
            db_query($link, "DELETE FROM user_menu WHERE `menu_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
            $resultAction = 'astop';
        } else {
            db_query(
                $link,
                "INSERT INTO `stop_thali` (`thali`, `stop_date`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `stop_date` = VALUES(`stop_date`)",
                "ss",
                [$thali, $menu_date]
            );
            db_query($link, "DELETE FROM user_menu WHERE `menu_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
            $resultAction = 'stop';
        }
    }
} else {
    $resultAction = 'rsvp';
}

if ($action === 'change_menu') {
    header("Location: /fmb/users/index.php?action=" . urlencode($resultAction) . "&date=" . urlencode($menu_date));
    exit;
}

if ($action === 'admin_change_menu') {
    header("Location: /fmb/users/thalisearch.php?thalino=" . urlencode($_POST['thalino'] ?? '')
        . "&tiffinno=" . urlencode($_POST['tiffinno'] ?? '')
        . "&general=" . urlencode($_POST['general'] ?? '')
        . "&year=" . urlencode($_POST['year'] ?? '')
        . "&action=" . urlencode($resultAction) . "&date=" . urlencode($menu_date));
    exit;
}

if ($action === 'feedback_menu') {
    $user_feedmenu = db_query($link, "SELECT id FROM user_feedmenu WHERE `menu_date` = ? AND `thali` = ?", "ss", [$menu_date, $thali]);
    $feedbackAction = $user_feedmenu->num_rows > 0 ? 'editfeed' : 'addfeed';

    db_query(
        $link,
        "INSERT INTO `user_feedmenu` (`thali`, `menu_date`, `menu_feed`, `feedback`) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE `menu_feed` = VALUES(`menu_feed`), `feedback` = VALUES(`feedback`)",
        "ssss",
        [$thali, $menu_date, encode_menu_item($_POST['menu_item'] ?? []), (string) ($_POST['feedback'] ?? '')]
    );

    header("Location: /fmb/users/index.php?action=" . urlencode($feedbackAction) . "&date=" . urlencode($menu_date));
    exit;
}

header("Location: /fmb/users/index.php");
exit;

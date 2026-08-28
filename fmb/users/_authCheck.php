<?php
require_once('connection.php');
require_once('helpers.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['fromLogin'])) {
    header("Location: /fmb/index.php");
    exit;
}

// check if user has right to access the page
$rights = [
    "musaid" => [
        "/fmb/users/musaid.php",
        "/fmb/users/_stop_thali_admin.php",
    ],
    "admin" => [
        "/fmb/users/musaid.php",
        "/fmb/users/admin_scripts.php",
        "/fmb/users/stop_permanant.php",
        "/fmb/users/thalisearch.php",
        "/fmb/users/special/friday.php",
        "/fmb/users/special/barnamaj.php",
        "/fmb/users/special/stopall.php",
        "/fmb/users/menu/food.php",
        "/fmb/users/menu/savefood.php",
        "/fmb/users/menu/list.php",
        "/fmb/users/menu/savelist.php",
        "/fmb/users/menu/edited.php",
        "/fmb/users/menu/feedback.php",
        "/fmb/users/roti/maker.php",
        "/fmb/users/roti/savemaker.php",
        "/fmb/users/roti/sheet.php",
        "/fmb/users/roti/payment.php",
        "/fmb/users/roti/api/sheet_save.php",
        "/fmb/users/roti/api/sheet_week.php",
        "/fmb/users/transporter/list.php",
        "/fmb/users/transporter/savelist.php",
        "/fmb/users/transporter/activethali.php",
        "/fmb/users/transporter/inactivethali.php",
        "/fmb/users/transporter/hardstopthali.php",
        "/fmb/users/transporter/report.php",
        "/fmb/users/pendingactions.php",
        "/fmb/users/_stop_thali_admin.php",
        "/fmb/users/uploadoutstanding.php",
        "/fmb/users/uploadreciept.php",
        "/fmb/users/uploadsector.php",
        "/fmb/users/uploadmembers.php",
        "/fmb/sms/index.php",
        // The following were reachable by anyone before this file's missing
        // `exit;` bug was fixed (see the big comment below) — they were
        // never actually enforced. Added here now that enforcement works,
        // based on where each one is linked from (all are admin-only
        // actions reached from pendingactions.php or an admin-gated link
        // in events.php).
        "/fmb/users/activatethali.php",
        "/fmb/users/reject.php",
        "/fmb/users/savetransporter.php",
        "/fmb/users/event_get_not_registered_users.php",
        "/fmb/users/pendinghoob.php",
        "/fmb/users/integrity_check.php",
    ],
    "staff" => [
        "/fmb/users/thalisearch.php",
        "/fmb/users/_payhoob.php",
        "/fmb/users/rotipayment.php",
    ],
    "all" => [
        "/fmb/users/index.php",
        "/fmb/users/stopthali.php",
        "/fmb/users/stop_dates.php",
        "/fmb/users/viewmenu.php",
        "/fmb/users/changemenu.php",
        "/fmb/users/hoobHistory.php",
        "/fmb/users/events.php",
        "/fmb/users/hub_details.php",
        "/fmb/users/thali_details.php",
        "/fmb/users/update_details.php",
        "/fmb/users/selectyearlyhub.php",
        "/fmb/users/selectyearlyhub_action.php",
        // Self-service action, no evidence it's admin-restricted — see the
        // ownership check added inside changethalisize.php itself, which
        // matters more than this list for actually preventing misuse.
        "/fmb/users/changethalisize.php",
    ],
];

// fetch user role
$sql = db_query($link, "SELECT `role` FROM users WHERE `email` = ?", "s", [$_SESSION['email'] ?? '']);

$request_path = strtok($_SERVER["REQUEST_URI"], '?');

if ($row = mysqli_fetch_assoc($sql)) {
    $_SESSION['role'] = $row['role'];

    if ($row['role'] !== 'superadmin') {
        // CRITICAL FIX: the `exit;` after these redirects was commented out
        // in the original file. header("Location: ...") only sends an HTTP
        // header — it does NOT stop PHP from continuing to execute and
        // output the rest of the page. Any client that doesn't
        // automatically follow redirects (curl, a script, some in-app
        // browsers) — or any case where headers were already sent and the
        // Location header silently failed — would see the full page
        // content despite not being authorized to view it. This was a
        // real authorization bypass across every page that includes
        // _authCheck.php.
        $allowedPaths = $rights[$row['role']] ?? [];
        if (!in_array($request_path, $allowedPaths, true) && !in_array($request_path, $rights['all'], true)) {
            header("Location: /fmb/users/index.php");
            exit;
        }
    }
} elseif (!in_array($request_path, $rights['all'], true)) {
    header("Location: /fmb/users/index.php");
    exit;
}

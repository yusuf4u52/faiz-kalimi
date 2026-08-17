<?php
require_once('connection.php');
require_once('helpers.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['fromLogin'])) {
    header("Location: /testfmb/index.php");
    exit;
}

// check if user has right to access the page
$rights = [
    "musaid" => [
        "/testfmb/users/musaid.php",
        "/testfmb/users/_stop_thali_admin.php",
    ],
    "admin" => [
        "/testfmb/users/musaid.php",
        "/testfmb/users/admin_scripts.php",
        "/testfmb/users/stop_permanant.php",
        "/testfmb/users/thalisearch.php",
        "/testfmb/users/special/friday.php",
        "/testfmb/users/special/barnamaj.php",
        "/testfmb/users/special/stopall.php",
        "/testfmb/users/menu/food.php",
        "/testfmb/users/menu/savefood.php",
        "/testfmb/users/menu/list.php",
        "/testfmb/users/menu/savelist.php",
        "/testfmb/users/menu/edited.php",
        "/testfmb/users/menu/feedback.php",
        "/testfmb/users/roti/maker.php",
        "/testfmb/users/roti/savemaker.php",
        "/testfmb/users/roti/distribute.php",
        "/testfmb/users/roti/savedistribute.php",
        "/testfmb/users/roti/recieved.php",
        "/testfmb/users/roti/saverecieved.php",
        "/testfmb/users/roti/report.php",
        "/testfmb/users/roti/recieveimport.php",
        "/testfmb/users/roti/distributeimport.php",
        "/testfmb/users/transporter/list.php",
        "/testfmb/users/transporter/savelist.php",
        "/testfmb/users/transporter/activethali.php",
        "/testfmb/users/transporter/inactivethali.php",
        "/testfmb/users/transporter/hardstopthali.php",
        "/testfmb/users/transporter/thalicount.php",
        "/testfmb/users/transporter/report.php",
        "/testfmb/users/pendingactions.php",
        "/testfmb/users/_stop_thali_admin.php",
        "/testfmb/users/uploadoutstanding.php",
        "/testfmb/users/uploadreciept.php",
        "/testfmb/users/uploadsector.php",
        "/testfmb/users/uploadmembers.php",
        "/testfmb/sms/index.php",
        // The following were reachable by anyone before this file's missing
        // `exit;` bug was fixed (see the big comment below) — they were
        // never actually enforced. Added here now that enforcement works,
        // based on where each one is linked from (all are admin-only
        // actions reached from pendingactions.php or an admin-gated link
        // in events.php).
        "/testfmb/users/activatethali.php",
        "/testfmb/users/reject.php",
        "/testfmb/users/savetransporter.php",
        "/testfmb/users/event_get_not_registered_users.php",
        "/testfmb/users/pendinghoob.php",
        "/testfmb/users/integrity_check.php",
    ],
    "staff" => [
        "/testfmb/users/thalisearch.php",
        "/testfmb/users/_payhoob.php",
        "/testfmb/users/rotipayment.php",
    ],
    "all" => [
        "/testfmb/users/index.php",
        "/testfmb/users/stopthali.php",
        "/testfmb/users/stop_dates.php",
        "/testfmb/users/viewmenu.php",
        "/testfmb/users/changemenu.php",
        "/testfmb/users/hoobHistory.php",
        "/testfmb/users/events.php",
        "/testfmb/users/hub_details.php",
        "/testfmb/users/thali_details.php",
        "/testfmb/users/update_details.php",
        "/testfmb/users/selectyearlyhub.php",
        "/testfmb/users/selectyearlyhub_action.php",
        // Self-service action, no evidence it's admin-restricted — see the
        // ownership check added inside changethalisize.php itself, which
        // matters more than this list for actually preventing misuse.
        "/testfmb/users/changethalisize.php",
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
            header("Location: /testfmb/users/index.php");
            exit;
        }
    }
} elseif (!in_array($request_path, $rights['all'], true)) {
    header("Location: /testfmb/users/index.php");
    exit;
}

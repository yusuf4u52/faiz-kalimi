<?php
include('connection.php');
require_once('helpers.php');
include('getHijriDate.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * SECURITY FIX: this used to do
 *   if ($_POST['fromLogin']) {
 *       $_SESSION['fromLogin'] = $_POST['fromLogin'];
 *       $_SESSION['thaliid']   = $_POST['thaliid'];
 *       $_SESSION['thali']     = $_POST['thali'];
 *   }
 * i.e. it let the POST body itself *set* the session's identity, rather
 * than requiring the person already be logged in. Anyone could POST any
 * thaliid here and stop that thali, with no real authentication at all.
 * This page now only acts on the session already established by the
 * normal Google login flow (login.php -> navbar.php) — it never trusts
 * identity fields from the request body.
 */
if (empty($_SESSION['fromLogin']) || empty($_SESSION['thaliid'])) {
    header("Location: /testfmb/index.php");
    exit;
}

// check if request is in cut off time
date_default_timezone_set('Asia/Kolkata');
$cutoffTime = '20:00'; // Cut off at 8 pm
$startTime = '23:59'; // reset back to open at midnight

$time1 = (new DateTime($cutoffTime))->format('H:i');
$time2 = (new DateTime($startTime))->format('H:i');
$current = date("H:i");

if ($current > $time1 && $current < $time2) {
    header("Location: index.php?status=" . urlencode('Stop thali not allowed post 8 PM.'));
    exit;
}

$today = getTodayDateHijri();
$thaliId = $_SESSION['thaliid'];
$thaliNo = $_SESSION['thali'] ?? '';

db_query($link, "UPDATE thalilist SET Active = 0, Thali_stop_date = ? WHERE id = ?", "ss", [$today, $thaliId]);
db_query(
    $link,
    "UPDATE change_table SET processed = 1 WHERE userid = ? AND `Operation` IN ('Start Thali','Stop Thali','Start Transport','Stop Transport') AND processed = 0",
    "s",
    [$thaliId]
);
db_query(
    $link,
    "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES (?, ?, 'Stop Thali', ?)",
    "sss",
    [$thaliNo, $thaliId, $today]
);

header("Location: index.php?status=" . urlencode('Stop Thali Successful'));
exit;

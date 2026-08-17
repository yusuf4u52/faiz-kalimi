<?php
include('connection.php');
require_once('helpers.php');
include('getHijriDate.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SECURITY FIX: see stop_thali.php — this no longer trusts POST fields to
// establish session identity, only an already-authenticated session.
if (empty($_SESSION['fromLogin']) || empty($_SESSION['thaliid'])) {
    header("Location: /testfmb/index.php");
    exit;
}

date_default_timezone_set('Asia/Kolkata');
$cutoffTime = '20:00'; // Cut off at 8 pm
$startTime = '23:59'; // reset back to open at midnight

$time1 = (new DateTime($cutoffTime))->format('H:i');
$time2 = (new DateTime($startTime))->format('H:i');
$current = date("H:i");

if ($current > $time1 && $current < $time2) {
    header("Location: index.php?status=" . urlencode('Start thali not allowed post 8 PM.'));
    exit;
}

$today = getTodayDateHijri();
$thaliId = $_SESSION['thaliid'];

$result = db_query($link, "SELECT id, Thali, hardstop FROM thalilist WHERE id = ?", "s", [$thaliId]);
$values = mysqli_fetch_assoc($result);

if (!$values) {
    header("Location: index.php?status=" . urlencode('Thali not found.'));
    exit;
}

if ($values['hardstop'] == 1) {
    header("Location: index.php?status=" . urlencode('Your thali is currently on hard stop and cannot be started from here. Please contact us.'));
    exit;
}

db_query($link, "UPDATE thalilist SET Active = 1, Thali_start_date = ? WHERE id = ?", "ss", [$today, $values['id']]);
db_query(
    $link,
    "UPDATE change_table SET processed = 1 WHERE userid = ?
     AND (`Operation` IN ('Start Thali','Stop Thali','Update Address','Change Size')
          OR `Operation` LIKE 'Update Address from %'
          OR `Operation` LIKE 'Change Size from %')
     AND processed = 0",
    "s",
    [$thaliId]
);
db_query(
    $link,
    "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES (?, ?, 'Start Thali', ?)",
    "sss",
    [$values['Thali'], $thaliId, $today]
);

header("Location: index.php?status=" . urlencode('Start Thali Successful'));
exit;

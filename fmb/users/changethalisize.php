<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');
include('getHijriDate.php');

$id = $_POST['id'] ?? null;
$thalisize = $_POST['thalisize'] ?? null;
$thaliNo = $_POST['Thali'] ?? null;

// This is reachable by every logged-in member (self-service), so make sure
// they can only change their own thali's size, not an arbitrary id passed
// in the form.
if ($id === null || $thalisize === null || (string) $id !== (string) ($_SESSION['thaliid'] ?? '')) {
    header("Location: /fmb/users/index.php");
    exit;
}

$today = getTodayDateHijri();

$currentSizeResult = db_query($link, "SELECT `thalisize` FROM thalilist WHERE `id` = ? LIMIT 1", "i", [(int) $id]);
$currentSize = $currentSizeResult->fetch_assoc();
if (!$currentSize) {
    header("Location: /fmb/users/index.php");
    exit;
}
$sizeOperation = 'Change Size from ' . ($currentSize['thalisize'] ?: 'Unassigned');

// mark all previous change sizes as processed before making a new entry
db_query(
    $link,
    "UPDATE change_table SET processed = 1
     WHERE userid = ? AND (`Operation` = 'Change Size' OR `Operation` LIKE 'Change Size from %') AND processed = 0",
    "s",
    [$id]
);
db_query($link, "UPDATE thalilist SET thalisize = ? WHERE id = ?", "ss", [$thalisize, $id]);
db_query(
    $link,
    "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`, `processed`) VALUES (?, ?, ?, ?, 0)",
    "ssss",
    [$thaliNo, $id, $sizeOperation, $today]
);

if (isset($_SERVER["HTTP_REFERER"])) {
    header("Location: " . $_SERVER["HTTP_REFERER"]);
    exit;
}

header("Location: /fmb/users/index.php");
exit;

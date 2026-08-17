<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');
include('getHijriDate.php');

$today = getTodayDateHijri();

$result = db_query($link, "SELECT id FROM thalilist WHERE thali = ?", "s", [$_POST['thaali_id'] ?? '']);
$name = mysqli_fetch_assoc($result);

if (!$name) {
    echo "Thali not found.";
    exit;
}

$active = (int) ($_POST['active'] ?? 0);

if (($_POST['hardstop'] ?? null) == 1) {
    db_query(
        $link,
        "UPDATE thalilist SET Active = ?, hardstop = ?, hardstop_comment = ? WHERE id = ?",
        "iisi",
        [$active, (int) $_POST['hardstop'], (string) ($_POST['hardstopcomment'] ?? ''), $name['id']]
    );
} else {
    db_query($link, "UPDATE thalilist SET Active = ? WHERE id = ?", "ii", [$active, $name['id']]);
}

db_query(
    $link,
    "UPDATE change_table SET processed = 1 WHERE userid = ? AND `Operation` IN ('Start Thali','Stop Thali','Start Transport','Stop Transport') AND processed = 0",
    "s",
    [$name['id']]
);

if ($active === 0) {
    db_query($link, "UPDATE thalilist SET Thali_stop_date = ? WHERE id = ?", "si", [$today, $name['id']]);
    db_query(
        $link,
        "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES (?, ?, 'Stop Thali', ?)",
        "sis",
        [$_POST['thaali_id'] ?? '', $name['id'], $today]
    );
} else {
    db_query($link, "UPDATE thalilist SET Thali_start_date = ?, hardstop = 0, hardstop_comment = '' WHERE id = ?", "si", [$today, $name['id']]);
    db_query(
        $link,
        "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES (?, ?, 'Start Thali', ?)",
        "sis",
        [$_POST['thaali_id'] ?? '', $name['id'], $today]
    );
}

echo "success";

<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');
include('getHijriDate.php');

$thali = $_POST['id'] ?? null;
$clearhub = true;

if ($thali !== null && ($_POST['action'] ?? null) === 'stop_permanant') {
    $today = getTodayDateHijri();

    $result = db_query(
        $link,
        "SELECT id, (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending FROM thalilist WHERE id = ?",
        "s",
        [$thali]
    );
    $name = mysqli_fetch_assoc($result);

    if ($name) {
        db_query(
            $link,
            "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES (?, ?, 'Stop Permanent', ?)",
            "sss",
            [$thali, $name['id'], $today]
        );
        db_query($link, "UPDATE thalilist SET Active = 0, hardstop = 1 WHERE id = ?", "s", [$name['id']]);

        if ($clearhub) {
            db_query(
                $link,
                "UPDATE thalilist SET yearly_hub = yearly_hub - ? WHERE id = ?",
                "ds",
                [(float) $name['Total_Pending'], $name['id']]
            );
        }

        db_query(
            $link,
            "UPDATE change_table SET processed = 1 WHERE userid = ? AND `Operation` IN ('New Thali') AND processed = 0",
            "s",
            [$name['id']]
        );
    }

    header("Location: /testfmb/users/thalisearch.php?thalino=" . urlencode($_POST['thalino'] ?? '')
        . "&tiffinno=" . urlencode($_POST['tiffinno'] ?? '')
        . "&general=" . urlencode($_POST['general'] ?? '')
        . "&year=" . urlencode($_POST['year'] ?? '')
        . "&action=spermanant");
    exit;
}

<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');

// Was extract($_POST) — dumps every POST key straight into local scope,
// letting a request overwrite any variable the rest of the script uses
// (including $link). Reading the specific fields we need instead.
$tiffinno = (string) ($_POST['tiffinno'] ?? '');
$thalisize = (string) ($_POST['thalisize'] ?? '');
$transporter = $_POST['transporter'] ?? null;
$thali = (string) ($_POST['Thali'] ?? '');

if ($thali === '') {
    header("Location: pendingactions.php");
    exit;
}

if ($transporter !== null) {
    db_query(
        $link,
        "UPDATE thalilist SET tiffinno = ?, thalisize = ?, Transporter = ? WHERE Thali = ?",
        "ssss",
        [$tiffinno, $thalisize, (string) $transporter, $thali]
    );
} else {
    // BUG FIX: the original column name here was `tifinno` (missing an
    // 'f') instead of `tiffinno` — a typo that would make this branch
    // fail outright whenever no transporter was supplied.
    db_query(
        $link,
        "UPDATE thalilist SET tiffinno = ?, thalisize = ? WHERE Thali = ?",
        "sss",
        [$tiffinno, $thalisize, $thali]
    );
}

header("Location: pendingactions.php");
exit;

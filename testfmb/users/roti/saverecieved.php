<?php
include('../connection.php');
include('../_authCheck.php');
require_once('helpers.php');

$action = $_POST['action'] ?? null;
if ($action === null) {
    header("Location: /testfmb/users/roti/recieved.php");
    exit;
}

$makerId = (int) ($_POST['maker_id'] ?? 0);
$recievedDate = (string) ($_POST['recieved_date'] ?? '');
$rotiRecieved = is_numeric($_POST['roti_recieved'] ?? null) ? (int) $_POST['roti_recieved'] : null;
$rotiStatus = (string) ($_POST['roti_status'] ?? '');
$recievedBy = current_user_email() ?? '';

$validStatuses = ['pending', 'recieved'];

if ($makerId <= 0 || $recievedDate === '' || $rotiRecieved === null || $rotiRecieved < 0
    || !in_array($rotiStatus, $validStatuses, true) || !DateTime::createFromFormat('Y-m-d', $recievedDate) || $recievedBy === '') {
    header("Location: /testfmb/users/roti/recieved.php?action=error");
    exit;
}

if ($action === 'add_rrecieved') {
    // Single atomic upsert instead of SELECT-then-branch — relies on the
    // UNIQUE KEY (maker_id, recieved_date) added in schema-modernization.sql.
    db_query(
        $link,
        "INSERT INTO fmb_roti_recieved (`maker_id`, `recieved_date`, `roti_recieved`, `roti_status`, `recieved_by`) VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            `roti_recieved` = VALUES(`roti_recieved`),
            `roti_status` = VALUES(`roti_status`),
            `recieved_by` = VALUES(`recieved_by`)",
        "isiss",
        [$makerId, $recievedDate, $rotiRecieved, $rotiStatus, $recievedBy]
    );

    $wasUpdate = mysqli_affected_rows($link) > 1;
    header("Location: /testfmb/users/roti/recieved.php?action=" . ($wasUpdate ? 'edit' : 'add')
        . "&maker=" . $makerId . "&recieved_date=" . urlencode($recievedDate));
    exit;
}

if ($action === 'edit_rrecieved') {
    $rrecievedId = (int) ($_POST['rrecieved_id'] ?? 0);
    if ($rrecievedId <= 0) {
        header("Location: /testfmb/users/roti/recieved.php?action=error");
        exit;
    }

    db_query(
        $link,
        "UPDATE fmb_roti_recieved SET `roti_recieved` = ?, `roti_status` = ?, `recieved_by` = ? WHERE `id` = ?",
        "issi",
        [$rotiRecieved, $rotiStatus, $recievedBy, $rrecievedId]
    );
    header("Location: /testfmb/users/roti/recieved.php?action=edit&maker=" . $makerId . "&recieved_date=" . urlencode($recievedDate));
    exit;
}

if ($action === 'delete_rrecieved') {
    $rrecievedId = (int) ($_POST['rrecieved_id'] ?? 0);
    if ($rrecievedId <= 0) {
        header("Location: /testfmb/users/roti/recieved.php?action=error");
        exit;
    }

    db_query($link, "DELETE FROM fmb_roti_recieved WHERE `id` = ?", "i", [$rrecievedId]);
    header("Location: /testfmb/users/roti/recieved.php?action=delete&maker=" . $makerId . "&recieved_date=" . urlencode($recievedDate));
    exit;
}

header("Location: /testfmb/users/roti/recieved.php");
exit;

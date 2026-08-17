<?php
include('../connection.php');
include('../_authCheck.php');
require_once('helpers.php');

$action = $_POST['action'] ?? null;
if ($action === null) {
    header("Location: /testfmb/users/roti/distribute.php");
    exit;
}

$makerId = (int) ($_POST['maker_id'] ?? 0);
$distributionDate = (string) ($_POST['distribution_date'] ?? '');
$flourDistributed = is_numeric($_POST['flour_distributed'] ?? null) ? (float) $_POST['flour_distributed'] : null;
$oilDistributed = is_numeric($_POST['oil_distributed'] ?? null) ? (float) $_POST['oil_distributed'] : null;
$packetsDistributed = is_numeric($_POST['packets_distributed'] ?? null) ? (float) $_POST['packets_distributed'] : 0.0;
$distributedBy = current_user_email() ?? '';

if ($makerId <= 0 || $distributionDate === '' || $flourDistributed === null || $oilDistributed === null
    || !DateTime::createFromFormat('Y-m-d', $distributionDate) || $distributedBy === '') {
    header("Location: /testfmb/users/roti/distribute.php?action=error");
    exit;
}

// Carry forward the running balance from the most recent prior distribution record.
$adistribution = db_query(
    $link,
    "SELECT `distribution_date`, `flour_distributed`, `flour_left`, `oil_distributed`, `oil_left`, `packets_distributed`, `packets_left`
     FROM fmb_roti_distribution WHERE `distribution_date` < ? AND `maker_id` = ? ORDER BY `distribution_date` DESC LIMIT 1",
    "si",
    [$distributionDate, $makerId]
);

if ($adistribution->num_rows > 0) {
    $row_adistribution = $adistribution->fetch_assoc();

    $recieved = db_query(
        $link,
        "SELECT SUM(roti_recieved) AS total_roti FROM fmb_roti_recieved
         WHERE `recieved_date` BETWEEN ? AND ? AND `maker_id` = ? AND `roti_status` = 'recieved'",
        "ssi",
        [$row_adistribution['distribution_date'], $distributionDate, $makerId]
    );
    $row_recieved = mysqli_fetch_assoc($recieved);
    $total_roti = (float) ($row_recieved['total_roti'] ?? 0);

    $flour_required = $total_roti * ATTO_PER_ROTI;
    $oil_required = $total_roti * OIL_PER_ROTI;
    $packets_required = $total_roti * PKTS_PER_ROTI;

    $flour_left = $row_adistribution['flour_distributed'] + $row_adistribution['flour_left'] - $flour_required;
    $oil_left = $row_adistribution['oil_distributed'] + $row_adistribution['oil_left'] - $oil_required;
    $packets_left = ($row_adistribution['packets_distributed'] ?? 0) + ($row_adistribution['packets_left'] ?? 0) - $packets_required;
} else {
    $flour_left = 0;
    $oil_left = 0;
    $packets_left = 0;
}

// Privileged users may manually override the computed "left" balances.
if (is_roti_privileged_user()) {
    if (isset($_POST['flour_left']) && is_numeric($_POST['flour_left']) && (float) $_POST['flour_left'] !== 0.0) {
        $flour_left = (float) $_POST['flour_left'];
    }
    if (isset($_POST['oil_left']) && is_numeric($_POST['oil_left']) && (float) $_POST['oil_left'] !== 0.0) {
        $oil_left = (float) $_POST['oil_left'];
    }
    if (isset($_POST['packets_left']) && is_numeric($_POST['packets_left']) && (float) $_POST['packets_left'] !== 0.0) {
        $packets_left = (float) $_POST['packets_left'];
    }
}

if ($action === 'add_rdistribute') {
    // Single atomic upsert instead of SELECT-then-branch — relies on the
    // UNIQUE KEY (maker_id, distribution_date) added in
    // schema-modernization.sql. Also closes the race window where two
    // people saving the same maker+date at once could previously both see
    // "not found" and both INSERT.
    db_query(
        $link,
        "INSERT INTO fmb_roti_distribution
            (`maker_id`, `distribution_date`, `flour_distributed`, `flour_left`, `oil_distributed`, `oil_left`, `packets_distributed`, `packets_left`, `distributed_by`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            `flour_distributed` = VALUES(`flour_distributed`),
            `flour_left` = VALUES(`flour_left`),
            `oil_distributed` = VALUES(`oil_distributed`),
            `oil_left` = VALUES(`oil_left`),
            `packets_distributed` = VALUES(`packets_distributed`),
            `packets_left` = VALUES(`packets_left`),
            `distributed_by` = VALUES(`distributed_by`)",
        "isdddddds",
        [$makerId, $distributionDate, $flourDistributed, $flour_left, $oilDistributed, $oil_left, $packetsDistributed, $packets_left, $distributedBy]
    );

    // mysqli_affected_rows() returns 1 for a fresh INSERT and 2 for a row
    // that triggered the UPDATE branch (MySQL's convention for
    // ON DUPLICATE KEY UPDATE), so we can still report add vs. edit
    // accurately without a separate lookup.
    $wasUpdate = mysqli_affected_rows($link) > 1;
    header("Location: /testfmb/users/roti/distribute.php?action=" . ($wasUpdate ? 'edit' : 'add')
        . "&maker=" . $makerId . "&distribution_date=" . urlencode($distributionDate));
    exit;
}

if ($action === 'edit_rdistribute') {
    $rdistributeId = (int) ($_POST['rdistribute_id'] ?? 0);
    if ($rdistributeId <= 0) {
        header("Location: /testfmb/users/roti/distribute.php?action=error");
        exit;
    }

    db_query(
        $link,
        "UPDATE fmb_roti_distribution SET `maker_id` = ?, `distribution_date` = ?, `flour_distributed` = ?, `flour_left` = ?,
                `oil_distributed` = ?, `oil_left` = ?, `packets_distributed` = ?, `packets_left` = ?, `distributed_by` = ? WHERE `id` = ?",
        "isddddddsi",
        [$makerId, $distributionDate, $flourDistributed, $flour_left, $oilDistributed, $oil_left, $packetsDistributed, $packets_left, $distributedBy, $rdistributeId]
    );
    header("Location: /testfmb/users/roti/distribute.php?action=edit&maker=" . $makerId . "&distribution_date=" . urlencode($distributionDate));
    exit;
}

if ($action === 'delete_rdistribute') {
    $rdistributeId = (int) ($_POST['rdistribute_id'] ?? 0);
    if ($rdistributeId <= 0) {
        header("Location: /testfmb/users/roti/distribute.php?action=error");
        exit;
    }

    db_query($link, "DELETE FROM fmb_roti_distribution WHERE `id` = ?", "i", [$rdistributeId]);
    header("Location: /testfmb/users/roti/distribute.php?action=delete&maker=" . $makerId . "&distribution_date=" . urlencode($distributionDate));
    exit;
}

header("Location: /testfmb/users/roti/distribute.php");
exit;

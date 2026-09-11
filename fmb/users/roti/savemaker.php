<?php
include('../connection.php');
include('../_authCheck.php');
require_once('helpers.php');

$action = $_POST['action'] ?? null;

if ($action === 'add_rmaker') {
    $itsNo = trim((string) ($_POST['its_no'] ?? ''));
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $code = trim((string) ($_POST['code'] ?? ''));
    $mobileNo = trim((string) ($_POST['mobile_no'] ?? ''));
    $bankDetails = trim((string) ($_POST['bank_details'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $defaultAtta = is_numeric($_POST['default_atta'] ?? null) ? (float) $_POST['default_atta'] : -1;
    $defaultOil = is_numeric($_POST['default_oil'] ?? null) ? (float) $_POST['default_oil'] : -1;
    $defaultRoti = is_numeric($_POST['default_roti'] ?? null) ? (float) $_POST['default_roti'] : -1;
    $faizContribution = is_numeric($_POST['faiz_contribution'] ?? null) ? (float) $_POST['faiz_contribution'] : -1;

    if ($itsNo === '' || $fullName === '' || $code === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $email) || $defaultAtta < 0 || $defaultOil < 0 || $defaultRoti < 0 || $faizContribution < 0 || !preg_match('/^[0-9]{10}$/', $mobileNo)) {
        header("Location: /fmb/users/roti/maker.php?action=error");
        exit;
    }

    // With the UNIQUE KEY (code) added in schema-modernization.sql, a
    // duplicate maker code now fails at the database level — catch that
    // and send back a friendly message instead of a fatal error.
    try {
        db_query(
            $link,
            "INSERT INTO fmb_roti_maker (`its_no`, `full_name`, `code`, `mobile_no`, `email`, `default_atta`, `default_oil`, `default_roti`, `faiz_contribution`, `bank_details`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "sssssdddds",
            [$itsNo, $fullName, $code, $mobileNo, $email, $defaultAtta, $defaultOil, $defaultRoti, $faizContribution, $bankDetails]
        );
    } catch (RuntimeException $e) {
        if (mysqli_errno($link) === 1062) { // ER_DUP_ENTRY
            header("Location: /fmb/users/roti/maker.php?action=duplicate&full_name=" . urlencode($fullName));
            exit;
        }
        throw $e;
    }
    header("Location: /fmb/users/roti/maker.php?action=add&full_name=" . urlencode($fullName));
    exit;
}

if ($action === 'edit_rmaker') {
    $itsNo = trim((string) ($_POST['its_no'] ?? ''));
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $code = trim((string) ($_POST['code'] ?? ''));
    $mobileNo = trim((string) ($_POST['mobile_no'] ?? ''));
    $bankDetails = trim((string) ($_POST['bank_details'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $defaultAtta = is_numeric($_POST['default_atta'] ?? null) ? (float) $_POST['default_atta'] : -1;
    $defaultOil = is_numeric($_POST['default_oil'] ?? null) ? (float) $_POST['default_oil'] : -1;
    $defaultRoti = is_numeric($_POST['default_roti'] ?? null) ? (float) $_POST['default_roti'] : -1;
    $faizContribution = is_numeric($_POST['faiz_contribution'] ?? null) ? (float) $_POST['faiz_contribution'] : -1;
    $rmakerId = (int) ($_POST['rmaker_id'] ?? 0);

    if ($itsNo === '' || $fullName === '' || $code === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $email) || $defaultAtta < 0 || $defaultOil < 0 || $defaultRoti < 0 || $faizContribution < 0 || !preg_match('/^[0-9]{10}$/', $mobileNo) || $rmakerId <= 0) {
        header("Location: /fmb/users/roti/maker.php?action=error");
        exit;
    }

    try {
        db_query(
            $link,
            "UPDATE fmb_roti_maker SET `its_no` = ?, `full_name` = ?, `code` = ?, `mobile_no` = ?, `email` = ?, `default_atta` = ?, `default_oil` = ?, `default_roti` = ?, `faiz_contribution` = ?, `bank_details` = ? WHERE `id` = ?",
            "sssssddddsi",
            [$itsNo, $fullName, $code, $mobileNo, $email, $defaultAtta, $defaultOil, $defaultRoti, $faizContribution, $bankDetails, $rmakerId]
        );
    } catch (RuntimeException $e) {
        if (mysqli_errno($link) === 1062) { // ER_DUP_ENTRY
            header("Location: /fmb/users/roti/maker.php?action=duplicate&full_name=" . urlencode($fullName));
            exit;
        }
        throw $e;
    }
    header("Location: /fmb/users/roti/maker.php?action=edit&full_name=" . urlencode($fullName));
    exit;
}

if ($action === 'delete_rmaker') {
    $rmakerId = (int) ($_POST['rmaker_id'] ?? 0);
    $fullName = trim((string) ($_POST['full_name'] ?? ''));

    if ($rmakerId <= 0) {
        header("Location: /fmb/users/roti/maker.php?action=error");
        exit;
    }

    db_query($link, "DELETE FROM fmb_roti_maker WHERE `id` = ?", "i", [$rmakerId]);
    header("Location: /fmb/users/roti/maker.php?action=delete&full_name=" . urlencode($fullName));
    exit;
}

header("Location: /fmb/users/roti/maker.php");
exit;

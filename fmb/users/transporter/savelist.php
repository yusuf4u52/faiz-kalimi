<?php
include('../connection.php');
include('../_authCheck.php');
require_once('../helpers.php');

$action = $_POST['action'] ?? null;

if ($action === 'add_transporter') {
    $name = trim((string) ($_POST['Name'] ?? ''));
    $mobile = trim((string) ($_POST['Mobile'] ?? ''));
    $email = trim((string) ($_POST['Email'] ?? ''));

    if ($name === '' || !is_valid_mobile($mobile) || !is_valid_gmail_address($email)) {
        header("Location: /fmb/users/transporter/list.php?action=error");
        exit;
    }

    // With the UNIQUE KEY (Email) added in schema-modernization.sql, a
    // duplicate email now fails at the database level — catch that and
    // send back a friendly message instead of a fatal error.
    try {
        db_query(
            $link,
            "INSERT INTO transporters (`Name`, `Mobile`, `Email`) VALUES (?, ?, ?)",
            "sss",
            [$name, $mobile, $email]
        );
    } catch (RuntimeException $e) {
        if (mysqli_errno($link) === 1062) { // ER_DUP_ENTRY
            header("Location: /fmb/users/transporter/list.php?action=duplicate&Name=" . urlencode($name));
            exit;
        }
        throw $e;
    }
    header("Location: /fmb/users/transporter/list.php?action=add&Name=" . urlencode($name));
    exit;
}

if ($action === 'edit_transporter') {
    $name = trim((string) ($_POST['Name'] ?? ''));
    $mobile = trim((string) ($_POST['Mobile'] ?? ''));
    $email = trim((string) ($_POST['Email'] ?? ''));
    $transporterId = (int) ($_POST['transporter_id'] ?? 0);

    if ($name === '' || !is_valid_mobile($mobile) || !is_valid_gmail_address($email) || $transporterId <= 0) {
        header("Location: /fmb/users/transporter/list.php?action=error");
        exit;
    }

    try {
        db_query(
            $link,
            "UPDATE transporters SET `Name` = ?, `Mobile` = ?, `Email` = ? WHERE `id` = ?",
            "sssi",
            [$name, $mobile, $email, $transporterId]
        );
    } catch (RuntimeException $e) {
        if (mysqli_errno($link) === 1062) { // ER_DUP_ENTRY
            header("Location: /fmb/users/transporter/list.php?action=duplicate&Name=" . urlencode($name));
            exit;
        }
        throw $e;
    }
    header("Location: /fmb/users/transporter/list.php?action=edit&Name=" . urlencode($name));
    exit;
}

if ($action === 'delete_transporter') {
    $transporterId = (int) ($_POST['transporter_id'] ?? 0);
    $name = trim((string) ($_POST['Name'] ?? ''));

    if ($transporterId <= 0) {
        header("Location: /fmb/users/transporter/list.php?action=error");
        exit;
    }

    db_query($link, "DELETE FROM transporters WHERE `id` = ?", "i", [$transporterId]);
    header("Location: /fmb/users/transporter/list.php?action=delete&Name=" . urlencode($name));
    exit;
}

// No recognised action — send the user back rather than rendering a blank page.
header("Location: /fmb/users/transporter/list.php");
exit;

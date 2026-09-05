<?php
include('connection.php');
require_once('helpers.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['fromLogin'])) {
    header("Location: /fmb/index.php");
    exit;
}

$its = trim((string) ($_POST['its'] ?? ''));
$name = trim((string) ($_POST['name'] ?? ''));
$mobile = trim((string) ($_POST['mobile'] ?? ''));
$referenceId = $_POST['reference_id'] ?? '';
$eventId = $_POST['eventid'] ?? '';

if (!preg_match('/^[0-9]{8}$/', $its) || $name === '' || !preg_match('/^[0-9]{10}$/', $mobile)
    || !ctype_digit((string) $referenceId) || !ctype_digit((string) $eventId)) {
    header("Location: events.php");
    exit;
}

$result = db_query($link, "SELECT id FROM thalilist WHERE ITS_No = ? AND Active IN ('0', '1')", "s", [$its]);

if (mysqli_num_rows($result) === 0) {
    db_query(
        $link,
        "INSERT INTO event_response (`reference_id`, `thaliid`, `eventid`, `response`, `its`, `name`, `mobile`) VALUES (?, '0', ?, 'yes', ?, ?, ?)",
        "iisss",
        [(int) $referenceId, (int) $eventId, $its, $name, $mobile]
    );
    ?>
    <script>
        window.alert('Registered Successfully');
        window.location.href = 'events.php';
    </script>
    <?php
} else {
    ?>
    <script>
        window.alert('The friend you are trying to add is already taking thali and so needs to login to his account and register his confirmation.');
        window.location.href = 'events.php';
    </script>
    <?php
}

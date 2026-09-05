<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');
include('getHijriDate.php');
require '_sendMail.php';

$today = getTodayDateHijri();

$columns = ['Thali', 'tiffinno', 'thalisize', 'Active', 'Thali_Start_Date', 'yearly_hub'];
$values = [
    (string) ($_POST['sabeelno'] ?? ''),
    (string) ($_POST['thalino'] ?? ''),
    (string) ($_POST['thalisize'] ?? ''),
    '1',
    $today,
    (string) ($_POST['hub'] ?? ''),
];

if (isset($_POST['transporter'])) {
    $columns[] = 'Transporter';
    $values[] = (string) $_POST['transporter'];
}

if (isset($_POST['sector'])) {
    $columns[] = 'sector';
    $values[] = (string) $_POST['sector'];

    $musaidResult = db_query($link, "SELECT musaid FROM `thalilist` WHERE sector = ? LIMIT 1", "s", [$_POST['sector']]);
    if ($musaidResult->num_rows > 0) {
        $musaidRow = mysqli_fetch_assoc($musaidResult);
        $columns[] = 'musaid';
        $values[] = (string) $musaidRow['musaid'];
    }
}

$setClause = implode(', ', array_map(fn($col) => "`$col` = ?", $columns));
$types = str_repeat('s', count($values)) . 's';
$params = [...$values, (string) ($_POST['id'] ?? '')];

db_query($link, "UPDATE thalilist SET $setClause WHERE id = ?", $types, $params);

db_query(
    $link,
    "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`, `processed`) VALUES (?, ?, 'New Thali', ?, 0)",
    "sss",
    [$_POST['thalino'] ?? '', $_POST['id'] ?? '', $today]
);
db_query(
    $link,
    "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`, `processed`) VALUES (?, ?, 'Start Thali', ?, 1)",
    "sss",
    [$_POST['thalino'] ?? '', $_POST['id'] ?? '', $today]
);
db_query(
    $link,
    "UPDATE change_table SET processed = 1 WHERE userid = ? AND `Operation` IN ('Stop Permanent') AND processed = 0",
    "s",
    [$_POST['id'] ?? '']
);


$msgvar = "Salaam %name%,<br><br>

Mubarak on starting your Faiz ul Mawaid il Burhaniyah Thaali.<br><br>

Please kindly note the following:<br><br>

<b>1) Help & Assistance:</b><br>
For any help or assistance, please email us at kalimimohallapoona@gmail.com or WhatsApp us on 9826932974 / 9820518835.<br><br>

<b>2) Manage your Thaali:</b><br>
You can start or stop your Thaali, update your menu quantity, and update your details through our website:<br>
http://kalimijamaatpoona.org/fmb/<br><br>

<b>3) Login to the Website:</b><br>
Please use your registered Gmail address to login to the above website. Kindly use the same Gmail address that you entered while submitting your Thaali registration form.<br><br>

<b>4) Hub Payment:</b><br>
Please ensure that your Hub is paid for each Miqaat listed on the website. If you face any difficulty in making the Hub payment, kindly contact us in advance.<br><br>

<b>5) Tiffin:</b><br>
Please ensure that the tiffin is returned every day after being properly washed. If the tiffin is unwashed, partially washed, or not returned, your Thaali will not be delivered the following day. In such a situation, you will need to collect your Thaali from Faiz.<br><br>

The Bhai doing the delivery will still come to collect the empty tiffin so that your Thaali can be delivered the next day. Kindly note that he will collect only one empty tiffin.<br><br>

We request your kind cooperation in following the above guidelines so that the Khidmat of Faiz ul Mawaid il Burhaniyah can continue smoothly.<br><br>

Abeede Sayedna (TUS)<br>
FMB Khidmat Team";

$msgvar = str_replace(
    ['%thali%', '%name%', '%email%'],
    [$_POST['thalino'] ?? '', $_POST['name'] ?? '', $_POST['email'] ?? ''],
    $msgvar
);

if (!empty($_POST['email'])) {
    sendEmail([$_POST['email']], 'Thali Activated', $msgvar, null);
}

header("Location: pendingactions.php");
exit;

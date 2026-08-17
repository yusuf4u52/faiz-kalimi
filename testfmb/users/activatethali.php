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

$msgvar = "Salaam %name%,<br><br>Mubarak for starting your Faiz ul Mawaid il Burhaniyah Thaali -<br><br>Your Thali No. will be : <b>%thali%</b><br><br>
1) If you need any help please email us on kalimimohallapoona@gmail.com or WhatsApp us on 9096778753, 9503054797.
<br>
2) You can start / stop your thaali and update your details from the site - http://kalimijamaatpoona.org/testfmb/users/index.php
<br>
3) Please ensure your hub is paid on each Miqaat listed on the site. If you have any problems in paying the hub please contact us in advance.
<br>
4) Please ensure you return a washed tiffin everyday. If your tiffin is unwashed / partially washed or not returned, your thaali will not be delivered the next day. In this case you will have to pick it up from Faiz, your thaali will not be delivered that day. However the bhai doing delivery will come to take the empty tiffin, so that your thaali can be delivered the next day. He will only take one empty tiffin.
<br>
<br>
Abeede Sayedna (TUS)<br>
Faiz Khidmat Team<br>";

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

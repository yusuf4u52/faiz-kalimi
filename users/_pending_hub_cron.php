<?php
include('connection.php');
require_once '_sendMail.php';
require '../vendor/autoload.php';

$sql = mysqli_query($link, "SELECT `Thali`, `NAME`,`CONTACT`,`Transporter`,`Total_Pending` FROM `thalilist` WHERE `Thali` is not null AND `Total_Pending` > 0 AND `Active` = 0 ORDER BY `Transporter` ASC, `Total_Pending` DESC");

$inactiveString = implode(",", array('Thali', 'Name', 'Contact', 'Transporter', 'Total_Pending')) . "\r\n";

while ($row = mysqli_fetch_assoc($sql)) {
    $inactiveString .= implode(",", $row) . "\r\n";
}

$attachInactive = new \SendGrid\Mail\Attachment();
$attachInactive->setContent(base64_encode($inactiveString));
$attachInactive->setType('text/csv');
$attachInactive->setFilename('Inactive' . date('d/m/Y') . ".csv");
$attachInactive->setDisposition("attachment");
$attachInactive->setContentId("Database Backup");

//----- For Active ----------
$sql = mysqli_query($link, "SELECT `Thali`, `NAME`,`CONTACT`,`Transporter`,`Total_Pending` FROM `thalilist` WHERE `Thali` is not null AND `Total_Pending` > 0 AND `Active` = 1 ORDER BY `Transporter` ASC, `Total_Pending` DESC");

$activeString = implode(",", array('Thali', 'Name', 'Contact', 'Transporter', 'Total_Pending')) . "\r\n";

while ($row = mysqli_fetch_assoc($sql)) {
    $activeString .= implode(",", $row) . "\r\n";
}

$attachActive = new \SendGrid\Mail\Attachment();
$attachActive->setContent(base64_encode($activeString));
$attachActive->setType('text/csv');
$attachActive->setFilename('Active' . date('d/m/Y') . ".csv");
$attachActive->setDisposition("attachment");
$attachActive->setContentId("Database Backup");

sendEmail('yusuf4u52@gmail.com', 'Pending Hub File ' . date('d/m/Y'), 'PFA', null, array($attachActive, $attachInactive));

echo "Done";

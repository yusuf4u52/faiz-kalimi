<?php

include('connection.php');
include('_authCheck.php');
include('getHijriDate.php');
require '_sendMail.php';


$today = getTodayDateHijri();
// print_r($_POST); exit;
$values[] = "Thali = '" . addslashes($_POST['sabeelno']) . "'";
$values[] = "tiffinno = '" . addslashes($_POST['thalino']) . "'";
$values[] = "thalisize = '" . addslashes($_POST['thalisize']) . "'";
$values[] = "Active = '1'";
$values[] = "Thali_Start_Date = '" . ($today) . "'";
$values[] = "yearly_hub = '" . addslashes($_POST['hub']) . "'";

if (isset($_POST['transporter'])) {
	$values[] = "Transporter = '" . addslashes($_POST['transporter']) . "'";
}

if (isset($_POST['sector'])) {
	$values[] = "sector = '" . addslashes($_POST['sector']) . "'";
	$musaiddata = "Select musaid from `thalilist` where sector='" . $_POST['sector'] . "' limit 1";
	$musaidresult = mysqli_query($link, $musaiddata);
	if (mysqli_num_rows($musaidresult) > 0) {
		$musaidrow = mysqli_fetch_assoc($musaidresult);
		$musaid = $musaidrow['musaid'];
		$values[] = "musaid = '" . addslashes($musaid) . "'";
	}
}

mysqli_query($link, "UPDATE thalilist set " . implode(',', $values) . " WHERE id = '" . $_POST['id'] . "'") or die(mysqli_error($link));
mysqli_query($link, "INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`,`processed`) VALUES ('" . $_POST['thalino'] . "','" . $_POST['id'] . "', 'New Thali','" . $today . "',0)") or die(mysqli_error($link));
mysqli_query($link, "INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`,`processed`) VALUES ('" . $_POST['thalino'] . "','" . $_POST['id'] . "', 'Start Thali','" . $today . "',1)") or die(mysqli_error($link));
mysqli_query($link, "update change_table set processed = 1 where userid = '" . $_POST['id'] . "' and `Operation` in ('Stop Permanent') and processed = 0") or die(mysqli_error($link));


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

$msgvar = str_replace(array('%thali%', '%name%', '%email%'), array($_POST['thalino'], $_POST['name'], $_POST['email']), $msgvar);
sendEmail([$_POST['email']], 'Thali Activated', $msgvar, null);

header("Location: pendingactions.php");

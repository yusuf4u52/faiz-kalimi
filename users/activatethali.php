<?php

include('connection.php');
include('_authCheck.php');
include('getHijriDate.php');
require '_sendMail.php';


$today = getTodayDateHijri();
// print_r($_POST); exit;
$values[] = "Thali = '".addslashes($_POST['thalino'])."'";
$values[] = "musaid = '".addslashes($_POST['musaid'])."'";
$values[] = "Active = '1'";
$values[] = "Thali_Start_Date = '".($today)."'";
$values[] = "yearly_hub = '".($_POST['hub'])."'";

if(isset($_POST['transporter']))
{
	$values[] = "Transporter = '".addslashes($_POST['transporter'])."'";	
} 

mysqli_query($link,"UPDATE thalilist set ".implode(',', $values)." WHERE id = '".$_POST['id']."'") or die(mysqli_error($link));
mysqli_query($link,"INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`,`processed`) VALUES ('" . $_POST['thalino'] . "','".$_POST['id']."', 'New Thali','" . $today . "',0)") or die(mysqli_error($link));
mysqli_query($link,"INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`,`processed`) VALUES ('" . $_POST['thalino'] . "','".$_POST['id']."', 'Start Thali','" . $today . "',1)") or die(mysqli_error($link));
mysqli_query($link,"update change_table set processed = 1 where userid = '" . $_POST['id'] . "' and `Operation` in ('Stop Permanent') and processed = 0") or die(mysqli_error($link));


$msgvar = "Salaam %name%,<br><br>Mubarak for starting your Faiz ul Mawaid il Burhaniyah Thaali -<br><br>Your Thali No. will be : <b>%thali%</b><br><br>
1) If you need any help please email us on kalimifaiz@gmail.com or WhatsApp us on 9096778753, 9503054797.
<br>
2) You can start / stop your thaali and update your details from the site - http://kalimijamaatpoona.org/fmb/users/index.php
<br>
3) Please ensure your hub is paid on each Miqaat listed on the site. If you have any problems in paying the hub please contact us in advance.
<br>
4) Please ensure you return a washed tiffin everyday. If your tiffin is unwashed / partially washed or not returned, your thaali will not be delivered the next day. In this case you will have to pick it up from Faiz, your thaali will not be delivered that day. However the bhai doing delivery will come to take the empty tiffin, so that your thaali can be delivered the next day. He will only take one empty tiffin.
<br>
<br>
Abeede Sayedna (TUS)<br>
Faiz Khidmat Team<br>";

$msgvar = str_replace(array('%thali%','%name%','%email%'), array($_POST['thalino'],$_POST['name'],$_POST['email']), $msgvar);
sendEmail($_POST['email'], 'Thali Activated', $msgvar, null);

header("Location: pendingactions.php");

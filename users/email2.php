<?php
include('connection.php');
include('getHijriDate.php');
include '../backup/_email_backup.php';
include '../sms/_sms_automation.php';
require_once '_sendMail.php';

error_reporting(0);
$day = date("D");
if ($day == 'Sat') {
	echo "Skipping email on saturday.";
	exit;
}
$sql = mysqli_query($link, "SELECT t.id, c.Thali, t.thalisize, t.NAME, t.CONTACT, t.Transporter, t.Full_Address, c.Operation,c.id,t.markaz
						from change_table as c
						inner join thalilist as t on (c.userid = t.id)
						WHERE c.processed = 0");
$request = array();
$processed_ids = array();
echo "<pre>";
while ($row = mysqli_fetch_assoc($sql)) {
	$request[$row['Transporter']][$row['Operation']][] = $row;
	// To add Markaz
	// $request[$row['markaz']][$row['Operation']][] = $row;

	$processed[] = $row['id'];
}
foreach ($request as $transporter_name => $thalis) {
	$msgvar .= "<b>" . $transporter_name . "</b>\n";
	foreach ($thalis as $operation_type => $thali_details) {
		$msgvar .= $operation_type . "\n";
		if (in_array($operation_type, array('Start Thali', 'Start Transport', 'Update Address', 'New Thali'))) {
			foreach ($thali_details as $thaliuser) {
				$msgvar .= 	sprintf("%s - %s - %s - %s - %s - %s\n", $thaliuser['Thali'], $thaliuser['thalisize'], $thaliuser['NAME'], $thaliuser['CONTACT'], $thaliuser['Transporter'], $thaliuser['Full_Address']);
			}
		} else if (in_array($operation_type, array('Stop Thali', 'Stop Transport'))) {
			foreach ($thali_details as $thaliuser) {
				$msgvar .= 	sprintf("%s\n", $thaliuser['Thali']);
			}
		} else if (in_array($operation_type, array('Stop Permanent'))) {
			foreach ($thali_details as $thaliuser) {
				$msgvar .= 	sprintf("%s - %s\n", $thaliuser['Thali'], $thaliuser['NAME']);
			}
		}
		$msgvar .= 	"\n";
	}
}
mysqli_query($link, "update change_table set processed = 1 where id in (" . implode(',', $processed) . ")");
//----------------- Transporter wise count daily----------------------
$msgvar .= "\n<b>Transporter Count</b>\n";
$sql = mysqli_query($link, "SELECT Transporter,
					count(*) as tcount,
    				sum(case when thalisize = 'Small' then 1 else 0 end) AS smallcount,
    				sum(case when thalisize = 'Medium' then 1 else 0 end) AS mediumcount,
					sum(case when thalisize = 'Large' then 1 else 0 end) AS largecount
					FROM `thalilist` WHERE Active = 1 group by Transporter");
$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
while ($row = mysqli_fetch_assoc($sql)) {
	$msgvar .= 	sprintf("%s\n", $row['Transporter'] . ' Small: ' . $row['smallcount'] . ' Medium: ' . $row['mediumcount'] . ' Large: ' . $row['largecount'] . ' Total: ' . $row['tcount']);
	$insert_sql = "INSERT INTO transporter_daily_count (`date`, `name`,`small`,`medium`,`large`, `count`) VALUES ('" . $tomorrow_date . "','" . $row['Transporter'] . "', '" . $row['smallcount'] . "', '" . $row['mediumcount'] . "', '" . $row['largecount'] . "', '" . $row['tcount'] . "')";
	mysqli_query($link, $insert_sql) or die(mysqli_error($link));
}

//-------------------------------------------------------------------
$totalcountonsize = mysqli_query($link, "SELECT
					count(*) as tcount,
    				sum(case when thalisize = 'Small' then 1 else 0 end) AS smallcount,
    				sum(case when thalisize = 'Medium' then 1 else 0 end) AS mediumcount,
					sum(case when thalisize = 'Large' then 1 else 0 end) AS largecount
					FROM `thalilist` WHERE Active = 1");
$result = mysqli_fetch_row($totalcountonsize);
$msgvar .= 	sprintf("%s\n",  'Grand Total: ' . ' Small: ' . $result[1] . ' Medium: ' . $result[2] . ' Large: ' . $result[3] . ' Total: ' . $result[0]);
$hijridate = getTodayDateHijri();
$gregoraindate = date("Y-m-d");
mysqli_query($link, "INSERT INTO daily_thali_count (`Date`, `Hijridate`, `small`,`medium`,`large`, `Count`) VALUES ('" . $gregoraindate . "','" . $hijridate . "','" . $result[1] . "','" . $result[2] . "','" . $$result[3] . "'," . $result[0] . ")") or die(mysqli_error($link));

mysqli_query($link, "UPDATE thalilist SET thalicount = thalicount + 1 WHERE Active='1'");
$msgvar = str_replace("\n", "<br>", $msgvar);
sendEmail('kalimifaiz@gmail.com', 'Start Stop update ' . date('d/m/Y'), $msgvar, null);

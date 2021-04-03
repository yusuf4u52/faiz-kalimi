<?php
include('connection.php');
include('getHijriDate.php');
include 'stop_permanent_automate.php';
include '../backup/_email_backup.php';
include '../sms/_sms_automation.php';
require_once '_sendMail.php';

error_reporting(0);
$day = date("D");
if ($day == 'Sat') {
	echo "Skipping email on saturday.";
	exit;
}
$sql = mysqli_query($link,"SELECT t.id, c.Thali, t.NAME, t.CONTACT, t.Transporter, t.Full_Address, c.Operation,c.id,t.markaz
						from change_table as c
						inner join thalilist as t on (c.userid = t.id)
						WHERE c.processed = 0");
$request = array();
$processed_ids = array(); 
echo "<pre>";
while($row = mysqli_fetch_assoc($sql))
{
    $request[$row['Transporter']][$row['Operation']][] = $row;
    // To add Markaz
	// $request[$row['markaz']][$row['Operation']][] = $row;
    
	$processed[] = $row['id'];
}
foreach ($request as $transporter_name => $thalis) {
	$msgvar .= "<b>".$transporter_name."</b>\n";
	foreach ($thalis as $operation_type => $thali_details) {
		$msgvar .= $operation_type."\n";
		if(in_array($operation_type, array('Start Thali','Start Transport','Update Address','New Thali')))
		{
			foreach ($thali_details as $thaliuser) {
				$msgvar .= 	sprintf("%s - %s - %s - %s - %s\n",$thaliuser['Thali'],$thaliuser['NAME'],$thaliuser['CONTACT'],$thaliuser['Transporter'],$thaliuser['Full_Address']);
			}	
		}
		else if(in_array($operation_type, array('Stop Thali','Stop Transport')))
		{
			foreach ($thali_details as $thaliuser) {
				$msgvar .= 	sprintf("%s\n",$thaliuser['Thali']);
			}
		}
		else if(in_array($operation_type, array('Stop Permanent')))
		{
			foreach ($thali_details as $thaliuser) {
				$msgvar .= 	sprintf("%s - %s\n",$thaliuser['Thali'],$thaliuser['NAME']);
			}
		}
		$msgvar .= 	"\n";
	}
}
mysqli_query($link,"update change_table set processed = 1 where id in (".implode(',', $processed).")");
//----------------- Transporter wise count daily----------------------
$msgvar .= "\n<b>Transporter Count</b>\n";
$sql = mysqli_query($link,"SELECT Transporter,count(*) as tcount FROM `thalilist` WHERE Active = 1 and Transporter != 'Transporter' group by Transporter");
$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
while($row = mysqli_fetch_assoc($sql))
{
	$msgvar .= 	sprintf("%s\n",$row['Transporter'].' '.$row['tcount']);
	$insert_sql = "INSERT INTO transporter_daily_count (`date`, `name`, `count`) VALUES ('" . $tomorrow_date . "','" . $row['Transporter'] . "','" . $row['tcount'] . "')";
  	mysqli_query($link, $insert_sql) or die(mysqli_error($link));
}
//-------------------------------------------------------------------
$result = mysqli_query($link,"SELECT * FROM thalilist WHERE Active='1' ");
$count=mysqli_num_rows($result);
$hijridate = getTodayDateHijri();
$gregoraindate = date("Y-m-d");
mysqli_query($link,"INSERT INTO daily_thali_count (`Date`, `Hijridate`, `Count`) VALUES ('".$gregoraindate."','".$hijridate."',".$count.")") or die(mysqli_error($link));

$msgvar .= "\n Count \n $count ";
$myfile = fopen("requestarchive.txt", "a") or die("Unable to open file!");
$txt= date('d/m/Y')."\n".$msgvar."\n";
fwrite($myfile, $txt);
fclose($myfile);
mysqli_query($link,"UPDATE thalilist SET thalicount = thalicount + 1 WHERE Active='1'");
$msgvar = str_replace("\n", "<br>", $msgvar);
sendEmail('kalimifaiz@gmail.com', 'Start Stop update '.date('d/m/Y'), $msgvar, null);

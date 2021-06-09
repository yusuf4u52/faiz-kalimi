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
$sql = mysqli_query($link, "SELECT t.id, c.Thali, t.tiffinno, t.thalisize, t.NAME, t.CONTACT, t.Transporter,t.wingflat, t.society, t.Full_Address, c.Operation,c.id
						from change_table as c
						inner join thalilist as t on (c.userid = t.id)
						WHERE c.processed = 0");
$request = array();
$processed_ids = array();
while ($row = mysqli_fetch_assoc($sql)) {
	$request[$row['Transporter']][$row['Operation']][] = $row;
	$processed[] = $row['id'];
}
foreach ($request as $transporter_name => $thalis) {
	$msgvar .= "<b>" . $transporter_name . "</b>\n";
	foreach ($thalis as $operation_type => $thali_details) {
		$msgvar .= $operation_type . "\n";
		foreach ($thali_details as $thaliuser) {
			$msgvar .= 	sprintf("%s - %s - %s - %s - %s - %s\n", $thaliuser['tiffinno'], $thaliuser['thalisize'], $thaliuser['NAME'], $thaliuser['CONTACT'], $thaliuser['wingflat'], $thaliuser['society']);
		}
	}
	$msgvar .= 	"\n";
}

mysqli_query($link, "update change_table set processed = 1 where id in (" . implode(',', $processed) . ")");
//----------------- Transporter wise count daily----------------------
$hijridate = getHijriDate($tomorrow_date);
$msgvar .= "\n<b>Transporter Count $hijridate </b>\n";
$sql = mysqli_query($link, "SELECT Transporter,
					count(*) as tcount,
    				sum(case when thalisize = 'Small' then 1 else 0 end) AS smallcount,
    				sum(case when thalisize = 'Medium' then 1 else 0 end) AS mediumcount,
					sum(case when thalisize = 'Large' then 1 else 0 end) AS largecount
					FROM `thalilist` WHERE Active = 1 group by Transporter");
$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
$pivot = array();
$transporters = array();
while ($row = mysqli_fetch_assoc($sql)) {
	$transporters[$row['Transporter']] = 1;
	$pivot["small"][$row['Transporter']] = $row['smallcount'];
	$pivot["medium"][$row['Transporter']] = $row['mediumcount'];
	$pivot["large"][$row['Transporter']] = $row['largecount'];
	$pivot["total"][$row['Transporter']] = (int) $row['smallcount'] + (int) $row['mediumcount'] + (int) $row['largecount'];
	$insert_sql = "INSERT INTO transporter_daily_count (`date`, `name`,`small`,`medium`,`large`, `count`) VALUES ('" . $tomorrow_date . "','" . $row['Transporter'] . "', '" . $row['smallcount'] . "', '" . $row['mediumcount'] . "', '" . $row['largecount'] . "', '" . $row['tcount'] . "')";
	mysqli_query($link, $insert_sql) or die(mysqli_error($link));
}
$transporters["total"] = 1;

//-------------------------------------------------------------------
$totalcountonsize = mysqli_query($link, "SELECT
					count(*) as tcount,
    				sum(case when thalisize = 'Small' then 1 else 0 end) AS small,
    				sum(case when thalisize = 'Medium' then 1 else 0 end) AS medium,
					sum(case when thalisize = 'Large' then 1 else 0 end) AS large
					FROM `thalilist` WHERE Active = 1");

$result = mysqli_fetch_row($totalcountonsize);
$pivot["small"]["total"] = $result[1];
$pivot["medium"]["total"] = $result[2];
$pivot["large"]["total"] = $result[3];
$pivot["total"]["total"] = $result[0];

mysqli_query($link, "INSERT INTO daily_thali_count (`Date`, `Hijridate`, `small`,`medium`,`large`, `Count`) VALUES ('" . $tomorrow_date . "','" . $hijridate . "','" . $result[1] . "','" . $result[2] . "','" . $$result[3] . "'," . $result[0] . ")") or die(mysqli_error($link));

mysqli_query($link, "UPDATE thalilist SET thalicount = thalicount + 1 WHERE Active='1'");
$msgvar = str_replace("\n", "<br>", $msgvar);

$pivotTable = "<table border='1' ><tr><td></td>";
foreach ($transporters as $tname => $value) {
	$pivotTable .= "<td style='padding: 2px 10px 2px 10px;'>" . $tname . "</td>"; 
}
$pivotTable .= "</tr>";

foreach ($pivot as $size => $tcountArr) {
	$pivotTable .= "<tr><td style='padding: 2px 10px 2px 10px;'>" . $size . "</td>";
	foreach($transporters as $tname => $value) {
		$pivotTable .= "<td style='padding: 2px 10px 2px 10px;'>" . $tcountArr[$tname] . "</td>";
	}
	$pivotTable .= "</tr>";
}
$pivotTable .= "</table>";

$msgvar .= $pivotTable;

sendEmail('kalimifaiz@gmail.com', 'Start Stop update ' . date('d/m/Y'), $msgvar, null);

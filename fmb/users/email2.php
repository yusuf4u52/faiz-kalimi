<?php
include('connection.php');
include('getHijriDate.php');
include '../backup/_email_backup.php';
include '../sms/_sms_automation.php';
require_once '_sendMail.php';
//include('emailmenu.php');

error_reporting(0);
$today_date = date("Y-m-d");
$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
$day = date("l", strtotime($tomorrow_date));
$hijridate = getHijriDate($tomorrow_date);

$stop_thali = mysqli_query($link, "SELECT DISTINCT `thali` FROM stop_thali WHERE `stop_date` = '" . $tomorrow_date . "'");
if ($stop_thali->num_rows > 0) {
	while ($stop = mysqli_fetch_assoc($stop_thali)) {
		$start_list = mysqli_query($link, "SELECT `id`, `Thali` FROM thalilist WHERE `Thali` = '" . $stop['thali'] . "' AND `Active` = '1' LIMIT 1");
		if ($start_list->num_rows > 0) {
			$list = $start_list->fetch_assoc();
			$update_stop = "UPDATE thalilist SET `Active` = '0', `Thali_stop_date` = '" . $hijridate . "' WHERE `Thali` = '".$list['Thali']."'";
    		mysqli_query($link,$update_stop) or die(mysqli_error($link));

			mysqli_query($link, "update change_table set processed = 1 where userid = '" . $list['id'] . "' and `Operation` in ('Start Thali','Stop Thali','Start Transport','Stop Transport') and processed = 0") or die(mysqli_error($link));
			mysqli_query($link, "INSERT INTO change_table (`Thali`, `userid`,`Operation`, `Date`) VALUES ('" . $list['Thali'] . "','" . $list['id'] . "', 'Stop Thali','" . $hijridate . "')") or die(mysqli_error($link));
		}
	}
}

$chk_stop_thali = mysqli_query($link, "SELECT DISTINCT `thali` FROM stop_thali WHERE `stop_date` = '" . $today_date . "'");
if($chk_stop_thali->num_rows > 0) {
	while ($chk_stop_list = mysqli_fetch_assoc($chk_stop_thali)) {
		$start_thali = mysqli_query($link, "SELECT DISTINCT `thali` FROM stop_thali WHERE `stop_date` = '" . $tomorrow_date . "' AND `thali` = '" . $chk_stop_list['thali'] . "'");
		if ($start_thali->num_rows <= 0) {
			$stop_list = mysqli_query($link, "SELECT `id`, `Thali` FROM thalilist WHERE `Thali` = '" . $chk_stop_list['thali'] . "' AND `Active` = '0' LIMIT 1");
			if ($stop_list->num_rows > 0) {
				$list = $stop_list->fetch_assoc();
				$update_start = "UPDATE thalilist SET `Active` = '1', `Thali_start_date` = '" . $hijridate . "' WHERE `Thali` = '".$list['Thali']."'";
				mysqli_query($link,$update_start) or die(mysqli_error($link));

				mysqli_query($link, "update change_table set processed = 1 where userid = '" . $list['id'] . "' and `Operation` in ('Start Thali','Stop Thali','Update Address', 'Change Size') and processed = 0") or die(mysqli_error($link));
				mysqli_query($link, "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES ('" . $list['Thali'] . "','" . $list['id'] . "', 'Start Thali','" . $hijridate . "')") or die(mysqli_error($link));
			}
		}
	}
}

$menu_item = mysqli_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = '" . $tomorrow_date . "' AND `menu_type` = 'thaali' LIMIT 1");
if ($menu_item->num_rows == 0) {
	echo "Skipping email as no thali on Miqaat or any other reason.";
	exit;
}

$sql = mysqli_query($link, "SELECT t.id, c.Thali, t.tiffinno, t.thalisize, t.NAME, t.CONTACT, t.Transporter,t.wingflat, t.society, t.Full_Address, c.Operation,c.id 
		from change_table as c inner 
		join thalilist as t on (c.userid = t.id) 
		WHERE c.processed = 0 ORDER BY t.Transporter");
$request = array();
$processed_ids = array();
while ($row = mysqli_fetch_assoc($sql)) {
	$request[$row['Transporter']][$row['Operation']][] = $row;
	$processed[] = $row['id'];
}
foreach ($request as $transporter_name => $thalis) {
	$msg .= "<b>" . $transporter_name . "</b>\n";
	foreach ($thalis as $operation_type => $thali_details) {
		$msg .= "<b>" . $operation_type . "</b>\n";
		foreach ($thali_details as $thaliuser) {
			$msg .= sprintf("%s - %s - %s - %s - %s - %s\n", $thaliuser['tiffinno'], $thaliuser['thalisize'], $thaliuser['NAME'], $thaliuser['CONTACT'], $thaliuser['wingflat'], $thaliuser['society']);
			$msg .= "\n";
		}
	}
	$msg .= "\n";
}

//----------------- Transporter wise count daily----------------------
$msg .= "\n<b>Transporter Count $hijridate $day - $tomorrow_date</b>\n";
$sql = mysqli_query($link, "SELECT
					(case when Transporter IS NULL then 'No Transport' else Transporter end) AS Transporter,
					count(*) as tcount,
    				sum(case when thalisize = 'Large' then 1 else 0 end) AS largecount,
					sum(case when thalisize = 'Medium' then 1 else 0 end) AS mediumcount,
					sum(case when thalisize = 'Small' then 1 else 0 end) AS smallcount,
					sum(case when thalisize = 'Mini' then 1 else 0 end) AS minicount,
					sum(case when thalisize = 'Friday' then 1 else 0 end) AS fridaycount,
					sum(case when thalisize IS NULL then 1 else 0 end) AS nullcount
					FROM `thalilist` WHERE Active = 1 group by Transporter");
$pivot = array();
$transporters = array();
while ($row = mysqli_fetch_assoc($sql)) {
	$transporters[$row['Transporter']] = 1;
	$pivot["large"][$row['Transporter']] = $row['largecount'];
	$pivot["medium"][$row['Transporter']] = $row['mediumcount'];
	$pivot["small"][$row['Transporter']] = $row['smallcount'];
	$pivot["mini"][$row['Transporter']] = $row['minicount'];
	if($day === 'Friday') {
		$row['fridaycount'] = $row['fridaycount'];
	} else {
		$row['fridaycount'] = 0;
	}
	$pivot["friday"][$row['Transporter']] = $row['fridaycount'];
	$pivot["no size"][$row['Transporter']] = $row['nullcount'];
	$pivot["total"][$row['Transporter']] = (int) $row['minicount'] + (int) $row['smallcount'] + (int) $row['mediumcount'] + (int) $row['largecount'] + (int) $row['nullcount'] + (int) $row['fridaycount'];
	$insert_sql = "REPLACE INTO transporter_daily_count (`date`, `name`,`small`,`medium`,`large`,`mini`, `friday`, `count`) VALUES ('" . $tomorrow_date . "','" . $row['Transporter'] . "', '" . $row['smallcount'] . "', '" . $row['mediumcount'] . "', '" . $row['largecount'] . "','" . $row['minicount'] . "', '" . $row['fridaycount'] . "' '" . $row['tcount'] . "')";
	mysqli_query($link, $insert_sql) or die(mysqli_error($link));
}
$transporters["total"] = 1;

//-------------------------------------------------------------------
$totalcountonsize = mysqli_query($link, "SELECT
					count(*) as tcount,
    				sum(case when thalisize = 'Large' then 1 else 0 end) AS large,
					sum(case when thalisize = 'Medium' then 1 else 0 end) AS medium,
					sum(case when thalisize = 'Small' then 1 else 0 end) AS small,
					sum(case when thalisize = 'Mini' then 1 else 0 end) AS mini,
					sum(case when thalisize = 'Friday' then 1 else 0 end) AS friday,
					sum(case when thalisize IS NULL then 1 else 0 end) AS none
					FROM `thalilist` WHERE Active = 1");

$result = mysqli_fetch_row($totalcountonsize);
$pivot["large"]["total"] = $result[1];
$pivot["medium"]["total"] = $result[2];
$pivot["small"]["total"] = $result[3];
$pivot["mini"]["total"] = $result[4];
if($day === 'Friday') {
	$result[5] = $result[5];
} else {
	$result[5] = 0;
}
$pivot["friday"]["total"] = $result[5];
$pivot["no size"]["total"] = $result[6];
$pivot["total"]["total"] = $result[0];

mysqli_query($link, "INSERT INTO daily_thali_count (`Date`, `Hijridate`, `friday`, `mini`, `small`, `medium`, `large`, `Count`) VALUES ('" . $tomorrow_date . "','" . $hijridate . "','" . $result[5] . "','" . $result[4] . "','" . $result[3] . "','" . $result[2] . "','" . $result[1] . "'," . $result[0] . ")") or die(mysqli_error($link));

mysqli_query($link, "UPDATE thalilist SET thalicount = thalicount + 1 WHERE Active='1'");
$msg = str_replace("\n", "<br>", $msg);

$pivotTable = "<table border='1' ><tr><td></td>";
foreach ($transporters as $tname => $value) {
	$pivotTable .= "<td style='padding: 2px 10px 2px 10px;'>" . $tname . "</td>";
}
$pivotTable .= "</tr>";

foreach ($pivot as $size => $tcountArr) {
	$pivotTable .= "<tr><td style='padding: 2px 10px 2px 10px;'>" . $size . "</td>";
	foreach ($transporters as $tname => $value) {
		$pivotTable .= "<td style='padding: 2px 10px 2px 10px;'>" . $tcountArr[$tname] . "</td>";
	}
	$pivotTable .= "</tr>";
}
$pivotTable .= "</table>";

$msg .= $pivotTable;

// add total registered count
$registered_but_not_active = mysqli_query($link, "SELECT * FROM thalilist WHERE Active='0' and (Transporter <> '' or Transporter is not null)");
$total_registered_thali = $pivot["total"]["total"] + mysqli_num_rows($registered_but_not_active);
$msg .= "<br><strong>Total Registered Thali: " . $total_registered_thali . "</strong>";

// send email
sendEmail('kalimimohallapoona@gmail.com', 'Start Stop update ' . $tomorrow_date, $msg, null, null, true);

mysqli_query($link, "update change_table set processed = 1 where id in (" . implode(',', $processed) . ")");

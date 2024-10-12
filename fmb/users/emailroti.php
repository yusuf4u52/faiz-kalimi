<?php

error_reporting(0);
$day = date("l", strtotime("tomorrow"));
if ($day == 'Sunday') {
	echo "Skipping email as no thali on Sunday.";
	exit;
}

if(isset($_GET['date'])) {
	$tomorrow_date = $_GET['date'];		
} else {
	$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
}

$extramsg = mysqli_query($link, "SELECT DISTINCT `Transporter` from thalilist WHERE Active = 1 AND extraRoti != 0 ORDER BY Transporter");
while ($row_extra = mysqli_fetch_assoc($extramsg)) {
	$sql = mysqli_query($link, "SELECT * from thalilist WHERE extraRoti != 0 AND `Transporter` LIKE '".$row_extra['Transporter']."'");
	$msgroti .= "<b>" . $row_extra['Transporter'] . "</b><br/>";
	while ($row = mysqli_fetch_assoc($sql)) {
		if($row['thalisize'] == 'Mini' || $row['thalisize'] == 'Small') {
			$msgroti .= "<b>". 1 + $row['extraRoti'] ." Roti</b> - ";
		} elseif($row['thalisize'] == 'Medium' || $row['thalisize'] == 'Large') {
			$msgroti .= "<b>". 2 + $row['extraRoti'] ." Roti</b> - ";
		} else {
			$msgroti .= "<b>".$row['extraRoti']." Roti</b> - ";
		}
		$msgroti .= sprintf("%s - %s - %s - %s - %s - %s<br/>", $row['tiffinno'], $row['thalisize'], $row['NAME'], $row['CONTACT'], $row['wingflat'], $row['society']);
		$msgroti .= '<br/>';
	}
	$msgroti .= '<br/>';
}

$transporter = mysqli_query($link, "SELECT DISTINCT `Transporter` from thalilist WHERE Active = 1 ORDER BY Transporter");
$transporters = array();
while ($row_trans = mysqli_fetch_assoc($transporter)) {
	$transporters[] = $row_trans['Transporter'];
}

$thaliSize = array();
$hijridate = getHijriDate($tomorrow_date);
$msgroti .= "<br/><b>Roti Count on $hijridate $day - $tomorrow_date</b><br/>";
$rotiTable = "<table border='1' ><tr><td style='padding: 2px 10px 2px 10px;'>Size</td>";
foreach ($transporters as $transporter) {
	$totalCount = 0;
	$rotiTable .= "<td style='padding: 2px 10px 2px 10px;'>" . $transporter . "</td>";

	$thaliCount = 	mysqli_query($link, "SELECT
	sum(case when thalisize = 'Mini' then 1 else 0 end) AS minicount,
	sum(case when thalisize = 'Small' then 1 else 0 end) AS smallcount,
	sum(case when thalisize = 'Medium' then 1 else 0 end) AS mediumcount,
	sum(case when thalisize = 'Large' then 1 else 0 end) AS largecount,
	sum(case when thalisize IS NULL then 1 else 0 end) AS nullcount,
	SUM(extraRoti) AS extracount
	FROM `thalilist` WHERE Active = 1 AND `Transporter` LIKE '".$transporter."'");
	$result = mysqli_fetch_row($thaliCount);
	$thaliSize["mini"][$transporter] = $result[0];
	$thaliSize["small"][$transporter] = $result[1];
	$thaliSize["medium"][$transporter] = $result[2]*2;
	$thaliSize["large"][$transporter] = $result[3]*2;
	$thaliSize["no size"][$transporter] = $result[4];
	$thaliSize["extra"][$transporter] = $result[5];
	$thaliSize["Total"][$transporter] = (int) $result['0'] + (int) $result['1'] + (int) $result['2']*2 + (int) $result['3']*2 + (int) $result['4'] + (int) $result['5'];
}
$rotiTable .= "<td style='padding: 2px 10px 2px 10px;'>Total</td></tr>";


foreach ($thaliSize as $size => $sizeCount) {
	$totalSizeCount = 0;
	$rotiTable .= "<tr><td style='padding: 2px 10px 2px 10px;'>" . $size . "</td>";
	foreach ($transporters as $transporter) {
		$totalSizeCount = $totalSizeCount + $sizeCount[$transporter];
		$rotiTable .= "<td style='padding: 2px 10px 2px 10px;'>" . $sizeCount[$transporter] . "</td>";
	}
	$rotiTable .= "<td style='padding: 2px 10px 2px 10px;'>".$totalSizeCount."</td></tr>";
}	

$rotiTable .= "</table>";

$msgroti .= $rotiTable;

$totalCount = $totalSizeCount*4;

$msgroti .= "<br/><b>Total Roti Count is $totalCount</b>";

// send email
sendEmail('kalimimohallapoona@gmail.com', 'Roti update ' . $tomorrow_date, $msgroti, null, null, true);

if(isset($_GET['date'])) {
	header("Location: /fmb/users/usermenu.php?action=send&date=" . $_GET['date']);	
}


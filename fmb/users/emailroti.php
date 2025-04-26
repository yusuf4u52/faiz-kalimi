<?php
if(isset($_GET['menu_date'])) {
	$tomorrow_date = $_GET['menu_date'];		
} else {
	$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
}

$day = date("l", strtotime($tomorrow_date));

$menu_item = mysqli_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = '" . $tomorrow_date . "' AND `menu_type` = 'thaali' LIMIT 1");
if ($menu_item->num_rows > 0) {
	$row_menu = $menu_item->fetch_assoc();
	$menu_item = unserialize($row_menu['menu_item']);
	$roti =  $menu_item['roti']['item'];
	if(!empty($roti)) {
		$mini = $menu_item['roti']['tqty'];
		$small = $menu_item['roti']['sqty'];
		$medium = $menu_item['roti']['mqty'];
		$large = $menu_item['roti']['lqty'];

		if($roti === 'Roti') {
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
		}

		$transporter = mysqli_query($link, "SELECT DISTINCT `Transporter` from thalilist WHERE Active = 1 ORDER BY Transporter");
		$transporters = array();
		while ($row_trans = mysqli_fetch_assoc($transporter)) {
			$transporters[] = $row_trans['Transporter'];
		}

		$thaliSize = array();
		$hijridate = getHijriDate($tomorrow_date);
		$msgroti .= "<br/><b>$roti Count on $hijridate $day - $tomorrow_date</b><br/>";
		$rotiTable = "<table border='1' ><tr><td style='padding: 2px 10px 2px 10px;'>Size</td>";
		foreach ($transporters as $transporter) {
			$totalCount = 0;
			$rotiTable .= "<td style='padding: 2px 10px 2px 10px;'>" . $transporter . "</td>";

			$thaliCount = 	mysqli_query($link, "SELECT
			sum(case when thalisize = 'Mini' then 1 else 0 end) AS minicount,
			sum(case when thalisize = 'Small' then 1 else 0 end) AS smallcount,
			sum(case when thalisize = 'Medium' then 1 else 0 end) AS mediumcount,
			sum(case when thalisize = 'Large' then 1 else 0 end) AS largecount,
			sum(case when thalisize = 'Friday' then 1 else 0 end) AS fridaycount,
			sum(case when thalisize IS NULL then 1 else 0 end) AS nullcount,
			SUM(extraRoti) AS extracount
			FROM `thalilist` WHERE Active = 1 AND `Transporter` LIKE '".$transporter."'");
			$result = mysqli_fetch_row($thaliCount);
			$thaliSize["mini"][$transporter] = $result[0]*$mini;
			$thaliSize["small"][$transporter] = $result[1]*$small;
			$thaliSize["medium"][$transporter] = $result[2]*$medium;
			$thaliSize["large"][$transporter] = $result[3]*$large;
			if($day === 'Friday') {
				$thaliSize["friday"][$transporter] = $result[4]*$small;
			} else {
				$thaliSize["friday"][$transporter] = 0;
			}
			$thaliSize["no size"][$transporter] = $result[5];
			if($roti === 'Roti') {
				$thaliSize["extra"][$transporter] = $result[6];
				$thaliSize["Total"][$transporter] = (int) $result[0]*$mini + (int) $result['1']*$small + (int) $result['2']*$medium + (int) $result['3']*$large + (int) $result['4'] + (int) $result['5'] + (int) $result['6'];
			} else {
				$thaliSize["Total"][$transporter] = (int) $result[0]*$mini + (int) $result['1']*$small + (int) $result['2']*$medium + (int) $result['3']*$large + (int) $result['4'] + (int) $result['5'];
			}
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

		if($roti === 'Roti') {
			$totalCount = $totalSizeCount*4;
		} elseif($roti === 'Tandoori Roti') {
			$totalCount = $totalSizeCount*2;
		} else {
			$totalCount = $totalSizeCount;
		}

		$msgroti .= "<br/><b>Total $roti Count is $totalCount</b>";

		$subject = $roti .' update ' . $tomorrow_date;

		// send email
		sendEmail('kalimimohallapoona@gmail.com', $subject, $msgroti, null, null, true);
	} else {
		echo "Tomorrow no roti.";
	}
	
} else {
	echo "Skipping email as no thali on Miqaat or any other reason.";
	exit;
}


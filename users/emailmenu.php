<?php
include ('connection.php');
include '../backup/_email_backup.php';
include '../sms/_sms_automation.php';
require_once '_sendMail.php';

error_reporting(0);
$day = date("l", strtotime("tomorrow"));
if ($day == 'Sunday') {
	echo "Skipping email as no thali on Sunday.";
	exit;
}

$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
$msgvar = '';
$menu_item = mysqli_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = '" . $tomorrow_date . "' LIMIT 1");
if ($menu_item->num_rows > 0) {
	$row_menu = $menu_item->fetch_assoc();
	$menu_item = unserialize($row_menu['menu_item']);
	$totalmenu = count($menu_item);
	$totalrows = $totalmenu + 3;
	$transporter = mysqli_query($link, "SELECT DISTINCT `Transporter` from thalilist");
	while ($row_trans = mysqli_fetch_column($transporter)) {
		$msgvar .= '<table class="table" border="1">
			<thead>
				<tr>
					<th colspan="'.$totalrows.'">'.$row_trans.'</th>
				</tr>
				<tr>
					<th>Thali No</th>
					<th>Tiffin No</th>
					<th>Tiffin Size</th>';
					if (!empty($menu_item['sabji']['item'])) {
						$msgvar .= '<th>' . $menu_item['sabji']['item'] . '</th>';
					}
					if (!empty($menu_item['tarkari']['item'])) {
						$msgvar .= '<th>' . $menu_item['tarkari']['item'] . '</th>';
					}
					if (!empty($menu_item['rice']['item'])) {
						$msgvar .= '<th>' . $menu_item['rice']['item'] . '</th>';
					}
					if (!empty($menu_item['roti']['item'])) {
						$msgvar .= '<th>' . $menu_item['roti']['item'] . '</th>';
					}
					if (!empty($menu_item['extra']['item'])) {
						$msgvar .= '<th>' . $menu_item['extra']['item'] . '</th>';
					}
				$msgvar .= '<tr>
			</thead>
			<tbody>';
				$thali = mysqli_query($link, "SELECT id, Thali, tiffinno, thalisize from thalilist WHERE `Transporter` LIKE '".$row_trans."'");
				while ($row = mysqli_fetch_assoc($thali)) {
					$user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $tomorrow_date . "' AND `thali` = '" . $row['Thali'] . "'");
					if ($user_menu->num_rows > 0) {
						$row_user = $user_menu->fetch_assoc();
						$user_menu_item = unserialize($row_user['menu_item']);
						$msgvar .= '<tr>
							<th>'.$row['Thali'].'</th>
							<th>'.$row['tiffinno'].'</th>
							<th>'.$row['thalisize'].'</th>';
							if (!empty($user_menu_item['sabji']['item'])) {
								$msgvar .= '<th>' . $user_menu_item['sabji']['qty'] . '</th>';
							}
							if (!empty($user_menu_item['tarkari']['item'])) {
								$msgvar .= '<th>' . $user_menu_item['tarkari']['qty'] . '</th>';
							}
							if (!empty($user_menu_item['rice']['item'])) {
								$msgvar .= '<th>' . $user_menu_item['rice']['qty'] . '</th>';
							}
							if (!empty($user_menu_item['roti']['item'])) {
								$msgvar .= '<th>' . $user_menu_item['roti']['qty'] . '</th>';
							}
							if (!empty($user_menu_item['extra']['item'])) {
								$msgvar .= '<th>' . $user_menu_item['extra']['qty'] . '</th>';
							}
						$msgvar .= '<tr>';
					}
				}
			$msgvar .= '</tbody>
		<table>';
	}
}

// send email
sendEmail('kalimimohallapoona@gmail.com', 'Update Menu of' . $tomorrow_date, $msgvar, null, null, true);

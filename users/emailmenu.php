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

if(isset($_GET['date'])) {
	$tomorrow_date = $_GET['date'];		
} else {
	$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
}

$msgvar = '';
$menu_item = mysqli_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = '" . $tomorrow_date . "' LIMIT 1");
if ($menu_item->num_rows > 0) {
	$msgvar .= '<table border="0" bgcolor="#F5F5F5" width="100%" cellpadding="3" cellspacing="3">
		<td align="center" valign="top">
			<table border="0" width="640" cellpadding="0" cellspacing="0" bgcolor="#F5F5F5" style="color:#333333; padding:1rem;">
				<tr>
					<td align="left">
						<img src="https://kalimijamaatpoona.org/fmb/styles/img/fmb-logo.png" alt="Faizul Mawaidil Burhaniya (Kalimi Mohalla)" width="152" height="62"> 
					</td>
					<td align="right"><strong>Updated Thali of '.date('l, dS F Y', strtotime($tomorrow_date)).'</strong></td>
				</tr>
			</table>';
			$row_menu = $menu_item->fetch_assoc();
			$menu_item = unserialize($row_menu['menu_item']);
			$thali = mysqli_query($link, "SELECT `thali` FROM user_menu WHERE `menu_date` = '" . $tomorrow_date . "' ORDER BY thali");
			if ($thali->num_rows > 0) {
				while ($row_thali = mysqli_fetch_assoc($thali)) {
					$thalino[] = $row_thali['thali']; 
				}
				$sabeelno = "'" . implode ( "', '", $thalino ) . "'";
				$transporter = mysqli_query($link, "SELECT DISTINCT `Transporter` from thalilist WHERE Active = 1 AND Thali IN (".$sabeelno.") ORDER BY Transporter");
				while ($row_trans = mysqli_fetch_assoc($transporter)) {
					$msgvar .= '<table border="0" width="640" cellpadding="3" cellspacing="3" bgcolor="#7A62D3" style="color:#FFFFFF; padding:0.5rem;margin-top:1rem;">
						<tr>
							<th align="center"><strong>'.$row_trans['Transporter'].'</strong></th>
						</tr>
					</table>
					<table width="640" cellpadding="0" cellspacing="0" border="1" bgcolor="#ffffff" style="color:#333333; padding:0.5rem;">
						<thead>
							<tr bgcolor="#7A62D3" style="color:#FFFFFF;">
								<th>Sabeel No</th>
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
							$thali = mysqli_query($link, "SELECT Thali, tiffinno, thalisize from thalilist WHERE `Transporter` LIKE '".$row_trans['Transporter']."' AND Thali IN (".$sabeelno.") AND `hardstop` != 1 AND Active != 0 ORDER BY Transporter");
							while ($row = mysqli_fetch_assoc($thali)) {
								$user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $tomorrow_date . "' AND `thali` = '" . $row['Thali'] . "' ORDER BY thali");
								if ($user_menu->num_rows > 0) {
									$row_user = $user_menu->fetch_assoc();
									$user_menu_item = unserialize($row_user['menu_item']);
									$msgvar .= '<tr>
										<td align="center">'.$row['Thali'].'</td>
										<td align="center">'.$row['tiffinno'].'</td>
										<td align="center">'.$row['thalisize'].'</td>';
										if (!empty($user_menu_item['sabji']['item'])) {
											$msgvar .= '<td align="center">' . $user_menu_item['sabji']['qty'] . '</td>';
										}
										if (!empty($user_menu_item['tarkari']['item'])) {
											$msgvar .= '<td align="center">' . $user_menu_item['tarkari']['qty'] . '</td>';
										}
										if (!empty($user_menu_item['rice']['item'])) {
											$msgvar .= '<td align="center">' . $user_menu_item['rice']['qty'] . '</td>';
										}
										if (!empty($user_menu_item['roti']['item'])) {
											$msgvar .= '<td align="center">' . $user_menu_item['roti']['qty'] . '</td>';
										}
										if (!empty($user_menu_item['extra']['item'])) {
											$msgvar .= '<td align="center">' . $user_menu_item['extra']['qty'] . '</td>';
										}
									$msgvar .= '<tr>';
								}
							}
						$msgvar .= '</tbody>
					</table>';
				}
			}
		$msgvar .= '</td>
	<table>';
}

// send email
sendEmail('kalimimohallapoona@gmail.com', 'Update Menu of' . $tomorrow_date, $msgvar, null, null, true);

if(isset($_GET['date'])) {
	header("Location: /fmb/users/usermenu.php?action=send&date=" . $_GET['date']);	
}
<?php
include ('connection.php');
include('getHijriDate.php');
require_once '_sendMail.php';
include('emailroti.php');

if(isset($_GET['menu_date'])) {
	$tomorrow_date = $_GET['menu_date'];		
} else {
	$tomorrow_date = date("Y-m-d", strtotime("+ 1 day"));
}

$day = date("l", strtotime($tomorrow_date));
$hijridate = getHijriDate($tomorrow_date);

$msgmenu = '';
$menu_item = mysqli_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = '" . $tomorrow_date . "' AND `menu_type` = 'thaali' LIMIT 1");
if ($menu_item->num_rows > 0) {
	$msgmenu .= '<table border="0" bgcolor="#FFFFFF" width="100%" cellpadding="3" cellspacing="3">
		<td align="center" valign="top">
			<table border="0" width="1140" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF" style="color:#333333; padding:1rem;">
				<tr>
					<td align="left">
						<img src="https://kalimijamaatpoona.org/fmb/styles/img/fmb-logo.png" alt="Faizul Mawaidil Burhaniya (Kalimi Mohalla)" width="152" height="62"> 
					</td>
					<td align="right"><strong>Updated Thali of '.$day .'<br/>'.$hijridate.' '.$tomorrow_date.'</strong></td>
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
					$msgmenu .= '<table border="0" width="1140" cellpadding="3" cellspacing="3" bgcolor="#778b2a" style="color:#FFFFFF; padding:0.5rem;margin-top:1rem;">
						<tr>
							<th align="center"><strong>'.$row_trans['Transporter'].'</strong></th>
						</tr>
					</table>
					<table width="1140" cellpadding="0" cellspacing="0" border="1" bgcolor="#ffffff" style="color:#333333;">
						<thead>
							<tr bgcolor="#778b2a" style="color:#FFFFFF;">
								<th width="7%">Tiffin No</th>
								<th width="7%">Tiffin Size</th>';
								if (!empty($menu_item['sabji']['item'])) {
									$msgmenu .= '<th width="7%">' . $menu_item['sabji']['item'] . '</th>';
								}
								if (!empty($menu_item['tarkari']['item'])) {
									$msgmenu .= '<th width="7%">' . $menu_item['tarkari']['item'] . '</th>';
								}
								if (!empty($menu_item['rice']['item'])) {
									$msgmenu .= '<th width="7%">' . $menu_item['rice']['item'] . '</th>';
								}
							$msgmenu .= '<th>Name</th>
								<th>Contact</th>
								<th>Flat / Society</th>
							<tr>
						</thead>
						<tbody>';
							$thali = mysqli_query($link, "SELECT id, Thali, tiffinno, `NAME`, CONTACT, thalisize, wingflat, society from thalilist WHERE `Transporter` LIKE '".$row_trans['Transporter']."' AND Thali IN (".$sabeelno.") AND `hardstop` != 1 AND Active != 0 ORDER BY Transporter");
							while ($row = mysqli_fetch_assoc($thali)) {
								$user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $tomorrow_date . "' AND `thali` = '" . $row['Thali'] . "' ORDER BY thali");
								if ($user_menu->num_rows > 0) {
									$row_user = $user_menu->fetch_assoc();
									$user_menu_item = unserialize($row_user['menu_item']);
									$msgmenu .= '<tr>
										<td align="center">'.$row['tiffinno'].'</td>
										<td align="center">'.$row['thalisize'].'</td>';
										if (!empty($user_menu_item['sabji']['item'])) {
											$msgmenu .= '<td align="center">' . $user_menu_item['sabji']['qty'] . '</td>';
										}
										if (!empty($user_menu_item['tarkari']['item'])) {
											$msgmenu .= '<td align="center">' . $user_menu_item['tarkari']['qty'] . '</td>';
										}
										if (!empty($user_menu_item['rice']['item'])) {
											$msgmenu .= '<td align="center">' . $user_menu_item['rice']['qty'] . '</td>';
										}
									$msgmenu .= '<td align="center">'.$row['NAME'].'</td>
										<td align="center">'.$row['CONTACT'].'</td>
										<td align="center">'.$row['wingflat'].' '.$row['society'].'</td>
									<tr>';
								}
							}
						$msgmenu .= '</tbody>
					</table>';
				}
			}
		$msgmenu .= '</td>
	<table>';

	// send email
	sendEmail('kalimimohallapoona@gmail.com', 'Updated Thali ' . $tomorrow_date, $msgmenu, null, null, true);

	if(isset($_GET['menu_date'])) {
		header("Location: /fmb/users/usermenu.php?action=send&date=" . $_GET['menu_date']);	
	}
} else {
	echo "Skipping email as no thali on Miqaat or any other reason.";
	exit;
}

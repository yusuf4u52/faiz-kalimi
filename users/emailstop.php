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
$stop_thali = mysqli_query($link, "SELECT * FROM stop_thali WHERE '" . $tomorrow_date . "' BETWEEN `from_date` AND `to_date`");
if ($stop_thali->num_rows > 0) {
	$msgvar .= '<table border="0" bgcolor="#F5F5F5" width="100%" cellpadding="3" cellspacing="3">
		<td align="center" valign="top">
			<table border="0" width="640" cellpadding="0" cellspacing="0" bgcolor="#F5F5F5" style="color:#333333; padding:1rem;">
				<tr>
					<td align="left">
						<img src="https://kalimijamaatpoona.org/fmb/styles/img/fmb-logo.png" alt="Faizul Mawaidil Burhaniya (Kalimi Mohalla)" width="152" height="62"> 
					</td>
					<td align="right"><strong>Stop Thali of '.date('l, dS F Y', strtotime($tomorrow_date)).'</strong></td>
				</tr>
			</table>';
			while ($row_thali = mysqli_fetch_assoc($stop_thali)) {
				$thalino[] = $row_thali['thali']; 
			}
			$sabeelno = implode(", ", $thalino);
			$transporter = mysqli_query($link, "SELECT DISTINCT `Transporter` from thalilist WHERE Thali IN (".$sabeelno.")");
			while ($row_trans = mysqli_fetch_assoc($transporter)) {
				$msgvar .= '<table border="0" width="640" cellpadding="3" cellspacing="3" bgcolor="#7A62D3" style="color:#FFFFFF; padding:0.5rem;">
					<tr>
						<th align="center"><strong>'.$row_trans['Transporter'].'</strong></th>
					</tr>
				</table>
				<table width="640" cellpadding="0" cellspacing="0" border="1" bgcolor="#ffffff" style="color:#333333; padding:0.5rem;">
					<thead>
						<tr bgcolor="#7A62D3" style="color:#FFFFFF;">
							<th>Thali No</th>
							<th>Tiffin No</th>
							<th>Tiffin Size</th>';
						$msgvar .= '<tr>
					</thead>
					<tbody>';
						$thali = mysqli_query($link, "SELECT id, Thali, tiffinno, thalisize from thalilist WHERE `Transporter` LIKE '".$row_trans['Transporter']."' AND Thali IN (".$sabeelno.")");
						while ($row = mysqli_fetch_assoc($thali)) {
							$msgvar .= '<tr>
								<td align="center">'.$row['Thali'].'</td>
								<td align="center">'.$row['tiffinno'].'</td>
								<td align="center">'.$row['thalisize'].'</td>	
							<tr>';
						}
					$msgvar .= '</tbody>
				</table>';
			}
		$msgvar .= '</td>
	<table>';
}

//echo $msgvar; die;

// send email
sendEmail('kalimimohallapoona@gmail.com', 'Stop[ Thali of' . $tomorrow_date, $msgvar, null, null, true);

if(isset($_GET['date'])) {
	header("Location: /fmb/users/usermenu.php?action=send&date=" . $_GET['date']);	
}

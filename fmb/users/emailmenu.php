<?php
include('connection.php');
require_once('helpers.php');

// Same reasoning as email2.php — this has no login check of its own and
// is meant to be reachable by a scheduled trigger, so it needs the
// cron-token-or-admin-session guard rather than a plain login requirement.
require_cron_or_admin_access($link);

include('getHijriDate.php');
require_once '_sendMail.php';
include('emailroti.php');

$tomorrow_date = $_GET['menu_date'] ?? date('Y-m-d', strtotime('+ 1 day'));
if (!DateTime::createFromFormat('Y-m-d', $tomorrow_date)) {
    http_response_code(400);
    echo "Invalid menu_date.";
    exit;
}

$day = date('l', strtotime($tomorrow_date));
$hijridate = getHijriDate($tomorrow_date);

// Returns true if the two menu arrays differ anywhere other than the
// `roti` entry. Used so roti-only customizations don't cause a thali to
// show up in this sabji/tarkari/rice distribution report.
function menuDiffersIgnoringRoti(array $baseMenu, array $customMenu): bool
{
    $base = $baseMenu;
    $custom = $customMenu;
    unset($base['roti'], $custom['roti']);

    return $base != $custom;
}

$msgmenu = '';
$menu_item_result = db_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = ? AND `menu_type` = 'thaali' LIMIT 1", "s", [$tomorrow_date]);

if ($menu_item_result->num_rows > 0) {
    $msgmenu .= '<table border="0" bgcolor="#FFFFFF" width="100%" cellpadding="3" cellspacing="3">
		<td align="center" valign="top">
			<table border="0" width="720" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF" style="color:#333333; padding:1rem;">
				<tr>
					<td align="left">
						<img src="https://kalimijamaatpoona.org/fmb/assets/img/logo.avif" alt="Faizul Mawaidil Burhaniya (Kalimi Mohalla)" width="90" height="90"> 
					</td>
					<td align="right"><strong>Updated Thali of ' . e($day) . '<br/>' . e($hijridate) . ' ' . e($tomorrow_date) . '</strong></td>
				</tr>
			</table>';

    $row_menu = $menu_item_result->fetch_assoc();
    $menu_item = decode_menu_item($row_menu['menu_item']);

    $thali = db_query($link, "SELECT `thali` FROM user_menu WHERE `menu_date` = ? ORDER BY thali", "s", [$tomorrow_date]);
    $thalino = [];
    if ($thali->num_rows > 0) {
        while ($row_thali = mysqli_fetch_assoc($thali)) {
            $thalino[] = $row_thali['thali'];
        }

        $in = build_in_clause($thalino, 's');
        $transporter = db_query(
            $link,
            "SELECT DISTINCT `Transporter` FROM thalilist WHERE Active = 1 AND id IN " . $in['sql'] . " ORDER BY Transporter",
            $in['types'],
            $in['params']
        );

        // Single upfront query instead of one user_menu lookup per thali
        // (was N+1 — a separate round trip for every thali in every
        // transporter's group). Also fixes a bug in the original query: a
        // stray backtick in the WHERE clause (`u.menu_date\` = ...`) and
        // `u.thali` wrapped in backticks as one literal identifier meant
        // that per-thali lookup could never actually match a row.
        $userMenuByThaliId = [];
        $userMenuResult = db_query(
            $link,
            "SELECT thali, menu_item FROM user_menu WHERE menu_date = ?",
            "s",
            [$tomorrow_date]
        );
        while ($row_um = mysqli_fetch_assoc($userMenuResult)) {
            $userMenuByThaliId[$row_um['thali']] = decode_menu_item($row_um['menu_item']);
        }

        while ($row_trans = mysqli_fetch_assoc($transporter)) {
            $thaliRows = db_query(
                $link,
                "SELECT id, Thali, tiffinno, `NAME`, CONTACT, thalisize, wingflat, society FROM thalilist
                 WHERE `Transporter` = ? AND id IN " . $in['sql'] . " AND `hardstop` != 1 AND Active != 0 AND thalisize != 'Roti'
                 ORDER BY Transporter, thalisize, tiffinno",
                's' . $in['types'],
                array_merge([$row_trans['Transporter']], $in['params'])
            );

            $transporterRows = '';
            while ($row = mysqli_fetch_assoc($thaliRows)) {
                $user_menu_item = $userMenuByThaliId[$row['id']] ?? null;

                if ($user_menu_item === null) {
                    continue;
                }

                // Skip this thali entirely if the only customization is
                // to the roti quantity - that's covered by emailroti.php.
                if (!menuDiffersIgnoringRoti($menu_item, $user_menu_item)) {
                    continue;
                }

				$transporterRows .= '<tr>
										<td align="center">' . e($row['tiffinno']) . '</td>
										<td align="center">' . e($row['thalisize']) . '</td>';
                if (!empty($user_menu_item['sabji']['item'])) {
                    $transporterRows .= '<td align="center">' . (float) $user_menu_item['sabji']['qty'] . '</td>';
                }
                if (!empty($user_menu_item['tarkari']['item'])) {
                    $transporterRows .= '<td align="center">' . (float) $user_menu_item['tarkari']['qty'] . '</td>';
                }
                if (!empty($user_menu_item['rice']['item'])) {
                    $transporterRows .= '<td align="center">' . (float) $user_menu_item['rice']['qty'] . '</td>';
                }
                $transporterRows .= '<td align="center">' . e($row['NAME']) . '</td>
										<td align="center">' . e($row['wingflat'] . ' ' . $row['society']) . '</td>
									<tr>';
            }

            if ($transporterRows === '') {
                continue;
            }

            $msgmenu .= '<table border="1" width="720" cellpadding="10" cellspacing="0" bgcolor="#c36d29" style="color:#FFFFFF;border-color:#548484;margin-top:1rem;">
						<tr>
							<th align="center"><strong>' . e($row_trans['Transporter']) . '</strong></th>
						</tr>
					</table>
					<table width="720" cellpadding="0" cellspacing="0" border="1" bgcolor="#ffffff" style="color:#000; border-color:#548484;">
						<thead>
							<tr bgcolor="#c36d29" style="color:#FFFFFF;">
								<th width="7%">Tiffin No</th>
								<th width="7%">Tiffin Size</th>';
            if (!empty($menu_item['sabji']['item'])) {
                $msgmenu .= '<th width="7%">' . e($menu_item['sabji']['item']) . '</th>';
            }
            if (!empty($menu_item['tarkari']['item'])) {
                $msgmenu .= '<th width="7%">' . e($menu_item['tarkari']['item']) . '</th>';
            }
            if (!empty($menu_item['rice']['item'])) {
                $msgmenu .= '<th width="7%">' . e($menu_item['rice']['item']) . '</th>';
            }
            $msgmenu .= '<th>Name</th>
								<th>Flat/Society</th>
							<tr>
						</thead>
						<tbody>' . $transporterRows . '</tbody>
					</table>';
        }
    }
    $msgmenu .= '</td>
	<table>';

    //echo $msgmenu;

    sendEmail(MENU_UPDATE_EMAILS, 'Updated Thali ' . $tomorrow_date, $msgmenu, null, null, true);

    if (isset($_GET['menu_date'])) {
        header("Location: /fmb/users/menu/edited.php?action=send&date=" . urlencode($_GET['menu_date']));
        exit;
    }
} else {
    echo "Skipping email as no thali on Miqaat or any other reason.";
    exit;
}

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

    $row_menu = $menu_item_result->fetch_assoc();
    $menu_item = decode_menu_item($row_menu['menu_item']);

    // Which item columns exist today (used for BOTH header and rows so they always align)
    $itemKeys = [];
    foreach (['sabji', 'tarkari', 'rice'] as $k) {
        if (!empty($menu_item[$k]['item'])) {
            $itemKeys[] = $k;
        }
    }

    // Fixed widths (%) so the table always fills 100% regardless of item count
    $wSr = 7; $wTiffin = 8; $wSize = 9; $wItem = 8;
    $remaining = 100 - ($wSr + $wTiffin + $wSize) - (count($itemKeys) * $wItem);
    $wFlat = (int) round($remaining * 0.45);
    $wName = $remaining - $wFlat;

    $tableStyle = 'width:720px;max-width:100%;table-layout:fixed;border-collapse:collapse;border-color:#548484;';

    $msgmenu .= '<table border="0" bgcolor="#FFFFFF" width="100%" cellpadding="3" cellspacing="3">
    <tr>
    <td align="center" valign="top">
        <table border="0" width="720" cellpadding="0" cellspacing="0" bgcolor="#FFFFFF" style="width:720px;max-width:100%;color:#333333;padding:1rem;">
            <tr>
                <td align="left">
                    <img src="https://kalimijamaatpoona.org/fmb/assets/img/logo.avif" alt="Faizul Mawaidil Burhaniya (Kalimi Mohalla)" width="90" height="90">
                </td>
                <td align="right"><strong>Updated Thali of ' . e($day) . '<br/>' . e($hijridate) . ' ' . e($tomorrow_date) . '</strong></td>
            </tr>
        </table>';

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
            $i = 0;
            while ($row = mysqli_fetch_assoc($thaliRows)) {
                $user_menu_item = $userMenuByThaliId[$row['id']] ?? null;
                if ($user_menu_item === null) {
                    continue;
                }
                if (!menuDiffersIgnoringRoti($menu_item, $user_menu_item)) {
                    continue;
                }
                $i++;

                $transporterRows .= '<tr>
                    <td align="center">' . $i . '</td>
                    <td align="center">' . e($row['tiffinno']) . '</td>
                    <td align="center">' . e($row['thalisize']) . '</td>';
                foreach ($itemKeys as $k) {
                    $qty = (float) ($user_menu_item[$k]['qty'] ?? 0);
                    $transporterRows .= '<td align="center">' . $qty . '</td>';
                }
                $transporterRows .= '<td align="center">' . e($row['wingflat'] . ' ' . $row['society']) . '</td>
                    <td align="center">' . e($row['NAME']) . '</td>
                </tr>';
            }

            if ($transporterRows === '') {
                continue;
            }

            $msgmenu .= '<table border="1" width="720" cellpadding="10" cellspacing="0" bgcolor="#c36d29" style="' . $tableStyle . 'color:#FFFFFF;margin-top:1rem;">
                <tr>
                    <th align="center"><strong>' . e($row_trans['Transporter']) . '</strong></th>
                </tr>
            </table>
            <table width="720" cellpadding="4" cellspacing="0" border="1" bgcolor="#ffffff" style="' . $tableStyle . 'color:#000;">
                <thead>
                    <tr bgcolor="#c36d29" style="color:#FFFFFF;">
                        <th width="' . $wSr . '%">Sr. No</th>
                        <th width="' . $wTiffin . '%">Tiffin No</th>
                        <th width="' . $wSize . '%">Tiffin Size</th>';
            foreach ($itemKeys as $k) {
                $msgmenu .= '<th width="' . $wItem . '%">' . e($menu_item[$k]['item']) . '</th>';
            }
            $msgmenu .= '<th width="' . $wFlat . '%">Flat/Society</th>
                        <th width="' . $wName . '%">Name</th>
                    </tr>
                </thead>
                <tbody>' . $transporterRows . '</tbody>
            </table>';
        }
    }

    $msgmenu .= '</td>
    </tr>
    </table>';

    $menuEmailSent = sendEmail(MENU_UPDATE_EMAILS, 'Updated Thali ' . $tomorrow_date, $msgmenu, null, null, true);

    if ($menuEmailSent) {
        echo "Email sent successfully.";
        if (isset($_GET['menu_date'])) {
            header("Location: /fmb/users/menu/edited.php?action=send&date=" . urlencode($_GET['menu_date']));
            exit;
        }
    } else {
        echo $msgmenu;
        die;
    }
} else {
    echo "Skipping email as no thali on Miqaat or any other reason.";
    exit;
}

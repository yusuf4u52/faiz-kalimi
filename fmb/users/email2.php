<?php
include('connection.php');
require_once('helpers.php');
include('getHijriDate.php');
require_once '_sendMail.php';
//include('emailmenu.php');

// This endpoint has no login of its own to check (it's meant to be hit by
// an unattended scheduled trigger) — without this guard it was reachable
// by anyone who found the URL, who could then force-send the daily update
// email to the whole recipient list on demand.
require_cron_or_admin_access($link);

$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
$displayMessage = static function (string $message): void {
    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "<br>\n";
};

$email2LockHandle = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fmb-email2.lock', 'c');
if ($email2LockHandle === false || !flock($email2LockHandle, LOCK_EX | LOCK_NB)) {
    if ($email2LockHandle !== false) {
        fclose($email2LockHandle);
    }
    http_response_code(409);
    $displayMessage('Daily email processing is already running. Please wait for it to finish before trying again.');
    exit;
}
register_shutdown_function(static function () use ($email2LockHandle): void {
    flock($email2LockHandle, LOCK_UN);
    fclose($email2LockHandle);
});

// Start/stop notifications can involve many separate SMTP transactions.
// Return the cron/browser response before processing the full recipient list.
ignore_user_abort(true);
set_time_limit(0);
if (!$debug && function_exists('fastcgi_finish_request')) {
    http_response_code(202);
    echo 'Daily email processing started.';
    fastcgi_finish_request();
}

error_reporting(E_ALL);
ini_set('display_errors', '0'); // don't leak DB/query details if this is ever hit over HTTP

try {
    date_default_timezone_set('Asia/Kolkata');
    $today_date = date('Y-m-d');
    $tomorrow_date = date('Y-m-d', strtotime('+ 1 day'));
    $day = date('l', strtotime($tomorrow_date));
    $hijridate = getHijriDate($tomorrow_date);

    // --- Thalis stopping tomorrow ---
    $stop_thali = db_query($link, "SELECT DISTINCT `thali` FROM stop_thali WHERE `stop_date` = ?", "s", [$tomorrow_date]);
    if ($stop_thali->num_rows > 0) {
        $stopNotifications = [];
        while ($stop = mysqli_fetch_assoc($stop_thali)) {
            try {
                $start_list = db_query(
                    $link,
                    "SELECT `id`, `Thali`, `NAME`, `Email_ID` FROM thalilist WHERE `id` = ? AND `Active` = 1 LIMIT 1",
                    "s",
                    [$stop['thali']]
                );
                if ($start_list->num_rows > 0) {
                    $list = $start_list->fetch_assoc();

                    db_query(
                        $link,
                        "UPDATE thalilist SET `Active` = 0, `Thali_stop_date` = ? WHERE `id` = ?",
                        "ss",
                        [$hijridate, $list['id']]
                    );
                    db_query(
                        $link,
                        "UPDATE change_table SET processed = 1 WHERE userid = ? AND `Operation` IN ('Start Thali','Stop Thali','Start Transport','Stop Transport') AND processed = 0",
                        "s",
                        [$list['id']]
                    );
                    db_query(
                        $link,
                        "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES (?, ?, 'Stop Thali', ?)",
                        "sss",
                        [$list['Thali'], $list['id'], $hijridate]
                    );

                    $stopNotifications[] = [
                        'to' => [$list['Email_ID']],
                        'subject' => 'Thali Stop Notification',
                        'body' => "Salaam " . e($list['NAME']) . ",<br><br>Your thali has been stopped from tomorrow till the date you selected in the FMB Website.<br><br> Note: If your thali is stopped by mistake, please whatsapp us on <a href='https://wa.me/919826932974' target='_blank'>+91 98269 32974</a><br><br>Thank you,<br>Kalimi Mohalla",
                    ];
                }
            } catch (Throwable $e) {
                error_log('[email2.php] Stop notification DB error: ' . $e->getMessage());
            }
        }
        sendEmailBatch($stopNotifications);
    }

    // --- Thalis stopped today with no stop scheduled for tomorrow => resume tomorrow ---
    // Direct stop/start actions only change thalilist.Active and never add a
    // stop_thali row, so they are deliberately excluded from this process.
    $chk_stop_thali = db_query(
        $link,
        "SELECT t.`id`, t.`Thali`, t.`NAME`, t.`Email_ID`
         FROM thalilist t
         INNER JOIN stop_thali stopped_today
             ON stopped_today.`thali` = t.`id` AND stopped_today.`stop_date` = ?
         WHERE t.`Active` = 0 AND t.`hardstop` != 1
           AND NOT EXISTS (
               SELECT 1 FROM stop_thali upcoming
               WHERE upcoming.`thali` = t.`id` AND upcoming.`stop_date` = ?
           )",
        "ss",
        [$today_date, $tomorrow_date]
    );
    if ($chk_stop_thali->num_rows > 0) {
        $startNotifications = [];
        while ($list = mysqli_fetch_assoc($chk_stop_thali)) {
            try {
                db_query(
                    $link,
                    "UPDATE thalilist SET `Active` = 1, `Thali_start_date` = ? WHERE `id` = ?",
                    "ss",
                    [$hijridate, $list['id']]
                );
                db_query(
                    $link,
                    "UPDATE change_table SET processed = 1 WHERE userid = ?
                     AND (`Operation` IN ('Start Thali','Stop Thali','Update Address','Change Size')
                          OR `Operation` LIKE 'Update Address from %'
                          OR `Operation` LIKE 'Change Size from %')
                     AND processed = 0",
                    "s",
                    [$list['id']]
                );
                db_query(
                    $link,
                    "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES (?, ?, 'Start Thali', ?)",
                    "sss",
                    [$list['Thali'], $list['id'], $hijridate]
                );

                $startNotifications[] = [
                    'to' => [$list['Email_ID']],
                    'subject' => 'Thali Start Notification',
                    'body' => "Salaam " . e($list['NAME']) . ",<br><br>Your thali has been started from tomorrow.<br><br>Note: If your thali is started by mistake or you wish to extend the period, please whatsapp us on <a href='https://wa.me/919826932974' target='_blank'>+91 98269 32974</a><br><br>Thank you,<br>Kalimi Mohalla",
                ];
            } catch (Throwable $e) {
                error_log('[email2.php] Start notification DB error: ' . $e->getMessage());
            }
        }
        sendEmailBatch($startNotifications);
    }

    // --- Daily change/thali-count report for tomorrow ---
    $menu_item = db_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = ? AND `menu_type` = 'thaali'", "s", [$tomorrow_date]);

    if ($menu_item->num_rows > 0) {
        $sql = db_query(
            $link,
            "SELECT t.id, c.Thali, t.tiffinno, t.thalisize, t.NAME, t.CONTACT, t.Transporter, t.wingflat, t.society, t.Full_Address, c.Operation, c.id AS change_id
             FROM change_table AS c
             INNER JOIN thalilist AS t ON (c.userid = t.id)
             WHERE c.processed = 0
             ORDER BY t.Transporter, t.thalisize, t.tiffinno"
        );

        $request = [];
        $processed = [];
        $msg = '<h3>Start Stop update for ' . e($tomorrow_date) . ' - ' . e($hijridate) . ' - ' . e($day) . "</h3>\n";
        $transporterDailyRows = [];
        $dailyThaliCountRow = null;

        while ($row = mysqli_fetch_assoc($sql)) {
            $request[$row['Transporter']][$row['Operation']][] = $row;
            $processed[] = (int) $row['change_id'];
        }

        foreach ($request as $transporter_name => $thalis) {
            $msg .= "<b>" . e((string) $transporter_name) . "</b>\n";
            foreach ($thalis as $operation_type => $thali_details) {
                $msg .= "<b>" . e($operation_type) . "</b>\n";
                foreach ($thali_details as $thaliuser) {
                    $msg .= sprintf(
                        "%s - %s - %s - %s - %s - %s\n",
                        e($thaliuser['tiffinno']),
                        e($thaliuser['thalisize']),
                        e($thaliuser['NAME']),
                        e($thaliuser['CONTACT']),
                        e($thaliuser['wingflat']),
                        e($thaliuser['society'])
                    );
                    $msg .= "\n";
                }
            }
            $msg .= "\n";
        }

        // Check if tomorrow's menu has roti
        $hasRoti = false;
        $menuCheck = db_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = ? AND `menu_type` = 'thaali' LIMIT 1", "s", [$tomorrow_date]);
        if ($menuCheck->num_rows > 0) {
            $menuRow = $menuCheck->fetch_assoc();
            $menuData = decode_menu_item($menuRow['menu_item']);
            if (strcasecmp(trim((string) ($menuData['roti']['item'] ?? '')), 'Roti') === 0) {
                $hasRoti = true;
            }
        }

        //----------------- Transporter wise count daily----------------------
        $msg .= "\n<b>Transporter Count " . e($hijridate) . " " . e($day) . " - " . e($tomorrow_date) . "</b>\n";
        $sql = db_query(
            $link,
            "SELECT
                (CASE WHEN Transporter IS NULL THEN 'No Transport' ELSE Transporter END) AS Transporter,
                COUNT(*) AS tcount,
                SUM(CASE WHEN thalisize = 'Large' THEN 1 ELSE 0 END) AS largecount,
                SUM(CASE WHEN thalisize = 'Medium' THEN 1 ELSE 0 END) AS mediumcount,
                SUM(CASE WHEN thalisize = 'Small' THEN 1 ELSE 0 END) AS smallcount,
                SUM(CASE WHEN thalisize = 'Mini' THEN 1 ELSE 0 END) AS minicount,
                SUM(CASE WHEN thalisize = 'Friday' THEN 1 ELSE 0 END) AS fridaycount,
                SUM(CASE WHEN thalisize = 'Roti' THEN 1 ELSE 0 END) AS roticount,
                SUM(CASE WHEN thalisize = 'Barnamaj' THEN 1 ELSE 0 END) AS barnamajcount,
                SUM(CASE WHEN thalisize IS NULL THEN 1 ELSE 0 END) AS nullcount
             FROM `thalilist` WHERE Active = 1 GROUP BY Transporter"
        );

        $pivot = [];
        $transporters = [];
        while ($row = mysqli_fetch_assoc($sql)) {
            $transporters[$row['Transporter']] = 1;
            $pivot["large"][$row['Transporter']] = $row['largecount'];
            $pivot["medium"][$row['Transporter']] = $row['mediumcount'];
            $pivot["small"][$row['Transporter']] = $row['smallcount'];
            $pivot["mini"][$row['Transporter']] = $row['minicount'];
            $pivot["friday"][$row['Transporter']] = $row['fridaycount'];
            if ($hasRoti) {
                $pivot["roti"][$row['Transporter']] = $row['roticount'];
            }
            $pivot["barnamaj"][$row['Transporter']] = $row['barnamajcount'];
            $pivot["no size"][$row['Transporter']] = $row['nullcount'];
            $pivot["total"][$row['Transporter']] = (int) $row['minicount'] + (int) $row['smallcount'] + (int) $row['mediumcount']
                + (int) $row['largecount'] + (int) $row['nullcount'] + (int) $row['fridaycount'] + (int) $row['barnamajcount']
                + ($hasRoti ? (int) $row['roticount'] : 0);
            $transporterDailyRows[] = $row;
        }
        $transporters["total"] = 1;

        //-------------------------------------------------------------------
        $totalcountonsize = db_query(
            $link,
            "SELECT
                COUNT(*) AS tcount,
                SUM(CASE WHEN thalisize = 'Large' THEN 1 ELSE 0 END) AS large,
                SUM(CASE WHEN thalisize = 'Medium' THEN 1 ELSE 0 END) AS medium,
                SUM(CASE WHEN thalisize = 'Small' THEN 1 ELSE 0 END) AS small,
                SUM(CASE WHEN thalisize = 'Mini' THEN 1 ELSE 0 END) AS mini,
                SUM(CASE WHEN thalisize = 'Friday' THEN 1 ELSE 0 END) AS friday,
                SUM(CASE WHEN thalisize = 'Roti' THEN 1 ELSE 0 END) AS roti,
                SUM(CASE WHEN thalisize = 'Barnamaj' THEN 1 ELSE 0 END) AS barnamaj,
                SUM(CASE WHEN thalisize IS NULL THEN 1 ELSE 0 END) AS none
             FROM `thalilist` WHERE Active = 1"
        );

        $result = mysqli_fetch_row($totalcountonsize);
        $pivot["large"]["total"] = $result[1];
        $pivot["medium"]["total"] = $result[2];
        $pivot["small"]["total"] = $result[3];
        $pivot["mini"]["total"] = $result[4];
        $pivot["friday"]["total"] = $result[5];
        if ($hasRoti) {
            $pivot["roti"]["total"] = $result[6];
        }
        $pivot["barnamaj"]["total"] = $result[7];
        $pivot["no size"]["total"] = $result[8];
        $pivot["total"]["total"] = $result[0];
        $dailyThaliCountRow = $result;

        // Count one thaali day for every currently active user. The date marker
        // prevents a repeated run of this scheduled script from counting the
        // same menu day twice.
        $countMarkerPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fmb-email2-count-' . $tomorrow_date . '.sent';
        $countMarkerHandle = @fopen($countMarkerPath, 'x');
        if ($countMarkerHandle !== false) {
            fclose($countMarkerHandle);
            try {
                db_query(
                    $link,
                    "UPDATE `thalilist`
                     SET `thalicount` = COALESCE(`thalicount`, 0) + 1
                     WHERE `Active` = 1
                       AND EXISTS (
                           SELECT 1 FROM `menu_list`
                           WHERE `menu_type` = 'thaali' AND `menu_date` = ?
                       )",
                    "s",
                    [$tomorrow_date]
                );
            } catch (Throwable $e) {
                @unlink($countMarkerPath);
                throw $e;
            }
        }
        $msg = str_replace("\n", "<br>", $msg);

        $pivotTable = "<table border='1'><tr><td></td>";
        foreach ($transporters as $tname => $value) {
            $pivotTable .= "<td style='padding: 2px 10px 2px 10px;'>" . e((string) $tname) . "</td>";
        }
        $pivotTable .= "</tr>";

        foreach ($pivot as $size => $tcountArr) {
            $pivotTable .= "<tr><td style='padding: 2px 10px 2px 10px;'>" . e($size) . "</td>";
            foreach ($transporters as $tname => $value) {
                $pivotTable .= "<td style='padding: 2px 10px 2px 10px;'>" . e((string) ($tcountArr[$tname] ?? 0)) . "</td>";
            }
            $pivotTable .= "</tr>";
        }
        $pivotTable .= "</table>";

        $msg .= $pivotTable;

        // add total registered count
        $registered_but_not_active = db_query(
            $link,
            "SELECT COUNT(*) AS cnt FROM thalilist WHERE Active = 0 AND (Transporter <> '' OR Transporter IS NOT NULL)"
        );
        $registeredNotActiveCount = (int) (mysqli_fetch_assoc($registered_but_not_active)['cnt'] ?? 0);
        $total_registered_thali = $pivot["total"]["total"] + $registeredNotActiveCount;
        $msg .= "<br><strong>Total Registered Thali: " . e((string) $total_registered_thali) . "</strong>";

        $mailSent = false;
        if (!empty($processed) || !empty($request)) {
            $mailSent = sendEmail(DAILY_UPDATE_EMAILS, 'Start Stop update ' . $tomorrow_date, $msg, null, null, true);
        }

        if ($mailSent) {
            $displayMessage('Daily start/stop email sent successfully.');

            foreach ($transporterDailyRows as $row) {
                // ON DUPLICATE KEY UPDATE instead of REPLACE INTO: REPLACE
                // silently does a DELETE+INSERT under the hood, which churns
                // the auto-increment id on every re-run, would cascade-delete
                // any FK-dependent rows, and skips any ON UPDATE triggers.
                // Requires the UNIQUE KEY (date, name) from
                // schema-modernization.sql (already implied — REPLACE INTO
                // only works at all if such a key already exists).
                db_query(
                    $link,
                    "INSERT INTO transporter_daily_count (`date`, `name`, `small`, `medium`, `large`, `mini`, `friday`, `roti`, `barnamaj`, `count`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        `small` = VALUES(`small`), `medium` = VALUES(`medium`), `large` = VALUES(`large`),
                        `mini` = VALUES(`mini`), `friday` = VALUES(`friday`), `roti` = VALUES(`roti`),
                        `barnamaj` = VALUES(`barnamaj`), `count` = VALUES(`count`)",
                    "ssiiiiiiii",
                    [
                        $tomorrow_date,
                        $row['Transporter'],
                        (int) $row['smallcount'],
                        (int) $row['mediumcount'],
                        (int) $row['largecount'],
                        (int) $row['minicount'],
                        (int) $row['fridaycount'],
                        (int) $row['roticount'],
                        (int) $row['barnamajcount'],
                        (int) $row['tcount'],
                    ]
                );
            }

            if ($dailyThaliCountRow !== null) {
                // Was a plain INSERT with no conflict handling, so re-running
                // this script for a date that already has a row (e.g. a
                // manual re-trigger after a partial failure) would throw a
                // duplicate-key error instead of just refreshing the counts.
                // ON DUPLICATE KEY UPDATE makes it idempotent; relies on the
                // UNIQUE KEY (Date) added in schema-modernization.sql.
                db_query(
                    $link,
                    "INSERT INTO daily_thali_count (`Date`, `Hijridate`, `barnamaj`, `roti`, `friday`, `mini`, `small`, `medium`, `large`, `Count`)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE
                        `Hijridate` = VALUES(`Hijridate`), `barnamaj` = VALUES(`barnamaj`), `roti` = VALUES(`roti`),
                        `friday` = VALUES(`friday`), `mini` = VALUES(`mini`), `small` = VALUES(`small`),
                        `medium` = VALUES(`medium`), `large` = VALUES(`large`), `Count` = VALUES(`Count`)",
                    "ssiiiiiiii",
                    [
                        $tomorrow_date,
                        $hijridate,
                        (int) $dailyThaliCountRow[7],
                        (int) $dailyThaliCountRow[6],
                        (int) $dailyThaliCountRow[5],
                        (int) $dailyThaliCountRow[4],
                        (int) $dailyThaliCountRow[3],
                        (int) $dailyThaliCountRow[2],
                        (int) $dailyThaliCountRow[1],
                        (int) $dailyThaliCountRow[0],
                    ]
                );
            }

            if (!empty($processed)) {
                $in = build_in_clause($processed, 'i');
                db_query($link, "UPDATE change_table SET processed = 1 WHERE id IN " . $in['sql'], $in['types'], $in['params']);
            }
        } else {
            $displayMessage('Daily transporter update email failed: ' . ($GLOBALS['lastSendEmailError'] ?? 'Unknown email error'));
        }

            // Send the edited-menu and transporter-wise roti reports after the
            // scheduled stop/start changes above have updated Active status.
            //include __DIR__ . '/emailmenu.php';
    } else {
        $displayMessage('No thaali menu found for ' . $tomorrow_date . '; sending skip notice instead of transporter update.');
        $skipMarkerPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fmb-email2-skip-' . $tomorrow_date . '.sent';
        $skipMarkerHandle = @fopen($skipMarkerPath, 'x');
        if ($skipMarkerHandle === false) {
            $displayMessage('Skip notice already sent for ' . $tomorrow_date . '.');
        } else {
            fclose($skipMarkerHandle);
            $skipmsg = "Skipping email as no thali available for " . e($tomorrow_date) . ".";
            $smailSent = sendEmail(SKIP_NOTICE_EMAILS, 'No Thaali Update ' . $tomorrow_date, $skipmsg, null, null, true);
            if (!$smailSent) {
                @unlink($skipMarkerPath);
                $displayMessage('Skip notice email failed: ' . ($GLOBALS['lastSendEmailError'] ?? 'Unknown email error'));
            } else {
                echo 'Skip Email';
            }
        }
    }
} catch (Throwable $e) {
    $displayMessage('Error: ' . $e->getMessage());
    echo "An error occurred while processing the daily update.";
}

// Keep the database backup independent from the daily start/stop workflow.
// The backup script reports its own success or failure and runs after the
// notification work has completed.
//require_once __DIR__ . '/../backup/_email_backup.php';

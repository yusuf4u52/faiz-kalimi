<?php
/**
 * Shared file-based queue for the pending-hoob reminder emails.
 * Included by email.php (browser) and email-worker.php (cron). Produces no output.
 *
 * Design rules:
 *  - No database. State is one JSON file per report per day.
 *  - Nothing here ever runs longer than the time budget it is given, so a web
 *    request can never hit the gateway timeout (the cause of the 504).
 *  - A member is marked 'sent' only after SMTP accepted the message, and a
 *    'sent' member is never picked again — resuming can't double-send.
 *  - A corrupt queue file is an error, never silently rebuilt (a rebuild would
 *    re-email everyone).
 */

require_once __DIR__ . '/../connection.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../_sendMail.php';

date_default_timezone_set('Asia/Kolkata');

/* ------------------------------ settings ------------------------------ */

// Where the JSON files live. '' = <fmb root>/storage/email-queue.
// Better: a folder OUTSIDE public_html, e.g. '/home/u123456/fmb-email-queue'.
// Must be the same folder for the browser and for cron.
const QUEUE_STORAGE_DIR = '';

const QUEUE_CHUNK_SIZE   = 5;   // messages per SMTP connection / per save
const QUEUE_MAX_ATTEMPTS = 6;   // per member; backoff 2,4,8,16,32 min between tries
const QUEUE_MAX_PER_HOUR = 0;   // 0 = no cap. Set e.g. 100 if your host limits mails/hour.
const QUEUE_SUBJECT      = 'Reminder - FMB Hoob Pending';

/* ------------------------------ reports ------------------------------ */

function queueReports(): array
{
    return [
        'local' => [
            'title' => 'Local Current Year Pending Hoob',
            'where' => '(Previous_Due + yearly_hub - Paid) > 5 AND thalisize IS NOT NULL AND hardstop != 1 AND yearly_hub > 3 AND sabeelType = \'Kalimi ITS\'',
        ],
        'non-local' => [
            'title' => 'Non Local Current Year Pending Hoob',
            'where' => '(Previous_Due + yearly_hub - Paid) > 5 AND thalisize IS NOT NULL AND hardstop != 1 AND yearly_hub > 5 AND sabeelType != \'Kalimi ITS\'',
        ],
        'previous' => [
            'title' => 'Previous Year Pending Hoob',
            'where' => 'Previous_Due > 5 AND thalisize IS NOT NULL AND hardstop != 1 AND yearly_hub > 0',
        ],
    ];
}

/* ------------------------------ storage ------------------------------ */

function queueDir(): string
{
    $dir = QUEUE_STORAGE_DIR !== '' ? QUEUE_STORAGE_DIR : __DIR__ . '/../storage/email-queue';
    if (!is_dir($dir) && !mkdir($dir, 0770, true) && !is_dir($dir)) {
        throw new RuntimeException('Cannot create the queue folder: ' . $dir . '. Create it and make it writable by PHP.');
    }
    if (!is_writable($dir)) {
        throw new RuntimeException('The queue folder is not writable by PHP: ' . $dir);
    }
    $htaccess = $dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\n"); // Apache/LiteSpeed only; nginx ignores it
    }
    return $dir;
}

/**
 * File names carry a secret-derived token so they can't be guessed and fetched
 * over the web (nginx ignores .htaccess). Best protection is still to keep the
 * folder outside public_html via QUEUE_STORAGE_DIR.
 */
function queueToken(): string
{
    return substr(hash_hmac('sha256', 'fmb-email-queue', (string) SMTP_PASS . (string) SMTP_USER), 0, 16);
}

function queuePathFor(string $report, string $date): string
{
    return queueDir() . '/' . $report . '-' . $date . '-' . queueToken() . '.json';
}

function queueLockPath(string $report, string $suffix = ''): string
{
    return queueDir() . '/' . $report . $suffix . '.lock';
}

/** Today's file, or yesterday's if it is still unfinished (run crossed midnight). */
function queueActivePath(string $report): string
{
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    $todayPath = queuePathFor($report, $today);
    if (is_file($todayPath)) {
        return $todayPath;
    }
    $yesterdayPath = queuePathFor($report, $yesterday);
    if (is_file($yesterdayPath)) {
        $old = queueLoad($yesterdayPath);
        if ($old !== null && ($old['status'] ?? '') === 'in_progress') {
            return $yesterdayPath;
        }
    }
    return $todayPath;
}

function queueLoad(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    $data = $raw === false ? null : json_decode($raw, true);
    if (!is_array($data) || !isset($data['members']) || !is_array($data['members'])) {
        throw new RuntimeException(
            'Queue file ' . basename($path) . ' is unreadable. It was NOT rebuilt, to avoid emailing people twice. ' .
            'Inspect it, or delete it only if you are sure nothing was sent.'
        );
    }
    return $data;
}

function queueSave(string $path, array $campaign): void
{
    $json = json_encode($campaign, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        throw new RuntimeException('Could not encode the queue file: ' . json_last_error_msg());
    }
    $tmp = $path . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false || !rename($tmp, $path)) {
        throw new RuntimeException('Could not write the queue file: ' . basename($path));
    }
}

function queuePrune(int $keepDays = 30): void
{
    foreach (glob(queueDir() . '/*.json') ?: [] as $file) {
        if (filemtime($file) < time() - $keepDays * 86400) {
            @unlink($file);
        }
    }
}

/* ------------------------------ email content ------------------------------ */

function buildReminderEmail(array $member): string
{
    $name = e((string) $member['name']);
    $itsNumber = e((string) $member['its']);
    $sabeelNumber = e((string) $member['sabeel']);
    $pendingAmount = number_format((float) $member['pending']);
    $previousDue = (float) $member['previous_due'];

    $previousDueMessage = '';
    if ($previousDue > 0) {
        $previousDueMessage =
            '<p>Aapni pichla Waras ni due <strong>₹' . number_format($previousDue) .
            '</strong> haji pan pending che. Aap si adab sathe iltemaas che ane ummeed ke Aqa Maula T.U.S na Milad Mubarak na pehla aa due ada kari ne clear kari aapso.</p>';
    }

    return "
        <div style=\"font-family:Arial,sans-serif;color:#333;max-width:640px;margin:auto\">
            <img src=\"https://kalimijamaatpoona.org/fmb/assets/img/logo.avif\" alt=\"FMB Kalimi Mohalla\" width=\"90\" height=\"90\" style=\"display:block;margin:0 auto 20px\">
            <p>Salaam <strong>{$name}</strong>,</p>
            <p><strong>Reminder - FMB Hoob Pending</strong></p>
            <p>Aapna ghare <strong>Faiz ul Mawaid il Burhaniyah</strong> ni barakat pohchi rahi che. Aapni FMB ni hoob baki che, je ni tafseel niche aapi che.</p>
            <table cellpadding=\"8\" cellspacing=\"0\" style=\"border-collapse:collapse;width:100%;border:1px solid #ddd\">
                <tr><td style=\"border:1px solid #ddd\"><strong>Name</strong></td><td style=\"border:1px solid #ddd\">{$name}</td></tr>
                <tr><td style=\"border:1px solid #ddd\"><strong>ITS Number</strong></td><td style=\"border:1px solid #ddd\">{$itsNumber}</td></tr>
                <tr><td style=\"border:1px solid #ddd\"><strong>Sabeel Number</strong></td><td style=\"border:1px solid #ddd\">{$sabeelNumber}</td></tr>
                <tr><td style=\"border:1px solid #ddd\"><strong>Pending Amount</strong></td><td style=\"border:1px solid #ddd\">₹ {$pendingAmount}</td></tr>
            </table>
            {$previousDueMessage}
            <p>FMB ni hoob ada kerva ni <strong>8th Miqaat - Al-Hayyul Muqaddas Syedna Mohammed Burhanuddin (RA)</strong> na <strong>Milad Mubarak</strong> no din gujri chuko che. Aap Maula (T.U.S) ni nazarat ane barakat si <strong>Faiz ul Mawaid il Burhaniyah</strong> no aa amal jaari che ane ghana gharo sudhi Faiz ni barkat pohchi rahi che.</p>
            <p>Aap si adab sathe iltemaas che ke aap aapni pending FMB ni hoob ada kari ne aa khidmat ma ane <strong>Burhanuddin Maula (RA)</strong> na faiz ma shamil thaiye ane <strong>Mufaddal Maula (T.U.S)</strong> ni khushi haasil kariye. Aapna waqt pe hoob ada kerva si <strong>Faiz</strong> ane aa khidmat nu nizam barabar chaltu rahe che.</p>
            <p>Payment kara pachi screenshot ya receipt <a href=\"https://wa.me/917499860950\"><strong>+91 74998 60950</strong></a> par aapna Sabeel Number <strong>{$sabeelNumber}</strong> saathe mokli aapso, taake hame aapna records update kari shakay.</p>
            <p><strong>Note:</strong> Agar aap aa hoob pehla thi ada kari chuka ho, to payment ni receipt ya transfer nu screenshot hamne mokli aapso, taake hame records update kari shakay.</p>
            <p>Was Salaam,<br><strong>FMB Khidmat Team</strong></p>
            <p style=\"text-align:center;margin:24px 0 12px\"><img src=\"https://kalimijamaatpoona.org/fmb/assets/img/fmb-account.avif\" alt=\"FMB payment details\" width=\"900\" style=\"display:block;width:100%;max-width:900px;height:auto;margin:0 auto\"></p>
            <p style=\"text-align:center;margin:12px 0 24px\"><a href=\"upi://pay?pa=dbjt-fmb-kalimi@ybl&amp;pn=D%20B%20J%20T%20TRUST%20K%20M%20POONA%20-%20FMB&amp;cu=INR\" style=\"display:inline-block;background:#198754;color:#fff;padding:12px 28px;text-decoration:none;border-radius:4px;font-weight:bold\">Pay Now</a></p>
        </div>";
}

/* ------------------------------ campaign lifecycle ------------------------------ */

function queueBuild(string $report, array $reportDef, $link): array
{
    $members = db_query($link, "SELECT NAME, ITS_No, Thali, Email_ID, SEmail_ID, Previous_Due,
            (Previous_Due + yearly_hub - Paid) AS Total_Pending
        FROM thalilist WHERE {$reportDef['where']}
        ORDER BY Total_Pending DESC, ITS_No");

    $rows = [];
    $noEmail = 0;

    while ($m = mysqli_fetch_assoc($members)) {
        $recipients = [];
        foreach ([$m['Email_ID'], $m['SEmail_ID']] as $email) {
            $email = trim((string) $email);
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients, true)) {
                $recipients[] = $email;
            }
        }
        if (!$recipients) {
            $noEmail++;
            continue;
        }

        $its = (string) $m['ITS_No'];
        $rows[$its] = [
            'name' => (string) $m['NAME'],
            'its' => $its,
            'sabeel' => (string) $m['Thali'],
            'pending' => (float) ($m['Total_Pending'] ?? 0),
            'previous_due' => (float) ($m['Previous_Due'] ?? 0),
            'recipients' => $recipients,
            'status' => 'pending',      // pending | sent | failed
            'attempts' => 0,
            'next_attempt_at' => 0,     // unix time; 0 = due now
            'last_error' => null,
            'sent_at' => null,
        ];
    }

    // The previous version of this feature kept <report>-<date>.json (no token in the
    // name). Anyone it already emailed today is carried over as 'sent' so a switch
    // of versions in the middle of the day never emails the same person twice.
    $imported = 0;
    $legacyPath = queueDir() . '/' . $report . '-' . date('Y-m-d') . '.json';
    if (is_file($legacyPath)) {
        $legacy = json_decode((string) file_get_contents($legacyPath), true);
        foreach (($legacy['members'] ?? []) as $legacyIts => $legacyMember) {
            $key = (string) $legacyIts;
            if (($legacyMember['status'] ?? '') === 'sent' && isset($rows[$key])) {
                $rows[$key]['status'] = 'sent';
                $rows[$key]['sent_at'] = 'imported from earlier version';
                $imported++;
            }
        }
    }

    return [
        'report' => $report,
        'date' => date('Y-m-d'),
        'created_at' => date('c'),
        'status' => 'in_progress',      // in_progress | done
        'imported_sent' => $imported,
        'no_valid_email' => $noEmail,
        'cooldown_until' => 0,
        'consecutive_bad' => 0,
        'hour_key' => '',
        'hour_sent' => 0,
        'members' => $rows,
    ];
}

/**
 * Returns [campaign, created]. Creates today's campaign on the first call of the
 * day, otherwise returns the existing one untouched (never rebuilt, never reset).
 * Fast: no sending happens here.
 */
function queueStart(string $report, $link): array
{
    $reports = queueReports();
    $lock = fopen(queueLockPath($report, '.create'), 'c');
    if ($lock === false) {
        throw new RuntimeException('Cannot open the queue lock file.');
    }
    flock($lock, LOCK_EX); // held only while the snapshot is written (a second or two)
    try {
        $path = queueActivePath($report);
        $campaign = queueLoad($path);
        $created = false;
        if ($campaign === null) {
            $campaign = queueBuild($report, $reports[$report], $link);
            queueSave($path, $campaign);
            $created = true;
            queuePrune();
        }
        return [$campaign, $created];
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function queueSecondsToNextHour(): int
{
    return max(1, strtotime(date('Y-m-d H:00:00', time() + 3600)) - time());
}

function queueSummary(array $campaign): array
{
    $sent = $failed = $pending = 0;
    $nextAt = null;
    $errors = [];
    foreach ($campaign['members'] as $m) {
        if ($m['status'] === 'sent') {
            $sent++;
        } elseif ($m['status'] === 'failed') {
            $failed++;
        } else {
            $pending++;
            $at = (int) ($m['next_attempt_at'] ?? 0);
            $nextAt = $nextAt === null ? $at : min($nextAt, $at);
        }
        if (!empty($m['last_error']) && $m['status'] !== 'sent' && count($errors) < 3 && !in_array($m['last_error'], $errors, true)) {
            $errors[] = $m['last_error'];
        }
    }

    $now = time();
    $wait = 0;
    if ($pending > 0) {
        $wait = max(0, (int) $nextAt - $now);
        $wait = max($wait, (int) ($campaign['cooldown_until'] ?? 0) - $now);
        if (QUEUE_MAX_PER_HOUR > 0
            && ($campaign['hour_key'] ?? '') === date('Y-m-d H')
            && (int) ($campaign['hour_sent'] ?? 0) >= QUEUE_MAX_PER_HOUR) {
            $wait = max($wait, queueSecondsToNextHour());
        }
    }

    return [
        'total' => count($campaign['members']),
        'sent' => $sent,
        'failed' => $failed,
        'pending' => $pending,
        'no_valid_email' => (int) ($campaign['no_valid_email'] ?? 0),
        'imported' => (int) ($campaign['imported_sent'] ?? 0),
        'status' => $campaign['status'],
        'date' => $campaign['date'],
        'wait' => $wait,
        'errors' => $errors,
    ];
}

/**
 * Send for at most $budgetSeconds, then return. Safe to call from the browser
 * (small budget) and from cron (bigger budget); an exclusive lock guarantees only
 * one of them sends at a time. Unattempted members simply stay pending.
 */
function queueProcess(string $report, float $budgetSeconds): array
{
    $path = queueActivePath($report);

    $lock = fopen(queueLockPath($report), 'c');
    if ($lock === false) {
        throw new RuntimeException('Cannot open the queue lock file.');
    }
    if (!flock($lock, LOCK_EX | LOCK_NB)) {
        fclose($lock);
        $existing = queueLoad($path);
        return ['busy' => true, 'exists' => $existing !== null] + ($existing !== null ? queueSummary($existing) : []);
    }

    try {
        $campaign = queueLoad($path);
        if ($campaign === null) {
            return ['exists' => false];
        }
        if ($campaign['status'] === 'done') {
            return ['exists' => true, 'sent_now' => 0] + queueSummary($campaign);
        }

        $deadline = microtime(true) + $budgetSeconds;
        $sentNow = 0;
        $throttled = false;

        while (microtime(true) < $deadline) {
            $now = time();

            if ((int) ($campaign['cooldown_until'] ?? 0) > $now) {
                break; // SMTP looked unhealthy a moment ago; let it rest
            }

            $take = QUEUE_CHUNK_SIZE;
            $hourKey = date('Y-m-d H');
            if (($campaign['hour_key'] ?? '') !== $hourKey) {
                $campaign['hour_key'] = $hourKey;
                $campaign['hour_sent'] = 0;
            }
            if (QUEUE_MAX_PER_HOUR > 0) {
                $room = QUEUE_MAX_PER_HOUR - (int) $campaign['hour_sent'];
                if ($room <= 0) {
                    $throttled = true;
                    break;
                }
                $take = min($take, $room);
            }

            $chunk = [];
            foreach ($campaign['members'] as $its => $m) {
                if ($m['status'] === 'pending' && (int) $m['next_attempt_at'] <= $now) {
                    $chunk[] = (string) $its;
                    if (count($chunk) >= $take) {
                        break;
                    }
                }
            }
            if (!$chunk) {
                break; // nothing due (everything left is backing off)
            }

            $messages = [];
            foreach ($chunk as $its) {
                $messages[$its] = [
                    'to' => $campaign['members'][$its]['recipients'],
                    'subject' => QUEUE_SUBJECT,
                    'body' => buildReminderEmail($campaign['members'][$its]),
                ];
            }

            $results = sendEmailBatch($messages, 2, $deadline);

            $attempted = 0;
            $transientFailures = 0;
            $chunkSent = 0;
            foreach ($chunk as $its) {
                if (!isset($results[$its])) {
                    continue; // deadline hit before this one: stays pending, no attempt counted
                }
                $res = $results[$its];
                $attempted++;
                $member = &$campaign['members'][$its];
                $member['attempts']++;

                if ($res['ok']) {
                    $member['status'] = 'sent';
                    $member['sent_at'] = date('c');
                    $member['last_error'] = null;
                    $sentNow++;
                    $chunkSent++;
                    $campaign['hour_sent'] = (int) $campaign['hour_sent'] + 1;
                } elseif ($res['retry'] && $member['attempts'] < QUEUE_MAX_ATTEMPTS) {
                    $transientFailures++;
                    $member['last_error'] = $res['error'];
                    $member['next_attempt_at'] = time() + min(3600, 120 * (2 ** ($member['attempts'] - 1)));
                } else {
                    $member['status'] = 'failed';
                    $member['last_error'] = $res['error'];
                }
                unset($member);
            }

            // If everything we just tried failed transiently, SMTP is unhealthy
            // (rate limit, outage). Stop hammering it and rest before the next try.
            if ($attempted >= 2 && $transientFailures === $attempted) {
                $campaign['consecutive_bad'] = (int) $campaign['consecutive_bad'] + 1;
                $campaign['cooldown_until'] = time() + min(900, 60 * (2 ** ($campaign['consecutive_bad'] - 1)));
            } elseif ($chunkSent > 0) {
                $campaign['consecutive_bad'] = 0;
            }

            queueSave($path, $campaign);

            if ($attempted === 0) {
                break; // deadline reached before anything was attempted
            }
            if ((int) $campaign['cooldown_until'] > time()) {
                break;
            }
        }

        $stillPending = false;
        foreach ($campaign['members'] as $m) {
            if ($m['status'] === 'pending') {
                $stillPending = true;
                break;
            }
        }
        if (!$stillPending) {
            $campaign['status'] = 'done';
            $campaign['finished_at'] = date('c');
            error_log('[email-queue] ' . $report . ' ' . $campaign['date'] . ' finished: ' . json_encode(queueSummary($campaign)));
        }
        queueSave($path, $campaign);

        return ['exists' => true, 'sent_now' => $sentNow, 'throttled' => $throttled] + queueSummary($campaign);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../connection.php');
require_once('../helpers.php');
require_once('../_sendMail.php');

date_default_timezone_set('Asia/Kolkata');

/*
 * Access control unchanged from your original file — only people in
 * FOLLOW_UP_ACCESS_EMAILS can trigger this. If you also want a daily cron
 * job to hit this URL unattended, you have two options:
 *   1) Swap this for require_cron_or_admin_access($link) (same helper used
 *      in email2.php / emailmenu.php) — but check helpers.php first: that
 *      function may allow ANY admin, not just people in
 *      FOLLOW_UP_ACCESS_EMAILS, which would be a bigger access grant than
 *      you have today.
 *   2) Leave this as-is and have cron call this URL with a session cookie
 *      for a FOLLOW_UP_ACCESS_EMAILS account (e.g. via a saved cookie jar
 *      in curl/wget), so access stays scoped exactly as before.
 * Left untouched here deliberately — pick whichever matches your intent.
 */
if (!user_email_in(FOLLOW_UP_ACCESS_EMAILS)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => 'Forbidden.']);
    exit;
}

header('Content-Type: application/json');

/*
 * Tune these to your Hostinger plan. If you keep seeing 421/450-style
 * throttling errors in error_log after this change, your host is capping
 * sends per hour regardless of code — lower EMAILS_PER_HOUR, or move to a
 * transactional SMTP provider (SES / Brevo / Mailgun / SendGrid).
 */
const EMAILS_PER_HOUR = 250;   // target sustained send rate
const CHUNK_SIZE       = 20;    // members per sendEmailBatch() call; file is saved after each chunk
const MAX_PASSES        = 3;    // extra full sweeps over transient failures within this run
const PASS_GAP_SECONDS  = 180;  // wait between sweeps, to ride out a short SMTP outage

$reports = [
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

$report = (string) ($_REQUEST['report'] ?? '');
$action = (string) ($_REQUEST['action'] ?? 'run'); // 'run' (default) or 'status'

if (!isset($reports[$report])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid email report.']);
    exit;
}

/* ---------------------------- file-based queue storage ---------------------------- */

function queueDir(): string
{
    $dir = __DIR__ . '/../storage/email-queue';
    if (!is_dir($dir)) {
        mkdir($dir, 0770, true);
    }
    $htaccess = $dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        // Belt-and-suspenders: these files contain member emails and pending
        // amounts. Deny direct web access even if this folder ends up under
        // your document root.
        file_put_contents($htaccess, "Require all denied\n");
    }
    return $dir;
}

function campaignPath(string $report): string
{
    // One file per report per calendar day. Yesterday's file is left in
    // place as a small history log; delete old ones periodically if you
    // want to reclaim space.
    return queueDir() . '/' . $report . '-' . date('Y-m-d') . '.json';
}

function lockPath(string $report): string
{
    return queueDir() . '/' . $report . '.lock';
}

function loadCampaign(string $path): ?array
{
    if (!file_exists($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function saveCampaign(string $path, array $data): void
{
    // Write to a temp file and rename, so a request reading the file mid-write
    // (e.g. a status poll) never sees a half-written JSON file.
    $tmp = $path . '.tmp';
    file_put_contents($tmp, json_encode($data));
    rename($tmp, $path);
}

/* ---------------------------- email content ---------------------------- */

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

function buildCampaign(string $report, array $reportDef, mysqli $link): array
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
            'status' => 'pending',
            'attempts' => 0,
            'last_error' => null,
        ];
    }

    return [
        'report' => $report,
        'date' => date('Y-m-d'),
        'created_at' => date('c'),
        'status' => 'in_progress',
        'no_valid_email' => $noEmail,
        'members' => $rows,
    ];
}

function summarize(array $campaign): array
{
    $sent = $failed = $pending = 0;
    foreach ($campaign['members'] as $m) {
        if ($m['status'] === 'sent') {
            $sent++;
        } elseif ($m['status'] === 'failed') {
            $failed++;
        } else {
            $pending++;
        }
    }
    return [
        'total' => count($campaign['members']),
        'sent' => $sent,
        'failed' => $failed,
        'pending' => $pending,
        'no_valid_email' => $campaign['no_valid_email'] ?? 0,
        'status' => $campaign['status'],
        'date' => $campaign['date'],
    ];
}

/* ---------------------------- action=status ---------------------------- */

if ($action === 'status') {
    $campaign = loadCampaign(campaignPath($report));
    if ($campaign === null) {
        echo json_encode(['ok' => true, 'exists' => false]);
        exit;
    }
    echo json_encode(['ok' => true, 'exists' => true] + summarize($campaign));
    exit;
}

/* ---------------------------- action=run (default) ---------------------------- */

$lockHandle = fopen(lockPath($report), 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    if ($lockHandle !== false) {
        fclose($lockHandle);
    }
    echo json_encode(['ok' => true, 'busy' => true, 'message' => 'Already running for this report. Check back shortly.']);
    exit;
}
register_shutdown_function(static function () use ($lockHandle): void {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
});

ignore_user_abort(true);
set_time_limit(0);

$path = campaignPath($report);
$campaign = loadCampaign($path);
if ($campaign === null) {
    // First trigger today for this report: snapshot who currently qualifies.
    $campaign = buildCampaign($report, $reports[$report], $link);
    saveCampaign($path, $campaign);
} elseif ($campaign['status'] === 'done') {
    // Already fully processed today. Re-clicking won't re-send anyone —
    // it just reports today's completed totals.
    echo json_encode(['ok' => true, 'started' => false, 'already_done_today' => true] + summarize($campaign));
    exit;
}
// else: an earlier run today didn't finish (crash/restart) — resume it,
// skipping anyone already marked 'sent'.

echo json_encode(['ok' => true, 'started' => true] + summarize($campaign));
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

/* --------- everything below runs after the HTTP response has already been sent --------- */

$minIntervalSeconds = EMAILS_PER_HOUR > 0 ? (3600 / EMAILS_PER_HOUR) : 0;

for ($pass = 1; $pass <= MAX_PASSES; $pass++) {
    $pendingIts = array_keys(array_filter($campaign['members'], static fn($m) => $m['status'] === 'pending'));
    if (!$pendingIts) {
        break;
    }

    foreach (array_chunk($pendingIts, CHUNK_SIZE) as $chunk) {
        $messages = [];
        foreach ($chunk as $its) {
            $m = $campaign['members'][$its];
            $messages[$its] = [
                'to' => $m['recipients'],
                'subject' => 'Reminder - FMB Hoob Pending',
                'body' => buildReminderEmail($m),
            ];
        }

        $results = sendEmailBatch($messages);

        foreach ($chunk as $its) {
            $res = $results[$its] ?? ['ok' => false, 'retry' => true, 'error' => 'No result returned'];
            $campaign['members'][$its]['attempts']++;

            if ($res['ok']) {
                $campaign['members'][$its]['status'] = 'sent';
                $campaign['members'][$its]['last_error'] = null;
            } elseif ($res['retry'] && $pass < MAX_PASSES) {
                // leave status = 'pending' — picked up again next pass
                $campaign['members'][$its]['last_error'] = $res['error'];
            } else {
                $campaign['members'][$its]['status'] = 'failed';
                $campaign['members'][$its]['last_error'] = $res['error'];
            }
        }

        saveCampaign($path, $campaign);

        if ($minIntervalSeconds > 0) {
            usleep((int) ($minIntervalSeconds * count($chunk) * 1000000));
        }
    }

    $stillPending = array_filter($campaign['members'], static fn($m) => $m['status'] === 'pending');
    if ($stillPending && $pass < MAX_PASSES) {
        sleep(PASS_GAP_SECONDS);
    }
}

$campaign['status'] = 'done';
$campaign['finished_at'] = date('c');
saveCampaign($path, $campaign);

error_log('[email.php] Campaign ' . $report . ' ' . $campaign['date'] . ' finished: ' . json_encode(summarize($campaign)));

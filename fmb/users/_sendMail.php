<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/connection.php';

/**
 * Hostinger's shared/cPanel hosting enforces a hard outbound mail cap
 * (commonly 200 messages/hour across the whole account, regardless of
 * how many scripts or SMTP connections are sending). We can't raise
 * that limit from PHP, so instead we self-throttle to stay under it and
 * automatically back off/retry if we still get rate-limited.
 *
 * Set this a bit below your actual Hostinger limit as a safety margin
 * (check hPanel > Emails for your plan's exact number).
 */
if (!defined('SMTP_HOURLY_LIMIT')) {
    define('SMTP_HOURLY_LIMIT', 180);
}

/**
 * Detect Hostinger/Exim-style rate limit errors from an SMTP error string.
 */
function isSmtpRateLimitError(string $error): bool
{
    return strpos($error, '451') !== false
        || stripos($error, 'ratelimit') !== false
        || stripos($error, 'rate limit') !== false;
}

/**
 * Blocks (sleeping) until sending one more email would stay within
 * SMTP_HOURLY_LIMIT for the trailing 60-minute window. Shared across
 * every script/process via a lock-protected file, so it works even
 * when sendEmail() and sendEmailBatch() run in the same request or
 * across separate cron-triggered requests.
 */
function smtpThrottleGuard(): void
{
    $stateFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'smtp-hourly-send-log.json';

    // Loop instead of recursion: if we have to wait, we re-check afterwards
    // in case another process sent meanwhile, without growing the call stack.
    while (true) {
        $fp = fopen($stateFile, 'c+');
        if ($fp === false) {
            // Can't track usage — fail open rather than block sending entirely.
            error_log('[smtpThrottleGuard] Could not open state file; skipping throttle check.');
            return;
        }

        flock($fp, LOCK_EX);

        $raw = stream_get_contents($fp);
        $timestamps = $raw !== false && $raw !== '' ? (json_decode($raw, true) ?: []) : [];
        if (!is_array($timestamps)) {
            $timestamps = [];
        }

        $now = time();
        $timestamps = array_values(array_filter($timestamps, static function ($t) use ($now) {
            return $t > $now - 3600;
        }));

        if (count($timestamps) >= SMTP_HOURLY_LIMIT) {
            sort($timestamps);
            $waitSeconds = max(1, ($timestamps[0] + 3600) - $now + 1);

            // Persist the pruned list before releasing the lock so other
            // processes don't wait on stale entries.
            rewind($fp);
            ftruncate($fp, 0);
            fwrite($fp, json_encode($timestamps));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            error_log("[smtpThrottleGuard] Hourly limit (" . SMTP_HOURLY_LIMIT . ") reached; sleeping {$waitSeconds}s.");
            sleep($waitSeconds);
            continue; // re-check after waiting
        }

        $timestamps[] = $now;
        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, json_encode($timestamps));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return;
    }
}

/**
 * Sends via the given PHPMailer instance, self-throttling beforehand and
 * retrying with backoff if Hostinger still returns a rate-limit error
 * (e.g. because another process used up the quota in between).
 *
 * @throws Throwable if sending fails for a non-rate-limit reason, or the
 *                    rate limit persists past $maxRetries.
 */
function sendWithRateLimitRetry(PHPMailer $mail, int $maxRetries = 2, int $backoffSeconds = 65): bool
{
    $attempt = 0;

    while (true) {
        smtpThrottleGuard();

        try {
            $mail->send();
            return true;
        } catch (Throwable $e) {
            $error = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();

            if (isSmtpRateLimitError($error) && $attempt < $maxRetries) {
                $attempt++;
                error_log("[sendWithRateLimitRetry] Rate limited (attempt {$attempt}/{$maxRetries}); waiting {$backoffSeconds}s. {$error}");
                sleep($backoffSeconds);
                continue;
            }

            throw $e;
        }
    }
}

/**
 * Send one email.
 */
function sendEmail(
    array $to,
    string $subject,
    string $bodyHtml,
    ?array $cc = null,
    ?array $bcc = null,
    bool $isHtml = true,
    ?array $attachments = null
): bool {
    $GLOBALS['lastSendEmailError'] = null;

    if (SMTP_USER === '' || SMTP_PASS === '') {
        $GLOBALS['lastSendEmailError'] = 'SMTP credentials are not configured on the server.';
        error_log('[sendEmail] SMTP credentials are not configured.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;

        $mail->setFrom(SMTP_USER);

        foreach ($to as $email) {
            $mail->addAddress($email);
        }

        foreach ($cc ?? [] as $email) {
            $mail->addCC($email);
        }

        foreach ($bcc ?? [] as $email) {
            $mail->addBCC($email);
        }

        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        $mail->AltBody = $isHtml ? strip_tags($bodyHtml) : $bodyHtml;

        foreach ($attachments ?? [] as $attachment) {
            $mail->addStringAttachment($attachment['data'], $attachment['name']);
        }

        sendWithRateLimitRetry($mail);
        $mail->smtpClose();

        return true;
    } catch (Throwable $e) {
        $error = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();
        $GLOBALS['lastSendEmailError'] = $error;
        error_log('[sendEmail] PHPMailer error: ' . $error);
        $mail->smtpClose();

        return false;
    }
}

/**
 * Send personalized messages over one SMTP connection.
 *
 * Each message may contain a private "member_index" value. This value is
 * used by the caller to resume safely if Hostinger rate-limits the connection.
 *
 * @param array<int, array{to: array, subject: string, body: string, cc?: ?array, bcc?: ?array, isHtml?: bool, member_index?: int}> $messages
 */
function sendEmailBatch(array $messages): int
{
    $GLOBALS['lastSendEmailRateLimited'] = false;
    $GLOBALS['lastSendEmailAttempted'] = 0;
    $GLOBALS['lastSendEmailRateLimitMemberIndex'] = null;
    $GLOBALS['lastSendEmailError'] = null;

    if (empty($messages)) {
        return 0;
    }

    if (SMTP_USER === '' || SMTP_PASS === '') {
        $GLOBALS['lastSendEmailError'] = 'SMTP credentials are not configured on the server.';
        error_log('[sendEmailBatch] SMTP credentials are not configured.');
        return 0;
    }

    $mail = new PHPMailer(true);
    $sent = 0;
    $lastError = null;

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;

        foreach ($messages as $message) {
            try {
                $mail->clearAllRecipients();
                $mail->clearAttachments();
                $mail->setFrom(SMTP_USER);

                foreach ($message['to'] as $email) {
                    $mail->addAddress($email);
                }

                foreach ($message['cc'] ?? [] as $email) {
                    $mail->addCC($email);
                }

                foreach ($message['bcc'] ?? [] as $email) {
                    $mail->addBCC($email);
                }

                $isHtml = $message['isHtml'] ?? true;
                $mail->isHTML($isHtml);
                $mail->Subject = $message['subject'];
                $mail->Body = $message['body'];
                $mail->AltBody = $isHtml
                    ? strip_tags($message['body'])
                    : $message['body'];

                sendWithRateLimitRetry($mail);
                $sent++;
                $GLOBALS['lastSendEmailAttempted']++;
            } catch (Throwable $e) {
                $error = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();
                $lastError = $error;
                $GLOBALS['lastSendEmailError'] = $error;

                error_log(
                    '[sendEmailBatch] PHPMailer error: ' . $error .
                    ' | recipients: ' . implode(', ', $message['to'])
                );

                if (isSmtpRateLimitError($error)) {
                    $GLOBALS['lastSendEmailRateLimited'] = true;
                    $GLOBALS['lastSendEmailRateLimitMemberIndex'] =
                        isset($message['member_index'])
                            ? (int) $message['member_index']
                            : null;
                    $mail->smtpClose();
                    break;
                }

                // A non-rate-limit failure is counted as an attempted message.
                $GLOBALS['lastSendEmailAttempted']++;
            }
        }
    } catch (Throwable $e) {
        $GLOBALS['lastSendEmailError'] = $e->getMessage();
        error_log('[sendEmailBatch] SMTP setup error: ' . $e->getMessage());
    } finally {
        $mail->smtpClose();
    }

    if ($lastError !== null) {
        $GLOBALS['lastSendEmailError'] = $lastError;
    }

    return $sent;
}
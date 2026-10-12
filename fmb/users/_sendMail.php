<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/connection.php';

/**
 * Every call site in this codebase already passes 4-6 positional args
 * (to, subject, body, cc, bcc, isHtml) — the previous version of this
 * function only declared 4 params, so the cc/bcc/isHtml values callers
 * were passing were silently discarded by PHP rather than doing anything.
 * This signature matches what's actually being called everywhere.
 */
function sendEmail(array $to, string $subject, string $bodyHtml, ?array $cc = null, ?array $bcc = null, bool $isHtml = true, ?array $attachments = null): bool
{
    global $link;
    $GLOBALS['lastSendEmailError'] = null;
    if (SMTP_USER === '' || SMTP_PASS === '') {
        $GLOBALS['lastSendEmailError'] = 'SMTP credentials are not configured on the server.';
        error_log('[sendEmail] SMTP credentials are not configured. Set FMB_SMTP_USER and FMB_SMTP_PASS.');
        return false;
    }
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration for Hostinger
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 20;
        $mail->CharSet    = PHPMailer::CHARSET_UTF8;

        // From and To
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

        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $isHtml ? strip_tags($bodyHtml) : $bodyHtml;
        foreach ($attachments ?? [] as $attachment) {
            $mail->addStringAttachment($attachment['data'], $attachment['name']);
        }

        $mail->send();
        $mail->SMTPKeepAlive = false;
        $mail->smtpClose();
        return true;
    } catch (Throwable $e) {
        $error = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();
        $GLOBALS['lastSendEmailError'] = $error;
        error_log('[sendEmail] PHPMailer error: ' . $error . ' | recipients: ' . implode(', ', $to));
        return false;
    }
}

/**
 * Sends each message over one kept-alive SMTP connection, and recovers from
 * a dead/reset connection by closing it so PHPMailer reconnects on the next
 * send(). Transient errors (connection-level failures, SMTP 4xx codes such as
 * rate limiting) are retried in place with backoff up to $maxRetries times;
 * permanent 5xx errors are not retried.
 *
 * $deadline (unix time with microseconds, e.g. microtime(true) + 20) makes the
 * function stop early so a web request never outlives the gateway timeout:
 *   - messages not yet attempted when the deadline passes are NOT in the result
 *     array at all, so the caller can leave them pending;
 *   - a retry that would sleep past the deadline is skipped and reported as a
 *     transient failure (retry => true) instead.
 *
 * Returns an array of per-message results, NOT an int. email2.php ignores the
 * return value, so it is unaffected by this.
 *
 * @param array<int|string, array{to: array, subject: string, body: string, cc?: ?array, bcc?: ?array, isHtml?: bool}> $messages
 * @return array<int|string, array{ok: bool, retry: bool, error: ?string}> keyed like $messages
 */
function sendEmailBatch(array $messages, int $maxRetries = 2, ?float $deadline = null): array
{
    $results = [];
    if (empty($messages)) {
        return $results;
    }
    if (SMTP_USER === '' || SMTP_PASS === '') {
        foreach ($messages as $key => $_) {
            $results[$key] = ['ok' => false, 'retry' => false, 'error' => 'SMTP credentials are not configured.'];
        }
        return $results;
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host          = 'smtp.hostinger.com';
    $mail->SMTPAuth      = true;
    $mail->Username      = SMTP_USER;
    $mail->Password      = SMTP_PASS;
    $mail->SMTPSecure    = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port          = 587;
    $mail->Timeout       = 20;
    $mail->CharSet       = PHPMailer::CHARSET_UTF8;
    $mail->SMTPKeepAlive = true;

    foreach ($messages as $key => $message) {
        if ($deadline !== null && microtime(true) >= $deadline) {
            break; // not attempted: caller keeps it pending
        }

        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                $mail->clearAllRecipients();
                $mail->clearAttachments();
                $mail->setFrom(SMTP_USER);
                foreach ($message['to'] as $email)        { $mail->addAddress($email); }
                foreach ($message['cc'] ?? [] as $email)  { $mail->addCC($email); }
                foreach ($message['bcc'] ?? [] as $email) { $mail->addBCC($email); }
                $isHtml = $message['isHtml'] ?? true;
                $mail->isHTML($isHtml);
                $mail->Subject = $message['subject'];
                $mail->Body    = $message['body'];
                $mail->AltBody = $isHtml ? strip_tags($message['body']) : $message['body'];
                $mail->send();

                $results[$key] = ['ok' => true, 'retry' => false, 'error' => null];
                break;
            } catch (Throwable $e) {
                $error = $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $e->getMessage();
                $code  = (int) ($mail->getSMTPInstance()?->getError()['smtp_code'] ?? 0);
                // 0 = connection/timeout level, 4xx = temporary SMTP failure
                $transient = $code === 0 || ($code >= 400 && $code < 500);
                error_log("[sendEmailBatch] code=$code attempt=$attempt error=$error | " . implode(', ', $message['to']));

                $mail->smtpClose(); // drop the dead connection; next send() reconnects

                $backoff = 2 * $attempt;
                $outOfTime = $deadline !== null && (microtime(true) + $backoff) >= $deadline;

                if (!$transient || $attempt > $maxRetries || $outOfTime) {
                    $results[$key] = ['ok' => false, 'retry' => $transient, 'error' => $error];
                    break;
                }
                sleep($backoff);
            }
        }
        usleep(400000); // small pacing floor between messages
    }

    $mail->smtpClose();
    return $results;
}

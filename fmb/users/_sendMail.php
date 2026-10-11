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
 * Sends each message over one kept-alive SMTP connection, but recovers from
 * a dead/reset connection by closing it and letting PHPMailer reconnect on
 * the next send(). Transient errors (connection-level failures, or SMTP 4xx
 * codes such as rate limiting) are retried in place with backoff up to
 * $maxRetries times; permanent 5xx errors are not retried.
 *
 * IMPORTANT: this returns an array of per-message results, NOT an int count.
 * Every call site must read $results[$key]['ok'] / ['retry'] / ['error'],
 * not treat the return value as a number. (email2.php calls this and
 * ignores the return value entirely, so it is unaffected either way.)
 *
 * @param array<int|string, array{to: array, subject: string, body: string, cc?: ?array, bcc?: ?array, isHtml?: bool}> $messages
 * @return array<int|string, array{ok: bool, retry: bool, error: ?string}> keyed the same as $messages
 */
function sendEmailBatch(array $messages, int $maxRetries = 2): array
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

                if (!$transient || $attempt > $maxRetries) {
                    $results[$key] = ['ok' => false, 'retry' => $transient, 'error' => $error];
                    break;
                }
                sleep(2 * $attempt); // backoff before reconnecting
            }
        }
        usleep(400000); // small gentle pacing floor between messages
    }

    $mail->smtpClose();
    return $results;
}

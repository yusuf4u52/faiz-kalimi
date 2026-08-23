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
        error_log('[sendEmail] PHPMailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/connection.php';

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

        $mail->send();
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

                $mail->send();
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

                $isRateLimited =
                    strpos($error, '451') !== false ||
                    stripos($error, 'ratelimit') !== false ||
                    stripos($error, 'rate limit') !== false;

                if ($isRateLimited) {
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

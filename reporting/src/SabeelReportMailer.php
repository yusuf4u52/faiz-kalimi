<?php

declare(strict_types=1);

namespace JamaatReport;

use Dompdf\Dompdf;
use Dompdf\Options;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Renders a Sabeel report to PDF (via Dompdf) and emails it to the Head of
 * Family as an attachment (via PHPMailer over SMTP).
 */
final class SabeelReportMailer
{
    /** @param array{host: string, port: int|string, encryption?: string, username: string, password: string, from_email: string, from_name?: string} $smtpConfig */
    public function __construct(private array $smtpConfig)
    {
    }

    public function send(string $toEmail, string $toName, string $subject, string $bodyHtml, string $pdfHtml, string $pdfFilename): void
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($pdfHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->smtpConfig['host'];
        $mail->Port = (int) $this->smtpConfig['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtpConfig['username'];
        $mail->Password = $this->smtpConfig['password'];
        if (($this->smtpConfig['encryption'] ?? '') !== '') {
            $mail->SMTPSecure = $this->smtpConfig['encryption'];
        }

        $mail->setFrom($this->smtpConfig['from_email'], $this->smtpConfig['from_name'] ?? '');
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $bodyHtml;
        $mail->addStringAttachment($pdfContent, $pdfFilename, 'base64', 'application/pdf');

        $mail->send();
    }
}

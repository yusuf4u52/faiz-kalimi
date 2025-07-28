<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require_once 'connection.php';

function sendEmail(array $to, $subject, $bodyHtml, $bodyText = '')
{
	$mail = new PHPMailer(true);

	try {
		// SMTP configuration for Hostinger
		$mail->isSMTP();
		$mail->Host       = 'smtp.hostinger.com';
		$mail->SMTPAuth   = true;
		$mail->Username   = SMTP_USER;
		$mail->Password   = SMTP_PASS;
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$mail->Port       = 465;

		// From and To
		$mail->setFrom(SMTP_USER);
		foreach ($to as $email) {
			$mail->addAddress($email);
		}

		// Content
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = $bodyHtml;
		$mail->AltBody = $bodyText ?: strip_tags($bodyHtml);

		$mail->send();
		return true;
	} catch (Exception $e) {
		echo("Email could not be sent. PHPMailer Error: {$mail->ErrorInfo}");
		return false;
	}
}

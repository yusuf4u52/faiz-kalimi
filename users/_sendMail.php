<?php
function sendEmail($to, $subject, $msg, $attachment, $attachmentObj = false, $addAllRecipents = true)
{
	require '../vendor/autoload.php';
	require '../sms/_credentials.php';

	$email = new \SendGrid\Mail\Mail();
	$email->setFrom("no-reply@kalimijamaatpoona.org", "Faizul Mawaidil Burhaniya (Kalimi Mohalla)");
	$email->setSubject($subject);
	$email->addTo($to);

	if ($addAllRecipents) {
		$email->addTo("kalimifaiz@gmail.com");
	}

	$email->addContent(
		"text/html",
		$msg
	);

	if ($attachmentObj) {
		foreach ($attachmentObj as $value) {
			$email->addAttachment($value);
		}
	}

	if ($attachment != null) {
		$attach = new \SendGrid\Mail\Attachment();
		$attach->setContent(base64_encode($attachment));
		$attach->setType("application/text");
		$attach->setFilename("backup.sql");
		$attach->setDisposition("attachment");
		$attach->setContentId("Database Backup");
		$email->addAttachment($attach);
	}

	$sendgrid = new \SendGrid($SENDGRID_API_KEY);
	try {
		$sendgrid->send($email);
	} catch (Exception $e) {
		echo 'Caught exception: ' . $e->getMessage() . "\n";
	}
}

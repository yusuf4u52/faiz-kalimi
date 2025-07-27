<?php
function sendEmail($to, $subject, $msg, $attachment, $attachmentObj = null, $addTransporter = false)
{
	require '../vendor/autoload.php';
	require '../sms/_credentials.php';

	$email = new \SendGrid\Mail\Mail();
	$email->setFrom("no-reply@kalimijamaatpoona.org", "Faizul Mawaidil Burhaniya (Kalimi Mohalla)");
	$email->setSubject($subject);
	$email->addTo($to);

	if ($addTransporter) {
		$email->addTo("yusuf4u52@gmail.com");
		$email->addTo("moula1981sk@gmail.com");
		$email->addTo("khanbilalkbr@gmail.com");
		$email->addTo("mulla.moiz@gmail.com");
		$email->addTo("abbas.saifee5@gmail.com");
		$email->addTo("moizlife@gmail.com");
		$email->addTo("tinwalaabizer@gmail.com");
		$email->addTo("hussainbarnagarwala14@gmail.com");
		$email->addTo("kanchwalaabizer@gmail.com");
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

	function sendPhpMail($subject, $message)
	{
		$from = "no-reply@kalimijamaatpoona.org";
		$to = "kalimimohallapoona@gmail.com, yusuf4u52@gmail.com, mulla.moiz@gmail.com, moizlife@gmail.com";
		$headers = "From:" . $from;
		if (mail($to, $subject, $message, $headers)) {
			echo "The email message was sent.";
		} else {
			echo "The email message was not sent.";
		}
	}
}

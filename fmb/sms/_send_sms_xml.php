<?php
//assuming _credentials.php is already included by the file which is including this file
// this function sends the SMS, everything is hardcoded, the return value is the value returned
// from the XML api call
function send_sms_to_records($conn, $message)
{
	require '_credentials.php';
	//$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
	//$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$message_raw = $message;
	$qAmount = "next_install";
	$qThali = "Thali";
	$qName = "NAME";
	$qContact = "CONTACT";
	$tablename = "thalilist";
	$query = "SELECT $qThali, $qName, $qContact, $qAmount from $tablename where Active = 1 and $qAmount>0 and $qThali is not null and $qContact is not null";
	echo $query;
	$stmt = $conn->prepare($query);
	$stmt->execute();

	// set the resulting array to associative
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	echo "Messages sent " . sizeOf($result);
	echo "<hr>";
	foreach ($result as $record) {
		//extract($record);
		$number = $record[$qContact];
		$thali = $record[$qThali];
		$name = $record[$qName];
		$names = explode(" ", $name, 3);
		$name = $names[0] . $names[1];
		$amount = $record[$qAmount];
		$message_formatted = str_replace(array("<TN>", "<NAME>", "<AMO>"), array($thali, $name, $amount), $message_raw);
		// message charge will double beyond 142 chars for fast2sms
		// $message_formatted = (strlen($message_formatted) > 142) ? substr($message_formatted, 0, 142) : $message_formatted;

		// echo $message_formatted;
		$sms_body_encoded = rawurlencode($message_formatted);
		// send sms
		// $sendurl = "https://www.fast2sms.com/dev/bulkV2?authorization=$smsauthkey&route=v3&sender_id=TXTIND&message=$sms_body_encoded&language=english&flash=0&numbers=$number";
		$sendurl = "https://senderomatic.xyz/api/send-media.php?number=91$number&msg=$sms_body_encoded&media=https://".$_SERVER['HTTP_HOST']."/fmb/users/images/fmb-hdfc-qr.jpeg&apikey=$apikey&instance=G872gO9M5km6xef";
		file_get_contents($sendurl);
	}
	return "success";
}

<?php
include '../vendor/autoload.php';

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
		// echo $message_formatted;
		$sms_body_encoded = urlencode($message_formatted);
		// send sms
		$sendurl = "https://www.fast2sms.com/dev/bulkV2?authorization=$smsauthkey&route=v3&sender_id=TXTIND&message=$sms_body_encoded&language=english&flash=0&numbers=$number";

		$client = new GuzzleHttp\Client();
		$client->getAsync($sendurl);
	}
	return "success";
}

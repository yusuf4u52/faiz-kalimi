<?php
/*
salaam <NAME>, your thali #<THALINO> has outstanding amount of Rs. <AMOUNT>. please pay

it takes around 5 seconds to send telegram messages for 10 people
therefore in one second 2 people receive the message
the above observations were taken for murtazafaizstudent.pythonanywhere.com domain
for sms.myn2p, the delay will be more
*/
require '_credentials.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $records_raw = $_REQUEST['records'];
    $records = json_decode($records_raw, true);
    $message_raw = $_REQUEST['message'];
    //var_dump($records);
    // now begin by changing placeholders
    $params = array();
    foreach ($records as $record) {
        //extract($record);
        $contact = $record['CONTACT']; // this is an array of numbers
        $numbers = getMobilesString($contact);
        if (!$numbers) {
            // skip sending SMS to a faulty number(s)
            continue;
        }
        $thali = $record['Thali'];
        $name = $record['NAME'];
        $names = explode(" ", $name, 3);
        $name = $names[0] . " " . $names[1];
        $amount = $record['amount'];
        $message_formatted = str_replace(array("<THALI>", "<NAME>", "<AMOUNT>"), array($thali, $name, $amount), $message_raw);
        $message = urlencode($message_formatted);

        //$param["url"] = "authorization=$smsauthkey&route=v3&sender_id=TXTIND&message=$message&language=english&flash=0&numbers=$numbers";
        $param["url"] = "number=91$numbers&msg=$message&media=https://".$_SERVER['HTTP_HOST']."/fmb/users/images/fmb-hdfc-qr.jpeg&apikey=$apikey&instance=G872gO9M5km6xef";
        $param["number"] = $numbers;
        array_push($params, $param);
    }
    $data = array('result' => "success", 'params' => $params);
    echo json_encode($data);
}

function getMobilesString($numbers)
{
    /*
    myn2p expects numbers in this format
    <country_code><number>
    but we receive numbers in this format
    +<country_code> <number>

    from their documentation:
    Keep numbers in international format (with country code), multiple numbers should be separated by comma (,)
    https://sms1.almasaarr.com/api/sendhttp.php?authkey=YourAuthKey&mobiles=919999999990,919999999999&message=message&sender=ABCDEF&route=4&country=0
    */
    $regex_international_phone_number = '/^\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1) (\d{1,14})$/';

    $processedNumbers = array();
    foreach ($numbers as $num) {
        $matches = array();
        preg_match($regex_international_phone_number, $num, $matches);
        if (sizeof($matches) == 3) {
            // international phone number
            $country_code = $matches[1];
            $national_number = $matches[2];
            $formatted_number = $country_code . $national_number;
            array_push($processedNumbers, $formatted_number);
        } else if (preg_match('/^[1-9][0-9]{9}$/', $num)) {
            // indian national phone number
            array_push($processedNumbers, $num);
        } else {
            // the number was in an unrecognized format
        }
    }
    $finalString = implode(",", $processedNumbers);
    return $finalString;
}

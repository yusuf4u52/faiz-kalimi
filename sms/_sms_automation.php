<?php
require '_credentials.php';
require '_send_sms_xml.php';
require '../users/update_next_install.php';
/*

some functions that i may require

SELECT date_add(miqat_date, INTERVAL 1 DAY) FROM `sms_date` WHERE 1
echo date('Y-m-d');
SELECT from_unixtime(unix_timestamp(miqat_date)) FROM `sms_date` WHERE 1

also include thali not null condition while retrieving records to send sms to

to get transport stop date $date->modify('+1 day'); //does not work as of now

*/


function get_next_day($date)
{
    //$date = "04-15-2013";
    $date = str_replace('-', '/', $date);
    $tomorrow = date('d/m', strtotime($date . "+1 days"));
    return $tomorrow;
}

function get_todays_date()
{
    return date('Y-m-d');
}

function get_day_diff($miqat_date)
{
    $now = time();
    $miqat = strtotime($miqat_date);
    $time_diff = $miqat - $now;
    $day_diff = $diff = floor($time_diff / (60 * 60 * 24));
    return $day_diff;
}

$real = "https://sms1.almasaarr.com/sendhttp.php?";
$telegram = "http://murtazafaizstudent.pythonanywhere.com/sendhttp.php?";
$email = "http://murtazafaizstudent.pythonanywhere.com/sendsmtp?";

/*
    If table does not exist, create a table with only the miqaat dates
    create  table dates(miqaat date);
    insert into dates values('2017,06,16');
    insert into dates values('2017,07,21');
    insert into dates values('2017,08,19');
    insert into dates values('2017,09,09');
    insert into dates values('2017,10,06');
    insert into dates values('2017,11,09');
    insert into dates values('2017,11,30');
    insert into dates values('2018,01,07');
*/

try {

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $todays_date = get_todays_date();
    $stmt = $conn->prepare("SELECT * FROM `sms_date` WHERE miqat_date >= '$todays_date' order by miqat_id limit 1");
    $stmt->execute();
    $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $next_miqat = NULL;
    if (sizeof($stmt) == 0) {
        echo "no such miqat exists";
    } else {
        $next_miqat = $stmt[0];
        echo "next miqat is " . $next_miqat['miqat_description'];
    }

    // now get the day difference
    $day_diff = get_day_diff($next_miqat['miqat_date']);
    //$day_diff = 7; // for debugging.
    echo "day diff is " . $day_diff;
    /*
    so the day when i coded this was 7th June. 
    Lailatul Qadr is on 16th June. 
    The day_diff value observed was 8 days
    */

    // now check if any message template exists for this day_diff in the database
    $stmt = $conn->prepare("SELECT * FROM `sms_template` WHERE fired_before_days=$day_diff");
    $stmt->execute();
    $stmt = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sms_template = NULL;

    if (sizeof($stmt) == 0) {
        echo "No SMS to send before $day_diff days";
    } else {
        $sms_template = $stmt[0];
        $stop_date = get_next_day($next_miqat['miqat_date']);
        echo $stop_date;
        $template_formatted = str_replace(array("<DAY>", "<DATE>"), array($day_diff, $stop_date), $sms_template['template']);
        echo "<br>formatted template:<br>" . $template_formatted;

        $result = send_sms_to_records($conn, $template_formatted);
        echo "send sms returned ";
        print_r($result);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

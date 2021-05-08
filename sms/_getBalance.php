<?php
require '_credentials.php';
$balance = file_get_contents("https://www.fast2sms.com/dev/wallet?authorization=$smsauthkey");
echo json_decode($balance)->wallet;

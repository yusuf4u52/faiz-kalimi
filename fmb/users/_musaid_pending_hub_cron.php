<?php
include('connection.php');
require_once '_sendMail.php';
require '../vendor/autoload.php'; 

$musaid_list = mysqli_query($link,"SELECT * FROM `users`");

while($musaid = mysqli_fetch_assoc($musaid_list)){
    $sql = mysqli_query($link,"SELECT `Thali`, `NAME`,`CONTACT`,`Transporter`,`Total_Pending`
    FROM `thalilist`
    WHERE `Thali` is not null AND `Total_Pending` > 0 AND `musaid` = '".$musaid['email']."' 
    ORDER BY `Total_Pending` DESC");

    $contentString = implode(",", array('Thali','Name','Contact','Transporter','Total_Pending')) . "\r\n";

    $hasRecords = false;
    while($row = mysqli_fetch_assoc($sql))
    {
        $hasRecords = true;
        $contentString .= implode(",", $row) . "\r\n";
    }

    if($hasRecords){
        $attach = new \SendGrid\Mail\Attachment();
        $attach->setContent(base64_encode($contentString));
        $attach->setType('text/csv');
        $attach->setFilename('PendingHub.csv');
        $attach->setDisposition("attachment");
        $attach->setContentId("Database Backup");
        sendEmail($musaid['email'], 'Musaid Pending Hub File '.date('d/m/Y'), 'PFA', null, array($attach), false);
    }
}

echo "Done";
?>

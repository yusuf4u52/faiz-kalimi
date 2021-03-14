<?php

include('connection.php');
include('_authCheck.php');
require '_sendMail.php';

mysqli_query($link,"Update thalilist set Active='2' WHERE Email_id = '".$_POST['email']."'") or die(mysqli_error($link));

$msgvar = 'Salam %name%,<br><br>Your thaali has not been activated as we are not currently able to deliver at your address. For any queries please mail us at <b>kalimifaiz@gmail.com.</b><br><br>Regards,<br>Faiz Team';

$msgvar = str_replace(array('%name%'), array($_POST['name']), $msgvar);
sendEmail($_POST['email'], 'Thali Not Activated', $msgvar, null);

header("Location: pendingactions.php");

<?php

include('connection.php');
include('_authCheck.php');
require '_sendMail.php';

mysqli_query($link, "Update thalilist set Active='0' AND hardstop = '1' AND hardstop_comment = 'Not delivered to your address.' WHERE Email_id = '" . $_POST['email'] . "'") or die(mysqli_error($link));

$msgvar = 'Salam %name%,<br><br>

We regret to inform you that your Thaali could not be activated at this time, as we are currently unable to provide delivery service to your address.<br><br>

If you have any queries or require any further information, please feel free to contact us at <b>[kalimimohallapoona@gmail.com](mailto:kalimimohallapoona@gmail.com)</b>.<br><br>

We appreciate your understanding and cooperation.<br><br>

Regards,<br>
FMB Khidmat Team';

$msgvar = str_replace(array('%name%'), array($_POST['name']), $msgvar);
sendEmail([$_POST['email']], 'Thali Not Activated', $msgvar, null);

header("Location: pendingactions.php");
exit;

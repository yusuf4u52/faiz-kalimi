<?php
include('connection.php');
include('_authCheck.php');
require_once('helpers.php');
require '_sendMail.php';

/**
 * BUG FIX: the original query was
 *   UPDATE thalilist SET Active='0' AND hardstop='1' AND hardstop_comment='...' WHERE ...
 * `SET` assignments must be comma-separated, not joined with AND — AND
 * only makes sense inside a boolean expression, not a SET list. This was
 * invalid SQL that would fail every time (and, with the old
 * `or die(mysqli_error($link))`, would have dumped a raw SQL error to
 * the browser instead of silently doing nothing).
 */
db_query(
    $link,
    "UPDATE thalilist SET Active = 0, hardstop = 1, hardstop_comment = 'Not delivered to your address.' WHERE Email_id = ?",
    "s",
    [$_POST['email'] ?? '']
);

$msgvar = 'Salam %name%,<br><br>

We regret to inform you that your Thaali could not be activated at this time, as we are currently unable to provide delivery service to your address.<br><br>

If you have any queries or require any further information, please feel free to contact us at <b>[kalimimohallapoona@gmail.com](mailto:kalimimohallapoona@gmail.com)</b>.<br><br>

We appreciate your understanding and cooperation.<br><br>

Regards,<br>
FMB Khidmat Team';

$msgvar = str_replace(['%name%'], [$_POST['name'] ?? ''], $msgvar);

if (!empty($_POST['email'])) {
    sendEmail([$_POST['email']], 'Thali Not Activated', $msgvar, null);
}

header("Location: pendingactions.php");
exit;

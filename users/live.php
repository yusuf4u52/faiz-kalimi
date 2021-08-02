<?php
// check if user didn't hit this page directly and is coming from login page
session_start();
if (!isset($_SESSION['fromLogin'])) {
    header("Location: login.php");
    exit;
}

if (!in_array($_SESSION['email'], array('coolakw@gmail.com'))) {
    echo '<script language="javascript">';
    echo 'alert("You are unauthorized to view this link.")';
    echo '</script>';
    exit;
}
?>
<html>

<head>
</head>

<body>
    <iframe src="https://player.castr.com/live_1a39e900f37811ebb5c39b0ff1cc970f" width="100%" height="100%" frameborder="0" scrolling="no" allow="autoplay" allowfullscreen webkitallowfullscreen mozallowfullscreen oallowfullscreen msallowfullscreen></iframe>
</body>

</html>
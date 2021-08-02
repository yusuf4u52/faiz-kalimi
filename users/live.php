<!-- <!DOCTYPE html> -->
<?php
include('_bottomJS.php');
include('_head.php');
include('connection.php');
// check if user didn't hit this page directly and is coming from login page
session_start();
if (!isset($_SESSION['fromLogin'])) {
    header("Location: login.php");
    exit;
}

if (!in_array($_SESSION['email'], array('coolakw@gmail.com', 'yusuf4u52@gmail.com'))) {
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
    <?php include('_nav.php'); ?>
    <p align="center">
        <iframe src=<?php echo $live_uri; ?> width="80%" height="80%" frameborder="0" scrolling="no" allow="autoplay" allowfullscreen webkitallowfullscreen mozallowfullscreen oallowfullscreen msallowfullscreen></iframe>
    </p>
</body>

</html>
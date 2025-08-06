<?php
session_start();

// remove all session variables
session_unset(); 
// destroy the session 
session_destroy();

header('Location: https://www.google.co.in/accounts/Logout?continue=https://appengine.google.com/_ah/logout?continue=http://kalimijamaatpoona.org/fmb/transporter/index.php');

?>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove all session variables, then destroy the session itself.
$_SESSION = [];

// Also clear the session cookie client-side, not just the server-side data,
// so a stale cookie can't be replayed to resurrect the session.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// remove all session variables
session_unset();
// destroy the session
session_destroy();

header('Location: https://www.google.co.in/accounts/Logout?continue=https://appengine.google.com/_ah/logout?continue=http://kalimijamaatpoona.org/testfmb/index.php');
exit;

<?php
$application_data = [];

function setAppData($key, $value)
{
    global $application_data;

    $application_data[$key] = $value;
}

function getAppData($key)
{
    global $application_data;

    return isset($application_data[$key]) ? $application_data[$key] : null;
}

function getSiteURL()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol . $domainName;
}

$current_directory = basename(__DIR__);

$siteURL = getSiteURL();

//Parse the request URI ( separate out the URI and Query params )
$urls = parse_url($_SERVER['REQUEST_URI']);

$path = $urls['path'] ?? '';

//Calculate the position for substring
$pos = stripos($path, $current_directory) + strlen($current_directory);

$base_location = substr($path, 0, $pos);

$base_uri = $siteURL . $base_location;
setAppData('APP_BASE_URI', $base_uri);

/************************************** 
 * START of  CONFIGURATION SECTION 
 * **************************************/
// DEFINED('DB_HOST') or DEFINE('DB_HOST', 'localhost');
// DEFINED('DB_PORT') or DEFINE('DB_PORT', '3306');
// DEFINED('DB_NAME') or DEFINE('DB_NAME', 'olivezrt_society_management');
// DEFINED('DB_USER') or DEFINE('DB_USER', 'olivezrt_rnd');
// DEFINED('DB_PASS') or DEFINE('DB_PASS', '@livezrt_rnd');

DEFINED('VIEW_FUNC') or DEFINE('VIEW_FUNC', 'content_display');
DEFINED('VIEW_DIR') or DEFINE('VIEW_DIR', 'pages');
DEFINED('VIEW_TEMPLATE') or DEFINE('VIEW_TEMPLATE', 'template.php');

/* == APP SECURITY STUFF */
DEFINED('SECURE_DIR') or DEFINE('SECURE_DIR', 'admin__'); // This should be set true if application has secure pages, else false
DEFINED('IS_APP_SECURE') or DEFINE('IS_APP_SECURE', false); // This should be set true if application has secure pages, else false
DEFINED('IS_RELATIVE_AUTH_REDIRECT') or DEFINE('IS_RELATIVE_AUTH_REDIRECT', true);
//DEFINED('AUTH_REDIRECT') or DEFINE('AUTH_REDIRECT', 'https://www.its52.com/Login.aspx?OneLogin='); //If application is secure and not autheticated.. define the redirect page 
DEFINED('AUTH_REDIRECT') or DEFINE('AUTH_REDIRECT', '/login'); //If application is secure and not autheticated.. define the redirect page 
DEFINED('OPEN_PAGE_LIST') or DEFINE('OPEN_PAGE_LIST', ['login', 'redirect']); //List of pages that bypasses authentication.
DEFINED('SECURE_LANDING_PAGE') or DEFINE('SECURE_LANDING_PAGE', 'input-sabeel');

/* == SESSION STUFF */
DEFINED('DB_BASED_SESSION') or DEFINE('DB_BASED_SESSION', false);
DEFINED('SESSION_TABLE_NAME') or DEFINE('SESSION_TABLE_NAME', 'kq_php_sessions');
DEFINED('THE_SESSION_ID') or DEFINE('THE_SESSION_ID', 'Khatm_alQuran_App_786110');
DEFINED('TRANSIT_DATA') or DEFINE('TRANSIT_DATA', 'transit_data');

DEFINED('TIME_ZONE') or DEFINE('TIME_ZONE', 'Asia/Kolkata');
DEFINED('DEBUG') or DEFINE('DEBUG', true);

include_once '_os/user_interface.php';
include_once '_os/persistance.php';
require_once '_os/framework.php';
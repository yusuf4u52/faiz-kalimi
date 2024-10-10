<?php
$current_directory = basename(__DIR__);
DEFINE('CURRENT_DIR' , $current_directory);

DEFINE('IS_APP_SECURE', true); 
DEFINE('IS_RELATIVE_AUTH_REDIRECT', true);
DEFINE('AUTH_REDIRECT', '/login');
DEFINE('SECURE_LANDING_PAGE', 'dashboard');
DEFINE('OPEN_PAGE_LIST', ['login']);
DEFINE('THE_SESSION_ID', 'ABCD_5645645646');

require_once './../../fmb/users/connection.php';

//include_once 'rsvp_admin_dto.php';
require_once './../framework.php';
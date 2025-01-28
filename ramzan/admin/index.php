<?php

$current_directory = basename(__DIR__);
DEFINE('CURRENT_DIR' , $current_directory);

DEFINED('IS_APP_SECURE') or DEFINE('IS_APP_SECURE', true); // This should be set true if application has secure pages, else false
DEFINED('SECURE_LANDING_PAGE') or DEFINE('SECURE_LANDING_PAGE', 'home');

require_once './../../fmb/users/connection.php';

require_once './../kamaal.php';

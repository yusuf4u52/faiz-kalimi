<?php
$current_directory = basename(__DIR__);
DEFINE('CURRENT_DIR' , $current_directory);

DEFINE('IS_APP_SECURE', true); 
DEFINE('IS_RELATIVE_AUTH_REDIRECT', true);
DEFINE('AUTH_REDIRECT', '/login');
DEFINE('SECURE_LANDING_PAGE', 'home');
DEFINE('OPEN_PAGE_LIST', ['login']);
DEFINE('THE_SESSION_ID', 'SHEHRULLAH_5645645646');
DEFINE('VIEW_TEMPLATE', '../_template.php');

require_once __DIR__ . '/load_env.php';
load_env_file(__DIR__ . '/../../.env');

require_once './../../fmb/users/connection.php';

include_once './../_gui.php';
include_once './../_dto.php';
include_once './../_framework.php';

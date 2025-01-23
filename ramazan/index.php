<?php

$current_directory = basename(__DIR__);
DEFINE('CURRENT_DIR' , $current_directory);

require_once './../fmb/users/connection.php';

include_once 'os/user_interface.php';
include_once 'os/persistance.php';
require_once 'os/framework.php';

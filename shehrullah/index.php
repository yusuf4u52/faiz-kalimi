<?php

$current_directory = basename(__DIR__);
DEFINE('CURRENT_DIR' , $current_directory);

require_once './../fmb/users/connection.php';

include_once '_gui.php';
include_once '_dto.php';
require_once '_framework.php';
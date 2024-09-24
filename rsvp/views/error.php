<?php
$message = getSessionData('transit_data');
if (!isset($message)) {
    do_redirect('/data_entry');
}
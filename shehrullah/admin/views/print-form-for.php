<?php
if( !is_user_a(SUPER_ADMIN, RECEPTION, DATA_ENTRY) ) {
    do_redirect_with_message('/home' , 'Redirected as tried to access unauthorized area.');
}

if_not_post_redirect('/home');

$en_sabeel = $_POST['hof_id'];
if( is_null($en_sabeel) ) {
    do_redirect_with_message('/home' , 'Ops! Sabeel not found.');
}

setAppData('arg1' , do_encrypt($en_sabeel));
include_once __DIR__ . '/../../views/print-form.php';

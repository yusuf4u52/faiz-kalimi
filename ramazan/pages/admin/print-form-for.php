<?php
if( !is_user_a([ROLE->SA, ROLE->RC, ROLE->DA]) ) {
    do_redirect_with_message('/admin/home' , 'Redirected as tried to access unauthorized area.');
}

if_not_post_redirect('/admin/home');

$en_sabeel = $_POST['hof_id'];
if( is_null($en_sabeel) ) {
    do_redirect_with_message('/admin/home' , 'Ops! Sabeel not found.');
}

setAppData('arg1' , do_encrypt($en_sabeel));
include_once __DIR__ . '/../print-form.php';

<?php
if (!is_user_a(SUPER_ADMIN, RECEPTION, DATA_ENTRY)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}

if_not_post_redirect('/home');

$sabeel = $_POST['sabeel'];

$sabeel_data = get_thaalilist_data($sabeel);
if (is_null($sabeel_data)) {
    //do_redirect_with_message('\home', 'Sabeel not found. Please enter correct Sabeel.');
    $hof_id = $sabeel;
} else {
    $hof_id = $sabeel_data->ITS_No;        
}
setAppData('hof_id', $hof_id);


$hijri_year = get_current_hijri_year();
setAppData('hijri_year', $hijri_year);


$attendees_data = get_attendees_data_for_nonsab($hof_id, $hijri_year, false);
//$attendees_data = get_attendees_data_for($hof_id, $hijri_year, false);
if (is_null($attendees_data)) {
    do_redirect_with_message('/home', 'Error: Seems you ITS (' . $hof_id . ') belong to other mohalla. Add the HOF!');
}

setAppData('arg1', do_encrypt($hof_id));
setAppData('print', true);
include_once __DIR__ . '/../../views/print-form.php';

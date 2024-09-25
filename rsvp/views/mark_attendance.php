<?php
if_not_post_redirect('/data_entry');

$family_its_list = $_POST['family_its_list'] ?? [];
$miqaat_id = $_POST['miqaat_id'];
$sabeel = $_POST['sabeel'];
$hof_id = $_POST['hof_id'];

//Either verify the miqaat by searching 
//miqaat with ID or consider value coming is correct

$count = mark_attendance($hof_id, $miqaat_id, $family_its_list);

setSessionData(TRANSIT_DATA , "Your attendance has been recorded. $count of your family may attend.");
auto_post_redirect('search_sabeel' , [
    'hof_id' => $hof_id,
    'miqaat_id' => $miqaat_id
]);

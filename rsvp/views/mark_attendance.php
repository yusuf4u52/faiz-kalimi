<?php
if_not_post_redirect('/data_entry');

$family_its_list = $_POST['family_its_list'] ?? [];
$miqaat_id = $_POST['miqaat_id'];
$sabeel = $_POST['sabeel'];
$hof_id = $_POST['hof_id'];

$count = mark_attendance($hof_id, $miqaat_id, $family_its_list);

setSessionData(TRANSIT_DATA , "Aap ni miqaat attend karwa ni niyat nond thai gai che. Aap na ghar si $count members attend karse.");
auto_post_redirect('search_sabeel' , [
    'hof_id' => $hof_id,
    'miqaat_id' => $miqaat_id
]);

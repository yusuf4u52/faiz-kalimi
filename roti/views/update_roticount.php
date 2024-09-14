<?php
if_not_post_redirect('/data_entry');

$miqaat_id = $_POST['miqaat_id'];
$sabeel = $_POST['sabeel'];
$roti_count = $_POST['roti_count'];

$result = set_roti_count_for_miqaat($sabeel, $roti_count, $miqaat_id);
//do_redirect_with_message('/data_entry' , 'Record updated.');
auto_post_redirect('acknowledge' , ['sabeel'=>$sabeel, 'miqaat_id'=>$miqaat_id]);
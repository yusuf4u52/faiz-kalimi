<?php
include('connection.php');
include('_authCheck.php');


if( isset($_POST['action']) && $_POST['action'] == 'add_stop' ) {
    echo date('Y-m-d',strtotime($_POST['from_date']));
    $sql = "INSERT INTO stop_thali (`thali`, `from_date`, `to_date`) VALUES ('" . $_POST['thali'] . "', '" . date('Y-m-d',strtotime($_POST['from_date'])) . "', '" . date('Y-m-d',strtotime($_POST['to_date'])) . "')"; 
    mysqli_query($link, $sql) or die(mysqli_error($link));
    header("Location: /fmb/users/stopthali.php?action=add&from=".$_POST['from_date']."&to=".$_POST['to_date']);
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_stop' ) {
    $sql = "DELETE FROM stop_thali WHERE `id` = '".$_POST['stop_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/stopthali.php?action=delete&from=".$_POST['from_date']."&to=".$_POST['to_date']);
}
?>

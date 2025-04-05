<?php
include('connection.php');
include('_authCheck.php');

if( isset($_POST['action']) && $_POST['action'] == 'add_rmaker' ) {
    $sql = "INSERT INTO fmb_roti_maker (`its_no`, `full_name`, `code`, `mobile_no`) VALUES ('" . $_POST['its_no'] . "', '" . $_POST['full_name'] . "', '" . $_POST['code'] . "', '" . $_POST['mobile_no'] . "')"; 
    mysqli_query($link, $sql) or die(mysqli_error($link));
    header("Location: /fmb/users/rotimaker.php?action=add&full_name=".$_POST['full_name']);
}

if( isset($_POST['action']) && $_POST['action'] == 'edit_rmaker' ) {
    $sql = "UPDATE fmb_roti_maker SET `its_no` = '".$_POST['its_no']."', `full_name` = '".$_POST['full_name']."', `code` = '".$_POST['code']."', `mobile_no` = '".$_POST['mobile_no']."' WHERE `id` = '".$_POST['rmaker_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/rotimaker.php?action=edit&full_name=".$_POST['full_name']);
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_rmaker' ) {
    $sql = "DELETE FROM fmb_roti_maker WHERE `id` = '".$_POST['rmaker_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/rotimaker.php?action=delete&full_name=".$_POST['full_name']);
}
?>

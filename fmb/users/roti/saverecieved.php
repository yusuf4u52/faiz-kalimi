<?php
include('../connection.php');
include('../_authCheck.php');

if( isset($_POST['action']) ) {
    if( $_POST['action'] == 'add_rrecieved' ) {
        $arecieved = mysqli_query($link, "SELECT * FROM fmb_roti_recieved WHERE `recieved_date` = '".$_POST['recieved_date']."' AND `maker_id` = '" . $_POST['maker_id'] . "' order by `recieved_date` DESC LIMIT 1") or die(mysqli_error($link));
        if ($arecieved->num_rows > 0) {
            $row_arecieved = $arecieved->fetch_assoc();
            $sql = "UPDATE  fmb_roti_recieved SET `roti_recieved` = '" . $_POST['roti_recieved'] . "', `roti_status` = '" . $_POST['roti_status'] . "', `recieved_by` = '" . $_POST['recieved_by'] . "' WHERE `id` = '".$row_arecieved['id']."'";
            mysqli_query($link,$sql) or die(mysqli_error($link));
            header("Location: /fmb/users/roti/recieved.php?action=edit&maker=".$_POST['maker_id']."&recieved_date=".$_POST['recieved_date']);
        } else {
            $sql = "INSERT INTO  fmb_roti_recieved (`maker_id`, `recieved_date`, `roti_recieved`, `roti_status`, `recieved_by`) VALUES ('" . $_POST['maker_id'] . "', '" . $_POST['recieved_date'] . "', '" . $_POST['roti_recieved'] . "', '" . $_POST['roti_status'] . "', '" . $_POST['recieved_by'] . "')"; 
            mysqli_query($link, $sql) or die(mysqli_error($link));
            header("Location: /fmb/users/roti/recieved.php?action=add&maker=".$_POST['maker_id']."&recieved_date=".$_POST['recieved_date']); 
        }
    }

    if( $_POST['action'] == 'edit_rrecieved' ) {
        $sql = "UPDATE  fmb_roti_recieved SET `roti_recieved` = '" . $_POST['roti_recieved'] . "', `roti_status` = '" . $_POST['roti_status'] . "', `recieved_by` = '" . $_POST['recieved_by'] . "' WHERE `id` = '".$_POST['rrecieved_id']."'";
        mysqli_query($link,$sql) or die(mysqli_error($link));
        header("Location: /fmb/users/roti/recieved.php?action=edit&maker=".$_POST['maker_id']."&recieved_date=".$_POST['recieved_date']);
    }

    if( $_POST['action'] == 'delete_rrecieved' ) {
        $sql = "DELETE FROM  fmb_roti_recieved WHERE `id` = '".$_POST['rrecieved_id']."'";
        mysqli_query($link,$sql) or die(mysqli_error($link));
        header("Location: /fmb/users/roti/recieved.php?action=delete&maker=".$_POST['maker_id']."&recieved_date=".$_POST['recieved_date']);
    }
}
?>

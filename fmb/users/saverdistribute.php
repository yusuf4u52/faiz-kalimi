<?php
include('connection.php');
include('_authCheck.php');

if( isset($_POST['action']) && $_POST['action'] == 'add_rdistribute' ) {
    $recieved = mysqli_query($link, "SELECT * FROM fmb_roti_recieved WHERE `recieved_date` <= '".$_POST['distribution_date']."' AND `maker_id` = '" . $_POST['maker_id'] . "' order by `recieved_date` DESC LIMIT 1") or die(mysqli_error($link));
    if ($recieved->num_rows > 0) {
        $row_recieved = $recieved->fetch_assoc();
        $flour_left = $row_recieved['flour_left'];
        $oil_left = $row_recieved['oil_left'];
    } else {
        $flour_left = 0;
        $oil_left = 0;
    }
    $sql = "INSERT INTO  fmb_roti_distribution (`maker_id`, `distribution_date`, `flour_distributed`, `flour_left`, `oil_distributed`, `oil_left`, `distributed_by`) VALUES ('" . $_POST['maker_id'] . "', '" . $_POST['distribution_date'] . "', '" . $_POST['flour_distributed'] . "', '" . $flour_left . "', '" . $_POST['oil_distributed'] . "', '" . $oil_left . "', '" . $_POST['distributed_by'] . "')"; 
    mysqli_query($link, $sql) or die(mysqli_error($link));
    header("Location: /fmb/users/rotidistribute.php?action=add&maker=".$_POST['maker_id']."&distribution_date=".$_POST['distribution_date']);
}

if( isset($_POST['action']) && $_POST['action'] == 'edit_rdistribute' ) {
    $recieved = mysqli_query($link, "SELECT * FROM fmb_roti_recieved WHERE `recieved_date` <= '".$_POST['distribution_date']."' AND `maker_id` = '" . $_POST['maker_id'] . "' order by `recieved_date` DESC LIMIT 1") or die(mysqli_error($link));
    if ($recieved->num_rows > 0) {
        $row_recieved = $recieved->fetch_assoc();
        $flour_left = $row_recieved['flour_left'];
        $oil_left = $row_recieved['oil_left'];
    } else {
        $flour_left = 0;
        $oil_left = 0;
    }
   $sql = "UPDATE  fmb_roti_distribution SET `maker_id` = '".$_POST['maker_id']."', `distribution_date` = '".$_POST['distribution_date']."', `flour_distributed` = '".$_POST['flour_distributed']."', `flour_left` = '".$flour_left."', `oil_distributed` = '".$_POST['oil_distributed']."', `oil_left` = '".$oil_left."', `distributed_by` = '".$_POST['distributed_by']."' WHERE `id` = '".$_POST['rdistribute_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/rotidistribute.php?action=edit&maker=".$_POST['maker_id']."&distribution_date=".$_POST['distribution_date']);
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_rdistribute' ) {
    $sql = "DELETE FROM  fmb_roti_distribution WHERE `id` = '".$_POST['rdistribute_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/rotidistribute.php?action=delete&maker=".$_POST['maker_id']."&distribution_date=".$_POST['distribution_date']);
}
?>

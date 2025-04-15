<?php
include('connection.php');
include('_authCheck.php');

if( isset($_POST['action']) && $_POST['action'] == 'add_rrecieved' ) {
    $distribution = mysqli_query($link, "SELECT * FROM fmb_roti_distribution WHERE `distribution_date` <= '".$_POST['recieved_date']."' AND `maker_id` = '" . $_POST['maker_id'] . "' order by `distribution_date` DESC LIMIT 1") or die(mysqli_error($link));
    if ($distribution->num_rows > 0) {
        $flour_per_roti = 0.025;
        $oil_per_roti = 0.0025;
        $flour_required = $_POST['roti_recieved'] * $flour_per_roti;
        $oil_required = $_POST['roti_recieved'] * $oil_per_roti;

        $row_distribution = $distribution->fetch_assoc();
        $recieved = mysqli_query($link, "SELECT * FROM fmb_roti_recieved WHERE `recieved_date` <= '".$_POST['recieved_date']."' AND `maker_id` = '" . $_POST['maker_id'] . "' order by `recieved_date` DESC LIMIT 1") or die(mysqli_error($link));
        if ($recieved->num_rows > 0) {
            $row_recieved = $recieved->fetch_assoc();
            if($row_distribution['distribution_date'] > $row_recieved['recieved_date']) {
                $flour_left = $row_distribution['flour_distributed'] + $row_distribution['flour_left'] - $flour_required;
                $oil_left = $row_distribution['oil_distributed'] + $row_distribution['oil_left'] - $oil_required;
            } else {
                echo $flour_left = $row_recieved['flour_left'] - $flour_required;
                echo $oil_left = $row_recieved['oil_left'] - $oil_required;
            }
        } else {
            $flour_left = $row_distribution['flour_distributed'] + $row_distribution['flour_left'] - $flour_required;
            $oil_left = $row_distribution['oil_distributed'] + $row_distribution['oil_left'] - $oil_required;
        }
    }
    $sql = "INSERT INTO  fmb_roti_recieved (`maker_id`, `recieved_date`, `roti_recieved`, `flour_left`, `oil_left`, `recieved_by`) VALUES ('" . $_POST['maker_id'] . "', '" . $_POST['recieved_date'] . "', '" . $_POST['roti_recieved'] . "', '" . $flour_left . "', '" . $oil_left . "', '" . $_POST['recieved_by'] . "')"; 
    mysqli_query($link, $sql) or die(mysqli_error($link));
    header("Location: /fmb/users/rotirecieved.php?action=add&maker=".$_POST['maker_id']."&recieved_date=".$_POST['recieved_date']);
}

if( isset($_POST['action']) && $_POST['action'] == 'edit_rrecieved' ) {
    $distribution = mysqli_query($link, "SELECT * FROM fmb_roti_distribution WHERE `distribution_date` <= '".$_POST['recieved_date']."' AND `maker_id` = '" . $_POST['maker_id'] . "' order by `distribution_date` DESC LIMIT 1") or die(mysqli_error($link));
    if ($distribution->num_rows > 0) {
        $flour_per_roti = 0.025;
        $oil_per_roti = 0.0025;
        $flour_required = $_POST['roti_recieved'] * $flour_per_roti;
        $oil_required = $_POST['roti_recieved'] * $oil_per_roti;

        $row_distribution = $distribution->fetch_assoc();
        $recieved = mysqli_query($link, "SELECT * FROM fmb_roti_recieved WHERE `recieved_date` <= '".$_POST['recieved_date']."' AND `maker_id` = '" . $_POST['maker_id'] . "' order by `recieved_date` DESC LIMIT 1") or die(mysqli_error($link));
        if ($recieved->num_rows > 0) {
            $row_recieved = $recieved->fetch_assoc();
            if($row_distribution['distribution_date'] > $row_recieved['recieved_date']) {
                $flour_left = $row_distribution['flour_distributed'] + $row_distribution['flour_left'] - $flour_required;
                $oil_left = $row_distribution['oil_distributed'] + $row_distribution['oil_left'] - $oil_required;
            } else {
                $flour_left = $row_recieved['flour_left'] - $flour_required;
                $oil_left = $row_recieved['oil_left'] - $oil_required;
            }
        } else {
            $flour_left = $row_distribution['flour_distributed'] + $row_distribution['flour_left'] - $flour_required;
            $oil_left = $row_distribution['oil_distributed'] + $row_distribution['oil_left'] - $oil_required;
        }
    }
    $sql = "UPDATE  fmb_roti_recieved SET `maker_id` = '".$_POST['maker_id']."', `recieved_date` = '".$_POST['recieved_date']."', `roti_recieved` = '".$_POST['roti_recieved']."', `flour_left` = '".$flour_left."', `oil_left` = '".$oil_left."', `recieved_by` = '" . $_POST['recieved_by'] . "' WHERE `id` = '".$_POST['rrecieved_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/rotirecieved.php?action=edit&maker=".$_POST['maker_id']."&recieved_date=".$_POST['recieved_date']);
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_rrecieved' ) {
    $sql = "DELETE FROM  fmb_roti_recieved WHERE `id` = '".$_POST['rrecieved_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/rotirecieved.php?action=delete&maker=".$_POST['maker_id']."&recieved_date=".$_POST['recieved_date']);
}
?>

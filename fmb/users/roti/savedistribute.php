<?php
include('../connection.php');
include('../_authCheck.php');

if( isset($_POST['action']) ) {
    $current_date  = date('Y-m-d');
    $adistribution = mysqli_query($link, "SELECT * FROM fmb_roti_distribution WHERE `distribution_date` < '".$_POST['distribution_date']."' AND `maker_id` = '" . $_POST['maker_id'] . "' order by `distribution_date` DESC LIMIT 1") or die(mysqli_error($link));
    if ($adistribution->num_rows > 0) {
        $row_adistribution = $adistribution->fetch_assoc();  
        $recieved = mysqli_query($link, "SELECT SUM(roti_recieved) AS total_roti FROM fmb_roti_recieved WHERE `recieved_date` BETWEEN '" . $row_adistribution['distribution_date'] . "'  AND '" . $_POST['distribution_date']. "' AND `maker_id` = '" . $_POST['maker_id'] . "' AND `roti_status` = 'recieved' order by `recieved_date` DESC") or die(mysqli_error($link));
        if ($recieved->num_rows > 0) {
            $row_recieved = $recieved->fetch_assoc();
            $total_roti = $row_recieved['total_roti'];
            $flour_per_roti = 0.025;
            $oil_per_roti = 0.0025;
            $flour_required = $total_roti * $flour_per_roti;
            $oil_required = $total_roti * $oil_per_roti;
            $flour_left = $row_adistribution['flour_distributed'] + $row_adistribution['flour_left'] - $flour_required;
            $oil_left = $row_adistribution['oil_distributed'] + $row_adistribution['oil_left'] - $oil_required;
        } else {
            $flour_left = $row_adistribution['flour_distributed'] + $row_adistribution['flour_left'];
            $oil_left = $row_adistribution['oil_distributed'] + $row_adistribution['oil_left'];
        }
    } else {
        $flour_left = 0;
        $oil_left = 0;
    }

    if( $_POST['flour_left'] != 0 ) {
        $flour_left = $_POST['flour_left'];
    } 

    if( $_POST['oil_left'] != 0 ) {
        $oil_left = $_POST['oil_left'];
    }

    if( $_POST['action'] == 'add_rdistribute' ) {

        $distribution = mysqli_query($link, "SELECT * FROM fmb_roti_distribution WHERE `distribution_date` = '".$_POST['distribution_date']."' AND `maker_id` = '" . $_POST['maker_id'] . "' order by `distribution_date` DESC LIMIT 1") or die(mysqli_error($link));
        if ($distribution->num_rows > 0) { 
            $row_distribution = $distribution->fetch_assoc();  
            $sql = "UPDATE  fmb_roti_distribution SET `flour_distributed` = '".$_POST['flour_distributed']."', `flour_left` = '".$flour_left."', `oil_distributed` = '".$_POST['oil_distributed']."', `oil_left` = '".$oil_left."', `distributed_by` = '".$_POST['distributed_by']."' WHERE `id` = '".$row_distribution['id']."'";
            mysqli_query($link,$sql) or die(mysqli_error($link));
            header("Location: /fmb/users/roti/distribute.php?action=edit&maker=".$_POST['maker_id']."&distribution_date=".$_POST['distribution_date']);
        } else {
            $sql = "INSERT INTO  fmb_roti_distribution (`maker_id`, `distribution_date`, `flour_distributed`, `flour_left`, `oil_distributed`, `oil_left`, `distributed_by`) VALUES ('" . $_POST['maker_id'] . "', '" . $_POST['distribution_date'] . "', '" . $_POST['flour_distributed'] . "', '" . $flour_left . "', '" . $_POST['oil_distributed'] . "', '" . $oil_left . "', '" . $_POST['distributed_by'] . "')"; 
            mysqli_query($link, $sql) or die(mysqli_error($link));
            header("Location: /fmb/users/roti/distribute.php?action=add&maker=".$_POST['maker_id']."&distribution_date=".$_POST['distribution_date']);
        }
    }

    if( $_POST['action'] == 'edit_rdistribute' ) {
        $sql = "UPDATE  fmb_roti_distribution SET `maker_id` = '".$_POST['maker_id']."', `distribution_date` = '".$_POST['distribution_date']."', `flour_distributed` = '".$_POST['flour_distributed']."', `flour_left` = '".$flour_left."', `oil_distributed` = '".$_POST['oil_distributed']."', `oil_left` = '".$oil_left."', `distributed_by` = '".$_POST['distributed_by']."' WHERE `id` = '".$_POST['rdistribute_id']."'";
        mysqli_query($link,$sql) or die(mysqli_error($link));
        header("Location: /fmb/users/roti/distribute.php?action=edit&maker=".$_POST['maker_id']."&distribution_date=".$_POST['distribution_date']);
    }

    if( $_POST['action'] == 'delete_rdistribute' ) {
        $sql = "DELETE FROM  fmb_roti_distribution WHERE `id` = '".$_POST['rdistribute_id']."'";
        mysqli_query($link,$sql) or die(mysqli_error($link));
        header("Location: /fmb/users/roti/distribute.php?action=delete&maker=".$_POST['maker_id']."&distribution_date=".$_POST['distribution_date']);
    }
}

?>

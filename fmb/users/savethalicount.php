<?php
include('connection.php');
include('_authCheck.php');

if( isset($_POST['action']) && $_POST['action'] == 'add_tcount' ) {

    $atcount = mysqli_query($link, "SELECT * FROM transporter_thali_count WHERE `count_date` = '".$_POST['count_date']."' AND `transporter_id` = '" . $_POST['transporter_id'] . "' order by `count_date` DESC LIMIT 1") or die(mysqli_error($link));
    if ($atcount->num_rows > 0) {
        $row_atcount = $atcount->fetch_assoc();
        $sql = "UPDATE  transporter_thali_count SET `thali_count` = '".$_POST['thali_count']."', `counted_by` = '" . $_POST['counted_by'] . "' WHERE `id` = '".$row_atcount['id']."'";
        mysqli_query($link,$sql) or die(mysqli_error($link));
        header("Location: /fmb/users/transporterthalicount.php?action=edit&transporter=".$_POST['transporter_id']."&count_date=".$_POST['count_date']);
    } else {
        $sql = "INSERT INTO  transporter_thali_count (`transporter_id`, `count_date`, `thali_count`, `counted_by`) VALUES ('" . $_POST['transporter_id'] . "', '" . $_POST['count_date'] . "', '" . $_POST['thali_count'] . "', '" . $_POST['counted_by'] . "')"; 
        mysqli_query($link, $sql) or die(mysqli_error($link));
        header("Location: /fmb/users/transporterthalicount.php?action=add&transporter=".$_POST['transporter_id']."&count_date=".$_POST['count_date']); 
    }

}

if( isset($_POST['action']) && $_POST['action'] == 'edit_tcount' ) {
    $sql = "UPDATE  transporter_thali_count SET `transporter_id` = '".$_POST['transporter_id']."', `count_date` = '".$_POST['count_date']."', `thali_count` = '".$_POST['thali_count']."', `counted_by` = '" . $_POST['counted_by'] . "' WHERE `id` = '".$_POST['tcount_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/transporterthalicount.php?action=edit&transporter=".$_POST['transporter_id']."&count_date=".$_POST['count_date']);
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_tcount' ) {
    $sql = "DELETE FROM  transporter_thali_count WHERE `id` = '".$_POST['tcount_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/transporterthalicount.php?action=delete&transporter=".$_POST['transporter_id']."&count_date=".$_POST['count_date']);
}
?>

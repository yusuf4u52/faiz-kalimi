<?php
include('connection.php');
include('_authCheck.php');

if( isset($_POST['action']) && $_POST['action'] == 'add_transporter' ) {
    $sql = "INSERT INTO transporters (`Name`, `Mobile`) VALUES ('" . $_POST['Name'] . "''" . $_POST['Mobile'] . "')"; 
    mysqli_query($link, $sql) or die(mysqli_error($link));
    header("Location: /fmb/users/transporter.php?action=add&Name=".$_POST['Name']);
}

if( isset($_POST['action']) && $_POST['action'] == 'edit_transporter' ) {
    $sql = "UPDATE transporters SET `Name` = '".$_POST['Name']."', `Mobile` = '".$_POST['Mobile']."' WHERE `id` = '".$_POST['transporter_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/transporter.php?action=edit&Name=".$_POST['Name']);
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_transporter' ) {
    $sql = "DELETE FROM transporters WHERE `id` = '".$_POST['transporter_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/transporter.php?action=delete&Name=".$_POST['Name']);
}
?>

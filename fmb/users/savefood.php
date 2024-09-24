<?php
include('connection.php');
include('_authCheck.php');

if( isset($_POST['action']) && $_POST['action'] == 'add_food' ) {
    $sql = "INSERT INTO food_list (`dish_name`, `dish_type`) VALUES ('" . $_POST['dish_name'] . "', '" . $_POST['dish_type'] . "')"; 
    mysqli_query($link, $sql) or die(mysqli_error($link));
    header("Location: /fmb/users/foodlist.php?action=add&dish=".$_POST['dish_name']);
}

if( isset($_POST['action']) && $_POST['action'] == 'edit_food' ) {
    $sql = "UPDATE food_list SET `dish_name` = '".$_POST['dish_name']."', `dish_type` = '".$_POST['dish_type']."' WHERE `id` = '".$_POST['food_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/foodlist.php?action=edit&dish=".$_POST['dish_name']);
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_food' ) {
    $sql = "DELETE FROM food_list WHERE `id` = '".$_POST['food_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/foodlist.php?action=delete&dish=".$_POST['dish_name']);
}
?>

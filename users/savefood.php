<?php
include('connection.php');

if( isset($_POST['action']) && $_POST['action'] == 'add_food' ) {
    $sql = "INSERT INTO food_list (`dish_name`, `dish_type`) VALUES ('" . $_POST['dish_name'] . "', '" . $_POST['dish_type'] . "')"; 
    mysqli_query($link, $sql) or die(mysqli_error($link));
    header("Location: /fmb/users/food_list.php");
}

if( isset($_POST['action']) && $_POST['action'] == 'edit_food' ) {
    $sql = "UPDATE food_list SET `dish_name` = '".$_POST['dish_name']."', `dish_type` = '".$_POST['dish_type']."' WHERE `id` = '".$_POST['food_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/food_list.php");
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_food' ) {
    $sql = "DELETE FROM food_list WHERE `id` = '".$_POST['food_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/food_list.php");
}
?>

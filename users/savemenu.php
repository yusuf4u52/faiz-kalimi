<?php
include('connection.php');


if( isset($_POST['menu_type']) && isset($_POST['menu_item']) ) {

    $menu_item = array();

    if($_POST['menu_type'] == 'miqaat') {

        if( !empty($_POST['menu_item']['miqaat'])) {
            $menu_item['miqaat'] = $_POST['menu_item']['miqaat'];
        }

    } elseif($_POST['menu_type'] == 'thaali') {

        if( !empty($_POST['menu_item']['sabji']['item'])) {
            $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_name` = '".$_POST['menu_item']['sabji']['item']."' AND `dish_type` = '1'") or die(mysqli_error($link));
            if($result->num_rows == 0) {
                $sql = "INSERT INTO food_list (`dish_name`, `dish_type`) VALUES ('" . $_POST['menu_item']['sabji']['item'] . "', '1')"; 
                mysqli_query($link, $sql) or die(mysqli_error($link));
            } mysqli_free_result($result);
            $menu_item['sabji']['item'] = $_POST['menu_item']['sabji']['item'];
            $menu_item['sabji']['qty'] = $_POST['menu_item']['sabji']['qty'];
        }
        
        if( !empty($_POST['menu_item']['tarkari']['item'])) {
            $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_name` = '".$_POST['menu_item']['tarkari']['item']."' AND `dish_type` = '2'") or die(mysqli_error($link));
            if($result->num_rows == 0) {
                $sql = "INSERT INTO food_list (`dish_name`, `dish_type`) VALUES ('" . $_POST['menu_item']['tarkari']['item'] . "', '2')"; 
                mysqli_query($link, $sql) or die(mysqli_error($link));
            } mysqli_free_result($result);
            $menu_item['tarkari']['item'] = $_POST['menu_item']['tarkari']['item'];
            $menu_item['tarkari']['qty'] = $_POST['menu_item']['tarkari']['qty'];
        }

        if( !empty($_POST['menu_item']['rice']['item'])) {
            $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_name` = '".$_POST['menu_item']['rice']['item']."' AND `dish_type` = '3'") or die(mysqli_error($link));
            if($result->num_rows == 0) {
                $sql = "INSERT INTO food_list (`dish_name`, `dish_type`) VALUES ('" . $_POST['menu_item']['rice']['item'] . "', '3')"; 
                mysqli_query($link, $sql) or die(mysqli_error($link));
            } mysqli_free_result($result);
            $menu_item['rice']['item'] = $_POST['menu_item']['rice']['item'];
            $menu_item['rice']['qty'] = $_POST['menu_item']['rice']['qty'];
        }

        if( !empty($_POST['menu_item']['roti']['item'])) {
            $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_name` = '".$_POST['menu_item']['roti']['item']."' AND `dish_type` = '4'") or die(mysqli_error($link));
            if($result->num_rows == 0) {
                $sql = "INSERT INTO food_list (`dish_name`, `dish_type`) VALUES ('" . $_POST['menu_item']['roti']['item'] . "', '4')"; 
                mysqli_query($link, $sql) or die(mysqli_error($link));
            } mysqli_free_result($result);
            $menu_item['roti']['item'] = $_POST['menu_item']['roti']['item'];
            $menu_item['roti']['tqty'] = $_POST['menu_item']['roti']['tqty'];
            $menu_item['roti']['sqty'] = $_POST['menu_item']['roti']['sqty'];
            $menu_item['roti']['mqty'] = $_POST['menu_item']['roti']['mqty'];
            $menu_item['roti']['lqty'] = $_POST['menu_item']['roti']['lqty'];
        }

        if( !empty($_POST['menu_item']['extra']['item'])) {
            $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_name` = '".$_POST['menu_item']['extra']['item']."' AND `dish_type` = '1'") or die(mysqli_error($link));
            if($result->num_rows == 0) {
                $sql = "INSERT INTO food_list (`dish_name`, `dish_type`) VALUES ('" . $_POST['menu_item']['extra']['item'] . "', '1')"; 
                mysqli_query($link, $sql) or die(mysqli_error($link));
            } mysqli_free_result($result);
            $menu_item['extra']['item'] = $_POST['menu_item']['extra']['item'];
            $menu_item['extra']['qty'] = $_POST['menu_item']['extra']['qty'];
        }
    }
}

if( isset($_POST['action']) && $_POST['action'] == 'add_menu' ) {
    $sql = "INSERT INTO menu_list (`menu_date`,`menu_type`,`menu_item`) VALUES ('" . $_POST['menu_date'] . "', '" . $_POST['menu_type'] . "', '" . serialize($menu_item) . "')";
    mysqli_query($link, $sql) or die(mysqli_error($link));
    header("Location: /fmb/users/menulist.php?action=add&date=".$_POST['menu_date']);
}

if( isset($_POST['action']) && $_POST['action'] == 'edit_menu' ) {
    $sql = "UPDATE menu_list SET `menu_date` = '".$_POST['menu_date']."', `menu_type` = '" . $_POST['menu_type'] . "', `menu_item` = '".serialize($menu_item)."' WHERE `id` = '".$_POST['menu_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/menulist.php?action=edit&date=".$_POST['menu_date']);
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_menu' ) {
    $sql = "DELETE FROM menu_list WHERE `id` = '".$_POST['menu_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/menulist.php?action=delete&date=".$_POST['menu_date']);
}
?>

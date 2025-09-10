<?php
include('../connection.php');
include('../_authCheck.php');

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
    $user_menu_date = mysqli_query($link, "SELECT `menu_date` FROM menu_list WHERE `menu_date` = '".$_POST['menu_date']."'") or die(mysqli_error($link));
    if(isset($user_menu_date) && $user_menu_date->num_rows > 0) {
        header("Location: /fmb/users/menulist.php?action=existed&date=".$_POST['menu_date']);
    } else {
        $sql = "INSERT INTO menu_list (`menu_date`,`menu_type`,`menu_item`) VALUES ('" . $_POST['menu_date'] . "', '" . $_POST['menu_type'] . "', '" . serialize($menu_item) . "')";
        mysqli_query($link, $sql) or die(mysqli_error($link));
        header("Location: /fmb/users/menu/list.php?action=add&date=".$_POST['menu_date']);
    }
}

if( isset($_POST['action']) && $_POST['action'] == 'edit_menu' ) {
    $user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '".$_POST['menu_date']."'") or die(mysqli_error($link));
    if($user_menu->num_rows > 0) {
        while ($menu_values = mysqli_fetch_assoc($user_menu)) {
            $menu_delete = "DELETE FROM user_menu WHERE `id` = '".$menu_values['id']."'";
            mysqli_query($link,$menu_delete) or die(mysqli_error($link));
        }
    }
    $sql = "UPDATE menu_list SET `menu_date` = '".$_POST['menu_date']."', `menu_type` = '" . $_POST['menu_type'] . "', `menu_item` = '".serialize($menu_item)."' WHERE `id` = '".$_POST['menu_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));
    header("Location: /fmb/users/menu/list.php?action=edit&date=".$_POST['menu_date']);
}

if( isset($_POST['action']) && $_POST['action'] == 'delete_menu' ) {
    $sql = "DELETE FROM menu_list WHERE `id` = '".$_POST['menu_id']."'";
    mysqli_query($link,$sql) or die(mysqli_error($link));

    $sqluser = "DELETE FROM user_menu WHERE `id` = '".$_POST['menu_id']."'";
    mysqli_query($link,$sqluser) or die(mysqli_error($link));
    header("Location: /fmb/users/menu/list.php?action=delete&date=".$_POST['menu_date']);
}
?>

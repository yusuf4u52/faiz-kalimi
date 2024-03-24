<?php
include('connection.php');
include('_authCheck.php');

if( isset($_POST['menu_id']) && isset($_POST['thali']) ) {
    $user_menu_date = mysqli_query($link, "SELECT `menu_date` FROM user_menu WHERE `id` = '".$_POST['menu_id']."' AND `thali` = '".$_POST['thali']."'") or die(mysqli_error($link));
    if(isset($user_menu_date) && $user_menu_date->num_rows > 0) {
        $menu_date = $user_menu_date->fetch_column();
    } else {
        $menu_list = mysqli_query($link, "SELECT `menu_date` FROM menu_list WHERE `id` = '".$_POST['menu_id']."'") or die(mysqli_error($link));
        if(isset($menu_list) && $menu_list->num_rows > 0) {
            $menu_date = $menu_list->fetch_column();
        }
    }
    if( isset($_POST['action']) && $_POST['action'] == 'change_menu' ) {
        $GivenDate = new DateTime($menu_date . '20:00:00');
        $GivenDate->modify('-1 day');
        $GivenDate = $GivenDate->format('Y-m-d H:i:s'); 
        $CurrentDate = date('Y-m-d H:i:s');
    }
    if( isset($_POST['action']) && $_POST['action'] == 'admin_change_menu' ) { 
        $GivenDate = new DateTime($menu_date . '23:59:59');
        $CurrentDate = date('Y-m-d H:i:s');
    }
    if( $CurrentDate < $GivenDate ) { 
        $user_memu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '".$menu_date."' AND `thali` = '".$_POST['thali']."'") or die(mysqli_error($link));
        if($user_memu->num_rows > 0) {
            $row = $user_memu->fetch_assoc();
            $sql = "UPDATE `user_menu` SET `menu_item` = '" . serialize($_POST['menu_item']) . "' WHERE `id` = '".$row['id']."'";
        } else {
            $sql = "INSERT INTO `user_menu` (`thali`,`menu_date`,`menu_item`) VALUES ('" . $_POST['thali'] . "', '" . $menu_date . "', '" . serialize($_POST['menu_item']) . "')";
        }
        mysqli_query($link, $sql) or die(mysqli_error($link));
        $action = 'edit';
        $date = $menu_date;
    } else {
        $action = 'rsvp';
        $date = $menu_date;
    }
    if( isset($_POST['action']) && $_POST['action'] == 'change_menu' ) {
        header("Location: /fmb/users/viewmenu.php?action=".$action."&date=".$menu_date);
    }
    if( isset($_POST['action']) && $_POST['action'] == 'admin_change_menu' ) { 
        header("Location: /fmb/users/thalisearch.php?thalino=".$_POST['thalino']."&general=".$_POST['general']."&year=".$_POST['year']."&action=".$action."&date=".$menu_date);
    }
}
?>

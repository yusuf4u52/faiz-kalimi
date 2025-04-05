<?php
include ('connection.php');
include ('_authCheck.php');

if (isset($_POST['menu_id']) && isset($_POST['thali'])) {
   
    $menu_list = mysqli_query($link, "SELECT `menu_date` FROM menu_list WHERE `id` = '" . $_POST['menu_id'] . "'") or die(mysqli_error($link));
    if (isset($menu_list) && $menu_list->num_rows > 0) {
        $menu_date = $menu_list->fetch_assoc();
        $menu_date = $menu_date['menu_date'];
    }
    
    date_default_timezone_set('Asia/Kolkata');
    if (isset($_POST['action']) && $_POST['action'] == 'change_menu') {
        $GivenDate = new DateTime($menu_date . '20:00:00');
        $GivenDate->modify('-1 day');
        $GivenDate = $GivenDate->format('Y-m-d H:i:s');
        $CurrentDate = date('Y-m-d H:i:s');
    }

    if (isset($_POST['action']) && $_POST['action'] == 'admin_change_menu') {
        $GivenDate = new DateTime($menu_date . '00:00:00');
        $CurrentDate = date('Y-m-d H:i:s');
    }

    if (!empty($CurrentDate) && !empty($GivenDate) && $CurrentDate < $GivenDate) {
        if(isset($_POST['status'])) {
            $menu_item = mysqli_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = '" . $menu_date . "'") or die(mysqli_error($link));
            if ($menu_item->num_rows > 0) {
                $menu_item = $menu_item->fetch_assoc();
                $menu_item = unserialize($menu_item['menu_item']);
                $change = 'no';
                if (!empty($menu_item['sabji']['item'])) {
                    if ($menu_item['sabji']['qty'] !== $_POST['menu_item']['sabji']['qty']) {
                        $change = 'yes';
                    }
                } 
                if (!empty($menu_item['tarkari']['item'])) {
                    if ($menu_item['tarkari']['qty'] !== $_POST['menu_item']['tarkari']['qty']) {
                        $change = 'yes';
                    }
                } 
                if (!empty($menu_item['rice']['item'])) {
                    if ($menu_item['rice']['qty'] !== $_POST['menu_item']['rice']['qty']) {
                        $change = 'yes';
                    }
                }
            }

            $stop_thali = mysqli_query($link, "SELECT * FROM stop_thali WHERE `stop_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'") or die(mysqli_error($link));
            if ($stop_thali->num_rows > 0) {
                $delete_stop = "DELETE FROM stop_thali WHERE `stop_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'";
                mysqli_query($link,$delete_stop) or die(mysqli_error($link));
                $msg = 'start';
            }

            if(isset($change) && $change == 'yes') {
                $user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'") or die(mysqli_error($link));
                if ($user_menu->num_rows > 0) {
                    $row = $user_menu->fetch_assoc();
                    $sql = "UPDATE `user_menu` SET `menu_item` = '" . serialize($_POST['menu_item']) . "' WHERE `id` = '" . $row['id'] . "'";
                } else {
                    $sql = "INSERT INTO `user_menu` (`thali`,`menu_date`,`menu_item`) VALUES ('" . $_POST['thali'] . "', '" . $menu_date . "', '" . serialize($_POST['menu_item']) . "')";
                }
                mysqli_query($link, $sql) or die(mysqli_error($link));
                if(isset($msg) && $msg == 'start') {
                    $action = 'sedit';
                } else {
                    $action = 'edit';
                }
                $date = $menu_date;
            } else {
                $user_r_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'") or die(mysqli_error($link));
                if (isset($user_r_menu) && $user_r_menu->num_rows > 0) {
                    $sql = "DELETE FROM user_menu WHERE `menu_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'";
                    mysqli_query($link,$sql) or die(mysqli_error($link));
                }
                if(isset($msg) && $msg == 'start') {
                    $action = 'snochange';
                } else {
                    $action = 'nochange';
                }
                $date = $menu_date;
            }
        } else {
            $stop_thali = mysqli_query($link, "SELECT * FROM stop_thali WHERE `stop_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'") or die(mysqli_error($link));
            if ($stop_thali->num_rows > 0) {
                $user_s_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'") or die(mysqli_error($link));
                if (isset($user_s_menu) && $user_s_menu->num_rows > 0) {
                    $user_del = "DELETE FROM user_menu WHERE `menu_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'";
                    mysqli_query($link,$user_del) or die(mysqli_error($link));
                }
                $action = 'astop';
                $date = $menu_date;
            } else {
                $insert_stop = "INSERT INTO `stop_thali` (`thali`,`stop_date`) VALUES ('" . $_POST['thali'] . "', '" . $menu_date . "')";
                mysqli_query($link, $insert_stop) or die(mysqli_error($link));
                $user_s_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'") or die(mysqli_error($link));
                if (isset($user_s_menu) && $user_s_menu->num_rows > 0) {
                    $user_del = "DELETE FROM user_menu WHERE `menu_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'";
                    mysqli_query($link,$user_del) or die(mysqli_error($link));
                }
                $action = 'stop';
                $date = $menu_date;
            }
        }
    } else {
        $action = 'rsvp';
        $date = $menu_date;
    }

    if (isset($_POST['action']) && $_POST['action'] == 'change_menu') {
        header("Location: /fmb/users/index.php?action=" . $action . "&date=" . $menu_date);
    }

    if (isset($_POST['action']) && $_POST['action'] == 'admin_change_menu') {
        header("Location: /fmb/users/thalisearch.php?thalino=" . $_POST['thali'] . "&tiffinno=" . $_POST['tiffinno'] .  "&general=" . $_POST['general'] . "&year=" . $_POST['year'] . "&action=" . $action . "&date=" . $menu_date);
    }

    if (isset($_POST['action']) && $_POST['action'] == 'feedback_menu') {
        $user_feedmenu = mysqli_query($link, "SELECT * FROM user_feedmenu WHERE `menu_date` = '" . $menu_date . "' AND `thali` = '" . $_POST['thali'] . "'") or die(mysqli_error($link));
        if ($user_feedmenu->num_rows > 0) {
            $row = $user_feedmenu->fetch_assoc();
            $sql = "UPDATE `user_feedmenu` SET `menu_feed` = '" . serialize($_POST['menu_item']) . "', `feedback` = '" . $_POST['feedback'] . "' WHERE `id` = '" . $row['id'] . "'";
            $action = 'editfeed';
        } else {
            $sql = "INSERT INTO `user_feedmenu` (`thali`,`menu_date`,`menu_feed`,`feedback`) VALUES ('" . $_POST['thali'] . "', '" . $menu_date . "', '" . serialize($_POST['menu_item']) . "', '" . $_POST['feedback'] . "')";
            $action = 'addfeed';
        }
        mysqli_query($link, $sql) or die(mysqli_error($link));
        $date = $menu_date;
        header("Location: /fmb/users/index.php?action=" . $action . "&date=" . $menu_date);
    }
}
?>

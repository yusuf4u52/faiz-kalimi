<?php
include ('connection.php');

$user_menu = mysqli_query($link, "SELECT `id`, `thali` FROM user_menu") or die(mysqli_error($link));
while ($menu = mysqli_fetch_assoc($user_menu)) {
    $thalilist = mysqli_query($link, "SELECT `id`, `Thali` FROM thalilist WHERE `Thali` = '" . $menu['thali'] . "'") or die(mysqli_error($link));
    if ($thalilist->num_rows > 0) {
      $row = $thalilist->fetch_assoc();
      $sql = "UPDATE `user_menu` SET `thali` = '" . $row['id'] . "' WHERE `thali` = '" . $row['Thali'] . "'";
      mysqli_query($link, $sql) or die(mysqli_error($link));
    } else {
        $sql = "DELETE FROM user_menu WHERE `thali` = '" . $menu['thali'] . "'";
        mysqli_query($link,$sql) or die(mysqli_error($link));
    }
}

$user_feedmenu = mysqli_query($link, "SELECT `id`, `thali` FROM user_feedmenu") or die(mysqli_error($link));
while ($feed = mysqli_fetch_assoc($user_feedmenu)) {
    $thalilist = mysqli_query($link, "SELECT `id`, `Thali` FROM thalilist WHERE `Thali` = '" . $feed['thali'] . "'") or die(mysqli_error($link));
    if ($thalilist->num_rows > 0) {
      $row = $thalilist->fetch_assoc();
      $sql = "UPDATE `user_feedmenu` SET `thali` = '" . $row['id'] . "' WHERE `thali` = '" . $row['Thali'] . "'";
      mysqli_query($link, $sql) or die(mysqli_error($link));
    } else {
        $sql = "DELETE FROM `user_feedmenu` WHERE `thali` = '" . $feed['thali'] . "'";
        mysqli_query($link,$sql) or die(mysqli_error($link));
    }
}

$stop_thali = mysqli_query($link, "SELECT `id`, `thali` FROM stop_thali") or die(mysqli_error($link));
while ($stop = mysqli_fetch_assoc($stop_thali)) {
    $thalilist = mysqli_query($link, "SELECT `id`, `Thali` FROM thalilist WHERE `Thali` = '" . $stop['thali'] . "'") or die(mysqli_error($link));
    if ($thalilist->num_rows > 0) {
      $row = $thalilist->fetch_assoc();
      $sql = "UPDATE `stop_thali` SET `thali` = '" . $row['id'] . "' WHERE `thali` = '" . $row['Thali'] . "'";
      mysqli_query($link, $sql) or die(mysqli_error($link));
    } else {
        $sql = "DELETE FROM `stop_thali` WHERE `thali` = '" . $stop['thali'] . "'";
        mysqli_query($link,$sql) or die(mysqli_error($link));
    }
}

header("Location: /fmb/users/index.php");
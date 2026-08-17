<?php
include('../connection.php');
include('../_authCheck.php');
require_once('helpers.php');

$action = $_POST['action'] ?? null;

if ($action === 'add_food') {
    $dishName = trim((string) ($_POST['dish_name'] ?? ''));
    $dishType = (string) ($_POST['dish_type'] ?? '');

    if ($dishName === '' || !in_array($dishType, VALID_DISH_TYPES, true)) {
        header("Location: /testfmb/users/menu/food.php?action=error");
        exit;
    }

    // With the UNIQUE KEY (dish_name, dish_type) added in
    // schema-modernization.sql, adding the same dish twice now fails at the
    // database level instead of silently creating a duplicate row — catch
    // that and send back a friendly message instead of a fatal error.
    try {
        db_query(
            $link,
            "INSERT INTO food_list (`dish_name`, `dish_type`) VALUES (?, ?)",
            "ss",
            [$dishName, $dishType]
        );
    } catch (RuntimeException $e) {
        if (mysqli_errno($link) === 1062) { // ER_DUP_ENTRY
            header("Location: /testfmb/users/menu/food.php?action=duplicate&dish=" . urlencode($dishName));
            exit;
        }
        throw $e;
    }
    header("Location: /testfmb/users/menu/food.php?action=add&dish=" . urlencode($dishName));
    exit;
}

if ($action === 'edit_food') {
    $dishName = trim((string) ($_POST['dish_name'] ?? ''));
    $dishType = (string) ($_POST['dish_type'] ?? '');
    $foodId = (int) ($_POST['food_id'] ?? 0);

    if ($dishName === '' || !in_array($dishType, VALID_DISH_TYPES, true) || $foodId <= 0) {
        header("Location: /testfmb/users/menu/food.php?action=error");
        exit;
    }

    try {
        db_query(
            $link,
            "UPDATE food_list SET `dish_name` = ?, `dish_type` = ? WHERE `id` = ?",
            "ssi",
            [$dishName, $dishType, $foodId]
        );
    } catch (RuntimeException $e) {
        if (mysqli_errno($link) === 1062) { // ER_DUP_ENTRY
            header("Location: /testfmb/users/menu/food.php?action=duplicate&dish=" . urlencode($dishName));
            exit;
        }
        throw $e;
    }
    header("Location: /testfmb/users/menu/food.php?action=edit&dish=" . urlencode($dishName));
    exit;
}

if ($action === 'delete_food') {
    $foodId = (int) ($_POST['food_id'] ?? 0);
    $dishName = trim((string) ($_POST['dish_name'] ?? ''));

    if ($foodId <= 0) {
        header("Location: /testfmb/users/menu/food.php?action=error");
        exit;
    }

    db_query($link, "DELETE FROM food_list WHERE `id` = ?", "i", [$foodId]);
    header("Location: /testfmb/users/menu/food.php?action=delete&dish=" . urlencode($dishName));
    exit;
}

// No recognised action — send the user back rather than rendering a blank page.
header("Location: /testfmb/users/menu/food.php");
exit;

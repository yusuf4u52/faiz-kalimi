<?php
include('../connection.php');
include('../_authCheck.php');
require_once('helpers.php');

$menuType = $_POST['menu_type'] ?? null;
$menuItemInput = $_POST['menu_item'] ?? [];

$menu_item = [];

if ($menuType !== null && !in_array($menuType, VALID_MENU_TYPES, true)) {
    header("Location: /fmb/users/menu/list.php?action=error");
    exit;
}

if ($menuType === 'miqaat') {
    if (!empty($menuItemInput['miqaat'])) {
        $menu_item['miqaat'] = trim((string) $menuItemInput['miqaat']);
    }
} elseif ($menuType === 'thaali') {

    /**
     * Ensure a dish exists in food_list, inserting it if new — as a single
     * atomic upsert instead of SELECT-then-INSERT. Relies on the
     * UNIQUE KEY (dish_name, dish_type) added in schema-modernization.sql;
     * the ON DUPLICATE KEY UPDATE clause is a harmless no-op write that
     * exists only to make this a valid upsert (MySQL requires the clause
     * to do *something* — id=id is the standard idiom for "match and skip").
     */
    $ensureDish = function (string $dishName, string $dishType) use ($link): void {
        db_query(
            $link,
            "INSERT INTO food_list (`dish_name`, `dish_type`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `id` = `id`",
            "ss",
            [$dishName, $dishType]
        );
    };

    if (!empty($menuItemInput['sabji']['item'])) {
        $item = trim((string) $menuItemInput['sabji']['item']);
        $ensureDish($item, '1');
        $menu_item['sabji']['item'] = $item;
        $menu_item['sabji']['qty'] = (int) ($menuItemInput['sabji']['qty'] ?? 0);
    }

    if (!empty($menuItemInput['tarkari']['item'])) {
        $item = trim((string) $menuItemInput['tarkari']['item']);
        $ensureDish($item, '2');
        $menu_item['tarkari']['item'] = $item;
        $menu_item['tarkari']['qty'] = (int) ($menuItemInput['tarkari']['qty'] ?? 0);
    }

    if (!empty($menuItemInput['rice']['item'])) {
        $item = trim((string) $menuItemInput['rice']['item']);
        $ensureDish($item, '3');
        $menu_item['rice']['item'] = $item;
        $menu_item['rice']['qty'] = (int) ($menuItemInput['rice']['qty'] ?? 0);
    }

    if (!empty($menuItemInput['roti']['item'])) {
        $item = trim((string) $menuItemInput['roti']['item']);
        $ensureDish($item, '4');
        $menu_item['roti']['item'] = $item;
        $menu_item['roti']['tqty'] = (int) ($menuItemInput['roti']['tqty'] ?? 0);
        $menu_item['roti']['sqty'] = (int) ($menuItemInput['roti']['sqty'] ?? 0);
        $menu_item['roti']['mqty'] = (int) ($menuItemInput['roti']['mqty'] ?? 0);
        $menu_item['roti']['lqty'] = (int) ($menuItemInput['roti']['lqty'] ?? 0);
    }

    if (!empty($menuItemInput['extra']['item'])) {
        $item = trim((string) $menuItemInput['extra']['item']);
        // Preserved from the original: extras are stored under dish_type '1' too.
        $ensureDish($item, '1');
        $menu_item['extra']['item'] = $item;
        $menu_item['extra']['qty'] = (int) ($menuItemInput['extra']['qty'] ?? 0);
    }
}

$action = $_POST['action'] ?? null;

if ($action === 'add_menu') {
    $menuDate = (string) ($_POST['menu_date'] ?? '');

    if ($menuDate === '' || !DateTime::createFromFormat('Y-m-d', $menuDate)) {
        header("Location: /fmb/users/menu/list.php?action=error");
        exit;
    }

    // Attempt the insert directly and rely on the UNIQUE KEY (menu_date) from
    // schema-modernization.sql to reject a duplicate date, instead of a
    // separate SELECT-then-INSERT (which leaves a race window between two
    // people saving the same date at once).
    try {
        db_query(
            $link,
            "INSERT INTO menu_list (`menu_date`, `menu_type`, `menu_item`) VALUES (?, ?, ?)",
            "sss",
            [$menuDate, $menuType, encode_menu_item($menu_item)]
        );
    } catch (RuntimeException $e) {
        if (mysqli_errno($link) === 1062) { // ER_DUP_ENTRY
            header("Location: /fmb/users/menu/list.php?action=existed&date=" . urlencode($menuDate));
            exit;
        }
        throw $e;
    }

    handleLessRiceUsers($link, $menu_item, $menuDate);
    header("Location: /fmb/users/menu/list.php?action=add&date=" . urlencode($menuDate));
    exit;
}

if ($action === 'edit_menu') {
    $menuDate = (string) ($_POST['menu_date'] ?? '');
    $menuId = (int) ($_POST['menu_id'] ?? 0);

    if ($menuDate === '' || $menuId <= 0 || !DateTime::createFromFormat('Y-m-d', $menuDate)) {
        header("Location: /fmb/users/menu/list.php?action=error");
        exit;
    }

    if ($menuType === 'miqaat') {
        db_query($link, "DELETE FROM user_menu WHERE `menu_date` = ?", "s", [$menuDate]);
    } else {
        $userMenus = db_query($link, "SELECT `id`, `menu_item` FROM user_menu WHERE `menu_date` = ?", "s", [$menuDate]);
        while ($menu_values = mysqli_fetch_assoc($userMenus)) {
            $menu_item_values = decode_menu_item($menu_values['menu_item']);

            if (!empty($menu_item_values['sabji']['item']) && !empty($menu_item['sabji']['item'])
                && $menu_item_values['sabji']['item'] !== $menu_item['sabji']['item']) {
                $menu_item_values['sabji'] = $menu_item['sabji'];
            }
            if (!empty($menu_item_values['tarkari']['item']) && !empty($menu_item['tarkari']['item'])
                && $menu_item_values['tarkari']['item'] !== $menu_item['tarkari']['item']) {
                $menu_item_values['tarkari'] = $menu_item['tarkari'];
            }
            if (!empty($menu_item_values['rice']['item']) && !empty($menu_item['rice']['item'])
                && $menu_item_values['rice']['item'] !== $menu_item['rice']['item']) {
                $menu_item_values['rice'] = $menu_item['rice'];
            }

            db_query(
                $link,
                "UPDATE user_menu SET `menu_item` = ? WHERE `id` = ?",
                "si",
                [encode_menu_item($menu_item_values), (int) $menu_values['id']]
            );
        }
        mysqli_free_result($userMenus);
    }

    db_query(
        $link,
        "UPDATE menu_list SET `menu_date` = ?, `menu_type` = ?, `menu_item` = ? WHERE `id` = ?",
        "sssi",
        [$menuDate, $menuType, encode_menu_item($menu_item), $menuId]
    );
    handleLessRiceUsers($link, $menu_item, $menuDate);
    header("Location: /fmb/users/menu/list.php?action=edit&date=" . urlencode($menuDate));
    exit;
}

if ($action === 'delete_menu') {
    $menuId = (int) ($_POST['menu_id'] ?? 0);
    $menuDate = (string) ($_POST['menu_date'] ?? '');

    if ($menuId <= 0 || $menuDate === '') {
        header("Location: /fmb/users/menu/list.php?action=error");
        exit;
    }

    db_query($link, "DELETE FROM menu_list WHERE `id` = ?", "i", [$menuId]);
    db_query($link, "DELETE FROM user_menu WHERE `menu_date` = ?", "s", [$menuDate]);
    header("Location: /fmb/users/menu/list.php?action=delete&date=" . urlencode($menuDate));
    exit;
}

// No recognised action.
header("Location: /fmb/users/menu/list.php");
exit;

function isRiceItem(string $item): bool
{
    $item = trim($item);
    $riceItems = ['biryani', 'chawal', 'rice', 'pulav', 'pulao', 'khichdi'];

    foreach ($riceItems as $rice) {
        if (stripos($item, $rice) !== false) {
            return true;
        }
    }

    return false;
}

function getDefaultRice(array $menu, array $thaliData): int
{
    $qty = (int) ($menu['rice']['qty'] ?? 0);
    $qty -= (int) ($thaliData['lessRice'] ?? 0);
    return max(0, $qty);
}

function getDefaultRoti(array $menu, array $thaliData): int
{
    $qty = match (strtolower(trim((string) $thaliData['thalisize']))) {
        'mini' => (int) ($menu['roti']['tqty'] ?? 0),
        'small' => (int) ($menu['roti']['sqty'] ?? 0),
        'medium' => (int) ($menu['roti']['mqty'] ?? 0),
        'large' => (int) ($menu['roti']['lqty'] ?? 0),
        default => (int) ($menu['roti']['sqty'] ?? 0),
    };

    // Apply extraRoti only for plain "Roti"
    if (strcasecmp(trim((string) ($menu['roti']['item'] ?? '')), 'Roti') === 0) {
        $qty += (int) ($thaliData['extraRoti'] ?? 0);
    }

    return max(0, $qty);
}

function handleLessRiceUsers(mysqli $link, array $menu_item, string $menu_date): void
{
    if (empty($menu_item['rice']['item'])) {
        return;
    }

    $riceItem = $menu_item['rice']['item'];

    if (!isRiceItem($riceItem)) {
        return;
    }

    $users = db_query($link, "SELECT `id`, `lessRice`, `extraRoti`, `thalisize` FROM thalilist WHERE lessRice > 0 OR extraRoti != 0");

    while ($row = mysqli_fetch_assoc($users)) {
        $temp = $menu_item;

        $temp['rice'] = [
            'item' => $menu_item['rice']['item'],
            'qty' => max(0, getDefaultRice($menu_item, $row)),
        ];

        if (!empty($menu_item['roti']['item']) && strcasecmp(trim($menu_item['roti']['item']), 'Roti') === 0) {
            $temp['roti'] = [
                'item' => $menu_item['roti']['item'],
                'qty' => getDefaultRoti($menu_item, $row),
            ];
        }

        // Single atomic upsert instead of SELECT-then-branch. Relies on the
        // UNIQUE KEY (menu_date, thali) added in schema-modernization.sql.
        db_query(
            $link,
            "INSERT INTO user_menu (thali, menu_date, menu_item) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE menu_item = VALUES(menu_item)",
            "iss",
            [(int) $row['id'], $menu_date, encode_menu_item($temp)]
        );
    }
    mysqli_free_result($users);
}

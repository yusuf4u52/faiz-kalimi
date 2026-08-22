<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');

$result = db_query($link, "SELECT * FROM menu_list ORDER BY `menu_date` DESC");
$menuList = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);

$today = date('Y-m-d');
$upcomingMenus = array_values(array_filter($menuList, fn($v) => $v['menu_date'] > $today));

$flashAction = $_GET['action'] ?? null;
$flashDate = $_GET['date'] ?? null;
$flashDateFormatted = $flashDate ? date('d M Y', strtotime($flashDate)) : '';

// Single query grouped by dish_type instead of 5 separate round trips.
$dishOptionsByType = ['1' => [], '2' => [], '3' => [], '4' => [], '5' => []];
$dishOptionsResult = db_query($link, "SELECT dish_name, dish_type FROM food_list ORDER BY `dish_name` ASC");
while ($row = mysqli_fetch_assoc($dishOptionsResult)) {
    if (isset($dishOptionsByType[$row['dish_type']])) {
        $dishOptionsByType[$row['dish_type']][] = $row;
    }
}
$sabjiOptions = $dishOptionsByType['1'];
$tarkariOptions = $dishOptionsByType['2'];
$riceOptions = $dishOptionsByType['3'];
$rotiOptions = $dishOptionsByType['4'];
$extraOptions = $dishOptionsByType['5'];
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-6">
                <h2 class="mb-3">Menu List</h2>
            </div>
            <div class="col-6 text-end">
                <button type="button" class="btn btn-light mb-3" data-bs-target="#addmenu"
                    data-bs-toggle="modal">Add
                    Menu</button>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if ($flashAction === 'add') { ?>
                    <div class="alert alert-success" role="alert">Menu/Miqaat of
                        <strong><?php echo e($flashDateFormatted); ?></strong> is added
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'edit') { ?>
                    <div class="alert alert-info" role="alert">Menu/Miqaat of
                        <strong><?php echo e($flashDateFormatted); ?></strong> is edited
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'delete') { ?>
                    <div class="alert alert-danger" role="alert">Menu/Miqaat of
                        <strong><?php echo e($flashDateFormatted); ?></strong> is deleted
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'existed') { ?>
                    <div class="alert alert-warning" role="alert">Menu/Miqaat of
                        <strong><?php echo e($flashDateFormatted); ?></strong> is already
                        existed.
                    </div>
                <?php } ?>
                <table class="table table-striped display" width="100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Menu Type</th>
                            <th>Menu Item</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menuList as $values) {
                            $menu_item = decode_menu_item($values['menu_item']); ?>
                            <tr>
                                <td data-sort="<?php echo strtotime($values['menu_date']); ?>"><?php echo e(date('d M Y', strtotime($values['menu_date']))); ?></td>
                                <td><?php echo e(ucfirst($values['menu_type'])); ?></td>
                                <td>
                                    <?php if ($values['menu_type'] === 'miqaat') { ?>
                                        <?php echo e(!empty($menu_item['miqaat']) ? $menu_item['miqaat'] : 'No Miqaat'); ?>
                                    <?php } elseif ($values['menu_type'] === 'thaali') { ?>
                                        <?php echo (!empty($menu_item['sabji']['item']) ? e($menu_item['sabji']['item']) . '  (' . (int) $menu_item['sabji']['qty'] . ')<br />' : ''); ?>
                                        <?php echo (!empty($menu_item['tarkari']['item']) ? e($menu_item['tarkari']['item']) . '  (' . (int) $menu_item['tarkari']['qty'] . ')<br />' : ''); ?>
                                        <?php echo (!empty($menu_item['rice']['item']) ? e($menu_item['rice']['item']) . '  (' . (int) $menu_item['rice']['qty'] . ')<br />' : ''); ?>
                                        <?php echo (!empty($menu_item['roti']['item']) ? e($menu_item['roti']['item']) . '  (Mini:' . (int) $menu_item['roti']['tqty'] . ', Small:' . (int) $menu_item['roti']['sqty'] . ', Medium:' . (int) $menu_item['roti']['mqty'] . ', Large:' . (int) $menu_item['roti']['lqty'] . ')<br/>' : ''); ?>
                                        <?php echo (!empty($menu_item['extra']['item']) ? e($menu_item['extra']['item']) . '  (' . (int) $menu_item['extra']['qty'] . ')<br />' : ''); ?>
                                    <?php } ?>
                                </td>
                                <td><?php if ($today < $values['menu_date']) { ?><button
                                            type="button" class="btn btn-light"
                                            data-bs-target="#editmenu-<?php echo (int) $values['id']; ?>"
                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button><?php } ?> <button
                                        type="button" class="btn btn-light"
                                        data-bs-target="#deletemenu-<?php echo (int) $values['id']; ?>"
                                        data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>

            <?php foreach ($upcomingMenus as $values) {
                $menu_item = decode_menu_item($values['menu_item']); ?>
                <div class="modal fade" id="editmenu-<?php echo (int) $values['id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="editmenu-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savelist.php" autocomplete="off">
                                <input type="hidden" name="action" value="edit_menu" />
                                <input type="hidden" name="menu_id" value="<?php echo (int) $values['id']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Edit Menu</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3 row">
                                        <label for="menu_date" class="col-4 control-label">Menu Date</label>
                                        <div class="col-8">
                                            <input type="date" class="form-control"
                                                min="<?php echo $today; ?>" name="menu_date"
                                                value="<?php echo e($values['menu_date']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="menu_type" class="col-4 control-label">Menu Type</label>
                                        <div class="col-8">
                                            <div class="form-check">
                                                <input class="form-check-input menu_type" type="radio"
                                                    name="menu_type" id="menu_type1" value="thaali" <?php echo ($values['menu_type'] === 'thaali' ? 'checked' : ''); ?>
                                                    required>
                                                <label class="form-check-label" for="menu_type1">
                                                    Thaali
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input menu_type" type="radio"
                                                    name="menu_type" id="menu_type2" value="miqaat" <?php echo ($values['menu_type'] === 'miqaat' ? 'checked' : ''); ?>
                                                    required>
                                                <label class="form-check-label" for="menu_type2">
                                                    Miqaat
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="miqaat <?php echo ($values['menu_type'] !== 'miqaat' ? 'd-none' : ''); ?>">
                                        <div class="mb-3 row">
                                            <label for="miqaat" class="col-4 control-label">Miqaat</label>
                                            <div class="col-8">
                                                <textarea class="form-control" name="menu_item[miqaat]"
                                                    id="miqaat"><?php echo e($menu_item['miqaat'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="thaali <?php echo ($values['menu_type'] !== 'thaali' ? 'd-none' : ''); ?>">
                                        <div class="mb-3 row">
                                            <label for="sabji" class="col-4 control-label">Sabji
                                                Item</label>
                                            <div class="col-6">
                                                <input list="sabji-item" type="text" class="form-control"
                                                    name="menu_item[sabji][item]" id="sabji"
                                                    value="<?php echo e($menu_item['sabji']['item'] ?? ''); ?>">
                                            </div>
                                            <div class="col-2">
                                                <input type="number" class="form-control"
                                                    name="menu_item[sabji][qty]" id="sabjiqty" min="1"
                                                    value="<?php echo (int) ($menu_item['sabji']['qty'] ?? 1); ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label for="tarkari" class="col-4 control-label">Tarkari/Dal
                                                Item</label>
                                            <div class="col-6">
                                                <input list="tarkari-item" type="text" class="form-control"
                                                    name="menu_item[tarkari][item]" id="tarkari"
                                                    value="<?php echo e($menu_item['tarkari']['item'] ?? ''); ?>">
                                            </div>
                                            <div class="col-2">
                                                <input type="number" class="form-control"
                                                    name="menu_item[tarkari][qty]" id="tarkariqty" min="1"
                                                    value="<?php echo (int) ($menu_item['tarkari']['qty'] ?? 1); ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label for="rice" class="col-4 control-label">Rice Item</label>
                                            <div class="col-6">
                                                <input list="rice-item" type="text" class="form-control"
                                                    name="menu_item[rice][item]" id="rice"
                                                    value="<?php echo e($menu_item['rice']['item'] ?? ''); ?>">
                                            </div>
                                            <div class="col-2">
                                                <input type="number" class="form-control"
                                                    name="menu_item[rice][qty]" id="riceqty" min="1"
                                                    value="<?php echo (int) ($menu_item['rice']['qty'] ?? 2); ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label for="roti" class="col-4 control-label">Roti/Bread
                                                Item</label>
                                            <div class="col-8">
                                                <input list="roti-item" class="form-control"
                                                    name="menu_item[roti][item]" id="roti"
                                                    value="<?php echo e($menu_item['roti']['item'] ?? ''); ?>">
                                                <div class="mb-3 row">
                                                    <div class="col-3">
                                                        <label for="rotitqty"
                                                            class="control-label">Mini</label>
                                                        <input type="number" class="form-control"
                                                            name="menu_item[roti][tqty]" id="rotitqty"
                                                            min="1"
                                                            value="<?php echo (int) ($menu_item['roti']['tqty'] ?? 1); ?>">
                                                    </div>
                                                    <div class="col-3">
                                                        <label for="rotisqty"
                                                            class="control-label">Small</label>
                                                        <input type="number" class="form-control"
                                                            name="menu_item[roti][sqty]" id="rotisqty"
                                                            min="1"
                                                            value="<?php echo (int) ($menu_item['roti']['sqty'] ?? 1); ?>">
                                                    </div>
                                                    <div class="col-3">
                                                        <label for="rotimqty"
                                                            class="control-label">Medium</label>
                                                        <input type="number" class="form-control"
                                                            name="menu_item[roti][mqty]" id="rotimqty"
                                                            min="1"
                                                            value="<?php echo (int) ($menu_item['roti']['mqty'] ?? 2); ?>">
                                                    </div>
                                                    <div class="col-3">
                                                        <label for="rotilqty"
                                                            class="control-label">Large</label>
                                                        <input type="number" class="form-control"
                                                            name="menu_item[roti][lqty]" id="rotilqty"
                                                            min="1"
                                                            value="<?php echo (int) ($menu_item['roti']['lqty'] ?? 2); ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label for="extra" class="col-4 control-label">Extra
                                                Item</label>
                                            <div class="col-6">
                                                <input list="extra-item" type="text" class="form-control"
                                                    name="menu_item[extra][item]" id="extra"
                                                    value="<?php echo e($menu_item['extra']['item'] ?? ''); ?>">
                                            </div>
                                            <div class="col-2">
                                                <input type="number" class="form-control"
                                                    name="menu_item[extra][qty]" id="extraqty" min="1"
                                                    value="<?php echo (int) ($menu_item['extra']['qty'] ?? 1); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-light">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php foreach ($menuList as $values) { ?>
                <div class="modal fade" id="deletemenu-<?php echo (int) $values['id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="deletemenu-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savelist.php" autocomplete="off">
                                <input type="hidden" name="action" value="delete_menu" />
                                <input type="hidden" name="menu_date"
                                    value="<?php echo e($values['menu_date']); ?>" />
                                <input type="hidden" name="menu_id" value="<?php echo (int) $values['id']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Delete Menu</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <p> Are you sure you want to delete <strong>Menu</strong> of
                                        <strong><?php echo e(date('d M Y', strtotime($values['menu_date']))); ?></strong>
                                        from
                                        database ?
                                    </p>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-light">Delete</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <div class="modal fade" id="addmenu">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addmenu" class="form-horizontal" method="post" action="savelist.php" autocomplete="off">
                            <input type="hidden" name="action" value="add_menu" />
                            <div class="modal-header">
                                <h4 class="modal-title">Add Menu</h4>
                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3 row">
                                    <label for="menu_date" class="col-4 control-label">Menu Date</label>
                                    <div class="col-8">
                                        <input type="date" class="form-control"
                                            min="<?php echo $today; ?>" name="menu_date"
                                            required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="menu_type" class="col-4 control-label">Menu Type</label>
                                    <div class="col-8">
                                        <div class="form-check">
                                            <input class="form-check-input menu_type" type="radio"
                                                name="menu_type" id="menu_type1" value="thaali"
                                                required>
                                            <label class="form-check-label"
                                                for="menu_type1">Thaali</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input menu_type" type="radio"
                                                name="menu_type" id="menu_type2" value="miqaat"
                                                required>
                                            <label class="form-check-label"
                                                for="menu_type2">Miqaat</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="miqaat d-none">
                                    <div class="mb-3 row">
                                        <label for="miqaat" class="col-4 control-label">Miqaat</label>
                                        <div class="col-8">
                                            <textarea class="form-control" name="menu_item[miqaat]"
                                                id="miqaat"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="thaali d-none">
                                    <div class="mb-3 row">
                                        <label for="sabji" class="col-4 control-label">Sabji
                                            Item</label>
                                        <div class="col-6">
                                            <input list="sabji-item" type="text" class="form-control"
                                                name="menu_item[sabji][item]" id="sabji">
                                        </div>
                                        <div class="col-2">
                                            <input type="number" class="form-control"
                                                name="menu_item[sabji][qty]" id="sabjiqty" value="1"
                                                min="1">
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="tarkari" class="col-4 control-label">Tarkari/Dal
                                            Item</label>
                                        <div class="col-6">
                                            <input list="tarkari-item" type="text" class="form-control"
                                                name="menu_item[tarkari][item]" id="tarkari">
                                        </div>
                                        <div class="col-2">
                                            <input type="number" class="form-control"
                                                name="menu_item[tarkari][qty]" id="tarkariqty" value="1"
                                                min="1">
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="rice" class="col-4 control-label">Rice Item</label>
                                        <div class="col-6">
                                            <input list="rice-item" type="text" class="form-control"
                                                name="menu_item[rice][item]" id="rice">
                                        </div>
                                        <div class="col-2">
                                            <input type="number" class="form-control"
                                                name="menu_item[rice][qty]" id="riceqty" value="2"
                                                min="1">
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="roti" class="col-4 control-label">Roti/Bread
                                            Item</label>
                                        <div class="col-8">
                                            <input list="roti-item" class="form-control"
                                                name="menu_item[roti][item]" id="roti">
                                            <div class="mb-3 row">
                                                <div class="col-3">
                                                    <label for="rotitqty"
                                                        class="control-label">Mini</label>
                                                    <input type="number" class="form-control"
                                                        name="menu_item[roti][tqty]" id="rotitqty"
                                                        value="1" min="1">
                                                </div>
                                                <div class="col-3">
                                                    <label for="rotisqty"
                                                        class="control-label">Small</label>
                                                    <input type="number" class="form-control"
                                                        name="menu_item[roti][sqty]" id="rotisqty"
                                                        value="1" min="1">
                                                </div>
                                                <div class="col-3">
                                                    <label for="rotimqty"
                                                        class="control-label">Medium</label>
                                                    <input type="number" class="form-control"
                                                        name="menu_item[roti][mqty]" id="rotimqty"
                                                        value="2" min="1">
                                                </div>
                                                <div class="col-3">
                                                    <label for="rotilqty"
                                                        class="control-label">Large</label>
                                                    <input type="number" class="form-control"
                                                        name="menu_item[roti][lqty]" id="rotilqty"
                                                        value="2" min="1">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="extra" class="col-4 control-label">Extra
                                            Item</label>
                                        <div class="col-6">
                                            <input list="extra-item" type="text" class="form-control"
                                                name="menu_item[extra][item]" id="extra">
                                        </div>
                                        <div class="col-2">
                                            <input type="number" class="form-control"
                                                name="menu_item[extra][qty]" id="extraqty" value="1"
                                                min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-light">Add Menu</button>
                            </div>
                        </form>
                    </div>
                </div>

                <datalist id="sabji-item">
                    <?php foreach ($sabjiOptions as $values) { ?>
                        <option value="<?php echo e($values['dish_name']); ?>">
                    <?php } ?>
                </datalist>

                <datalist id="tarkari-item">
                    <?php foreach ($tarkariOptions as $values) { ?>
                        <option value="<?php echo e($values['dish_name']); ?>">
                    <?php } ?>
                </datalist>

                <datalist id="rice-item">
                    <?php foreach ($riceOptions as $values) { ?>
                        <option value="<?php echo e($values['dish_name']); ?>">
                    <?php } ?>
                </datalist>

                <datalist id="roti-item">
                    <?php foreach ($rotiOptions as $values) { ?>
                        <option value="<?php echo e($values['dish_name']); ?>">
                    <?php } ?>
                </datalist>

                <datalist id="extra-item">
                    <?php foreach ($extraOptions as $values) { ?>
                        <option value="<?php echo e($values['dish_name']); ?>">
                    <?php } ?>
                </datalist>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

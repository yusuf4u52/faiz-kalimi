<?php
include('header.php');
include('navbar.php');
$result = mysqli_query($link, "SELECT * FROM menu_list order by `menu_date` DESC") or die(mysqli_error($link));
?>
<div class="content mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h2 clas="mb-3">Menu List</h2>
                            </div>
                            <div class="col-6 text-end">
                                <button type="button" class="btn btn-light mb-3" data-bs-target="#addmenu"
                                    data-bs-toggle="modal">Add
                                    Menu</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'add') { ?>
                                    <div class="alert alert-success" role="alert">Menu/Miqaat of
                                        <strong><?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> is added
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
                                    <div class="alert alert-success" role="alert">Menu/Miqaat of
                                        <strong><?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> is edited
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') { ?>
                                    <div class="alert alert-success" role="alert">Menu/Miqaat of
                                        <strong><?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> is deleted
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'existed') { ?>
                                    <div class="alert alert-warning" role="alert">Menu/Miqaat of
                                        <strong><?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> is already
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
                                        <?php while ($values = mysqli_fetch_assoc($result)) {
                                            $menu_item = unserialize($values['menu_item']); ?>
                                            <tr>
                                                <td><?php echo date('d M Y', strtotime($values['menu_date'])); ?></td>
                                                <td><?php echo ucfirst($values['menu_type']); ?></td>
                                                <td>
                                                    <?php if ($values['menu_type'] == 'miqaat') { ?>
                                                        <?php echo (!empty($menu_item['miqaat']) ? $menu_item['miqaat'] : 'No Miqaat'); ?>
                                                    <?php } elseif ($values['menu_type'] == 'thaali') { ?>
                                                        <?php echo (!empty($menu_item['sabji']['item']) ? $menu_item['sabji']['item'] . '  (' . $menu_item['sabji']['qty'] . ')<br />' : ''); ?>
                                                        <?php echo (!empty($menu_item['tarkari']['item']) ? $menu_item['tarkari']['item'] . '  (' . $menu_item['tarkari']['qty'] . ')<br />' : ''); ?>
                                                        <?php echo (!empty($menu_item['rice']['item']) ? $menu_item['rice']['item'] . '  (' . $menu_item['rice']['qty'] . ')<br />' : ''); ?>
                                                        <?php echo (!empty($menu_item['roti']['item']) ? $menu_item['roti']['item'] . '  (Mini:' . $menu_item['roti']['tqty'] . ', Small:' . $menu_item['roti']['sqty'] . ', Medium:' . $menu_item['roti']['mqty'] . ', Large:' . $menu_item['roti']['lqty'] . ')<br/>' : ''); ?>
                                                        <?php echo (!empty($menu_item['extra']['item']) ? $menu_item['extra']['item'] . '  (' . $menu_item['extra']['qty'] . ')<br />' : ''); ?>
                                                    <?php } ?>
                                                </td>
                                                <td><?php if (date('Y-m-d') < $values['menu_date']) { ?><button
                                                            type="button" class="btn btn-light"
                                                            data-bs-target="#editmenu-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button><?php } ?> <button
                                                        type="button" class="btn btn-light"
                                                        data-bs-target="#deletemenu-<?php echo $values['id']; ?>"
                                                        data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php }
                                        mysqli_free_result($result); ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php $result = mysqli_query($link, "SELECT * FROM menu_list WHERE `menu_date` > '" . date('Y-m-d') . "' order by `menu_date` DESC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) {
                                $menu_item = unserialize($values['menu_item']); ?>
                                <div class="modal fade" id="editmenu-<?php echo $values['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="editmenu-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savemenu.php" autocomplete="off">
                                                <input type="hidden" name="action" value="edit_menu" />
                                                <input type="hidden" name="menu_id" value="<?php echo $values['id']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Edit Menu</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3 row">
                                                        <label for="menu_date" class="col-4 control-label">Menu Date</label>
                                                        <div class="col-8">
                                                            <input type="date" class="form-control"
                                                                min="<?php echo date('Y-m-d'); ?>" name="menu_date"
                                                                value="<?php echo $values['menu_date']; ?>" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="menu_type" class="col-4 control-label">Menu Type</label>
                                                        <div class="col-8">
                                                            <div class="form-check">
                                                                <input class="form-check-input menu_type" type="radio"
                                                                    name="menu_type" id="menu_type1" value="thaali" <?php echo (!empty($values['menu_type']) && $values['menu_type'] == 'thaali' ? 'Checked' : ''); ?>
                                                                    required>
                                                                <label class="form-check-label" for="menu_type1">
                                                                    Thaali
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input menu_type" type="radio"
                                                                    name="menu_type" id="menu_type2" value="miqaat" <?php echo (!empty($values['menu_type']) && $values['menu_type'] == 'miqaat' ? 'Checked' : ''); ?>
                                                                    required>
                                                                <label class="form-check-label" for="menu_type2">
                                                                    Miqaat
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="miqaat <?php echo (!empty($values['menu_type']) && $values['menu_type'] != 'miqaat' ? 'd-none' : ''); ?>">
                                                        <div class="mb-3 row">
                                                            <label for="miqaat" class="col-4 control-label">Miqaat</label>
                                                            <div class="col-8">
                                                                <textarea class="form-control" name="menu_item[miqaat]"
                                                                    id="miqaat"><?php echo (!empty($menu_item['miqaat']) ? $menu_item['miqaat'] : ''); ?></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="thaali <?php echo (!empty($values['menu_type']) && $values['menu_type'] != 'thaali' ? 'd-none' : ''); ?>">
                                                        <div class="mb-3 row">
                                                            <label for="sabji" class="col-4 control-label">Sabji
                                                                Item</label>
                                                            <div class="col-6">
                                                                <input list="sabji-item" type="text" class="form-control"
                                                                    name="menu_item[sabji][item]" id="sabji"
                                                                    value="<?php echo (!empty($menu_item['sabji']['item']) ? $menu_item['sabji']['item'] : ''); ?>">
                                                            </div>
                                                            <div class="col-2">
                                                                <input type="number" class="form-control"
                                                                    name="menu_item[sabji][qty]" id="sabjiqty" min="1"
                                                                    value="<?php echo (!empty($menu_item['sabji']['qty']) ? $menu_item['sabji']['qty'] : '1'); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="mb-3 row">
                                                            <label for="tarkari" class="col-4 control-label">Tarkari/Dal
                                                                Item</label>
                                                            <div class="col-6">
                                                                <input list="tarkari-item" type="text" class="form-control"
                                                                    name="menu_item[tarkari][item]" id="tarkari"
                                                                    value="<?php echo (!empty($menu_item['tarkari']['item']) ? $menu_item['tarkari']['item'] : ''); ?>">
                                                            </div>
                                                            <div class="col-2">
                                                                <input type="number" class="form-control"
                                                                    name="menu_item[tarkari][qty]" id="tarkariqty" min="1"
                                                                    value="<?php echo (!empty($menu_item['tarkari']['qty']) ? $menu_item['tarkari']['qty'] : '1'); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="mb-3 row">
                                                            <label for="rice" class="col-4 control-label">Rice Item</label>
                                                            <div class="col-6">
                                                                <input list="rice-item" type="text" class="form-control"
                                                                    name="menu_item[rice][item]" id="rice"
                                                                    value="<?php echo (!empty($menu_item['rice']['item']) ? $menu_item['rice']['item'] : ''); ?>">
                                                            </div>
                                                            <div class="col-2">
                                                                <input type="number" class="form-control"
                                                                    name="menu_item[rice][qty]" id="riceqty" min="1"
                                                                    value="<?php echo (!empty($menu_item['rice']['qty']) ? $menu_item['rice']['qty'] : '2'); ?>">
                                                            </div>
                                                        </div>
                                                        <div class="mb-3 row">
                                                            <label for="roti" class="col-4 control-label">Roti/Bread
                                                                Item</label>
                                                            <div class="col-8">
                                                                <input list="roti-item" class="form-control"
                                                                    name="menu_item[roti][item]" id="roti"
                                                                    value="<?php echo (!empty($menu_item['roti']['item']) ? $menu_item['roti']['item'] : ''); ?>">
                                                                <div class="mb-3 row">
                                                                    <div class="col-3">
                                                                        <label for="rotitqty"
                                                                            class="control-label">Mini</label>
                                                                        <input type="number" class="form-control"
                                                                            name="menu_item[roti][tqty]" id="rotitqty"
                                                                            min="1"
                                                                            value="<?php echo (!empty($menu_item['roti']['tqty']) ? $menu_item['roti']['tqty'] : '1'); ?>">
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <label for="rotisqty"
                                                                            class="control-label">Small</label>
                                                                        <input type="number" class="form-control"
                                                                            name="menu_item[roti][sqty]" id="rotisqty"
                                                                            min="1"
                                                                            value="<?php echo (!empty($menu_item['roti']['sqty']) ? $menu_item['roti']['sqty'] : '1'); ?>">
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <label for="rotimqty"
                                                                            class="control-label">Medium</label>
                                                                        <input type="number" class="form-control"
                                                                            name="menu_item[roti][mqty]" id="rotimqty"
                                                                            min="1"
                                                                            value="<?php echo (!empty($menu_item['roti']['mqty']) ? $menu_item['roti']['mqty'] : '2'); ?>">
                                                                    </div>
                                                                    <div class="col-3">
                                                                        <label for="rotilqty"
                                                                            class="control-label">Large</label>
                                                                        <input type="number" class="form-control"
                                                                            name="menu_item[roti][lqty]" id="rotilqty"
                                                                            min="1"
                                                                            value="<?php echo (!empty($menu_item['roti']['lqty']) ? $menu_item['roti']['lqty'] : '2'); ?>">
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
                                                                    value="<?php echo (!empty($menu_item['extra']['item']) ? $menu_item['extra']['item'] : ''); ?>">
                                                            </div>
                                                            <div class="col-2">
                                                                <input type="number" class="form-control"
                                                                    name="menu_item[extra][qty]" id="extraqty" min="1"
                                                                    value="<?php echo (!empty($menu_item['extra']['qty']) ? $menu_item['extra']['qty'] : '1'); ?>">
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
                            <?php }
                            mysqli_free_result($result); ?>

                            <?php $result = mysqli_query($link, "SELECT * FROM menu_list order by `menu_date` DESC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="deletemenu-<?php echo $values['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="deletemenu-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savemenu.php" autocomplete="off">
                                                <input type="hidden" name="action" value="delete_menu" />
                                                <input type="hidden" name="menu_date"
                                                    value="<?php echo $values['menu_date']; ?>" />
                                                <input type="hidden" name="menu_id" value="<?php echo $values['id']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Delete Menu</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p> Are you sure you want to delete <strong>Menu</strong> of
                                                        <strong><?php echo date('d M Y', strtotime($values['menu_date'])); ?></strong>
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
                            <?php }
                            mysqli_free_result($result); ?>

                            <div class="modal fade" id="addmenu">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form id="addmenu" class="form-horizontal" method="post" action="savemenu.php" autocomplete="off">
                                            <input type="hidden" name="action" value="add_menu" />
                                            <div class="modal-header">
                                                <h4 class="modal-title fs-5">Add Menu</h4>
                                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3 row">
                                                    <label for="menu_date" class="col-4 control-label">Menu Date</label>
                                                    <div class="col-8">
                                                        <input type="date" class="form-control"
                                                            min="<?php echo date('Y-m-d'); ?>" name="menu_date"
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
                                                                    <label for="rotisqty"
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
                                <?php $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_type` = '1' order by `dish_name` ASC") or die(mysqli_error($link));
                                if (!empty($result)) { ?>
                                    <datalist id="sabji-item">
                                        <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                                            <option value="<?php echo $values['dish_name']; ?>">
                                            <?php } ?>
                                    </datalist>
                                <?php }
                                mysqli_free_result($result); ?>

                                <?php $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_type` = '2' order by `dish_name` ASC") or die(mysqli_error($link));
                                if (!empty($result)) { ?>
                                    <datalist id="tarkari-item">
                                        <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                                            <option value="<?php echo $values['dish_name']; ?>">
                                            <?php } ?>
                                    </datalist>
                                <?php }
                                mysqli_free_result($result); ?>

                                <?php $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_type` = '3' order by `dish_name` ASC") or die(mysqli_error($link));
                                if (!empty($result)) { ?>
                                    <datalist id="rice-item">
                                        <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                                            <option value="<?php echo $values['dish_name']; ?>">
                                            <?php } ?>
                                    </datalist>
                                <?php }
                                mysqli_free_result($result); ?>

                                <?php $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_type` = '4' order by `dish_name` ASC") or die(mysqli_error($link));
                                if (!empty($result)) { ?>
                                    <datalist id="roti-item">
                                        <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                                            <option value="<?php echo $values['dish_name']; ?>">
                                            <?php } ?>
                                    </datalist>
                                <?php }
                                mysqli_free_result($result); ?>

                                <?php $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_type` = '5' order by `dish_name` ASC") or die(mysqli_error($link));
                                if (!empty($result)) { ?>
                                    <datalist id="extra-item">
                                        <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                                            <option value="<?php echo $values['dish_name']; ?>">
                                            <?php } ?>
                                    </datalist>
                                <?php }
                                mysqli_free_result($result); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
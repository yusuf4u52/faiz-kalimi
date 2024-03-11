<?php
include('connection.php');
include('_authCheck.php');

$result = mysqli_query($link, "SELECT * FROM menu_list order by `menu_date` DESC") or die(mysqli_error($link));
?>

<html>

<head>
    <?php include('_head.php'); ?>
</head>

<body>
    <?php include('_nav.php'); ?>
    <div class="container">
        <button type="button" class="btn btn-primary" data-target="#addmenu" data-toggle="modal">Add Menu</button><br><br>
        <table class="table table-striped table-hover" id="my-table">
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
                            <?php if($values['menu_type'] == 'miqaat') { ?>
                                <?php echo (!empty($menu_item['miqaat']) ? $menu_item['miqaat'] : 'No Miqaat'); ?>
                            <?php } elseif($values['menu_type'] == 'thaali') { ?>
                                <?php echo (!empty($menu_item['sabji']['item']) ? $menu_item['sabji']['item'] . '  (' . $menu_item['sabji']['qty'] . ')' : 'Empty'); ?><br/>
                                <?php echo (!empty($menu_item['tarkari']['item']) ? $menu_item['tarkari']['item'] . '  (' . $menu_item['tarkari']['qty'] . ')' : 'Empty'); ?><br/>
                                <?php echo (!empty($menu_item['rice']['item']) ? $menu_item['rice']['item'] . '  (' . $menu_item['rice']['qty'] . ')' : 'Empty'); ?><br/>
                                <?php echo (!empty($menu_item['roti']['item']) ? $menu_item['roti']['item'] . '  (Mini:' . $menu_item['roti']['tqty'] . ', Small:' . $menu_item['roti']['sqty'] . ', Medium:' . $menu_item['roti']['mqty'] . ', Large:' . $menu_item['roti']['lqty'] . ')' : 'Empty'); ?>
                            <?php } ?>
                        </td>
                        <td><?php if (date('Y-m-d') < $values['menu_date']) { ?><button type="button" class="btn btn-primary" data-target="#editmenu-<?php echo $values['id']; ?>" data-toggle="modal">Edit</button><?php } ?> <button type="button" class="btn btn-primary" data-target="#deletemenu-<?php echo $values['id']; ?>" data-toggle="modal">Delete</button></td>
                    </tr>
                <?php }
                mysqli_free_result($result); ?>
            </tbody>
        </table>
    </div>

    <?php $result = mysqli_query($link, "SELECT * FROM menu_list WHERE `menu_date` > '".date('Y-m-d')."' order by `menu_date` DESC") or die(mysqli_error($link));
    while ($values = mysqli_fetch_assoc($result)) {
        $menu_item = unserialize($values['menu_item']); ?>
        <div class="modal" id="editmenu-<?php echo $values['id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="editmenu-<?php echo $values['id']; ?>" class="form-horizontal" method="post" action="savemenu.php">
                        <input type="hidden" name="action" value="edit_menu" />
                        <input type="hidden" name="menu_id" value="<?php echo $values['id']; ?>" />
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">Edit Menu</h4>
                        </div>
                        <div class="modal-body">
                            <div class="form-group row">
                                <label for="menu_date" class="col-xs-4 control-label">Menu Date</label>
                                <div class="col-xs-8">
                                    <input type="date" class="form-control" min="<?php echo date('Y-m-d'); ?>" name="menu_date" value="<?php echo $values['menu_date']; ?>" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="menu_type" class="col-xs-4 control-label">Menu Type</label>
                                <div class="col-xs-8">
                                    <div class="form-check">
                                        <input class="form-check-input menu_type" type="radio" name="menu_type" id="menu_type1" value="thaali" <?php echo ( !empty($values['menu_type']) && $values['menu_type'] == 'thaali' ? 'Checked' : ''); ?> required>
                                        <label class="form-check-label" for="menu_type1">
                                            Thaali
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input menu_type" type="radio" name="menu_type" id="menu_type2" value="miqaat" <?php echo ( !empty($values['menu_type']) && $values['menu_type'] == 'miqaat' ? 'Checked' : ''); ?> required>
                                        <label class="form-check-label" for="menu_type2">
                                            Miqaat
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="miqaat <?php echo ( !empty($values['menu_type']) && $values['menu_type'] != 'miqaat' ? 'hidden' : ''); ?>">
                                <div class="form-group row">
                                    <label for="miqaat" class="col-xs-4 control-label">Miqaat</label>
                                    <div class="col-xs-8">
                                        <textarea class="form-control" name="menu_item[miqaat]" id="miqaat"><?php echo (!empty($menu_item['miqaat']) ? $menu_item['miqaat'] : ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="thaali <?php echo ( !empty($values['menu_type']) && $values['menu_type'] != 'thaali' ? 'hidden' : ''); ?>">
                                <div class="form-group row">
                                    <label for="sabji" class="col-xs-4 control-label">Sabji Item</label>
                                    <div class="col-xs-6">
                                        <input list="sabji-item" type="text" class="form-control" name="menu_item[sabji][item]" id="sabji" value="<?php echo (!empty($menu_item['sabji']['item']) ? $menu_item['sabji']['item'] : ''); ?>">
                                    </div>
                                    <div class="col-xs-2">
                                        <input type="number" class="form-control" name="menu_item[sabji][qty]" id="sabjiqty" min="1" value="<?php echo (!empty($menu_item['sabji']['qty']) ? $menu_item['sabji']['qty'] : '1'); ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="tarkari" class="col-xs-4 control-label">Tarkari/Dal Item</label>
                                    <div class="col-xs-6">
                                        <input list="tarkari-item" type="text" class="form-control" name="menu_item[tarkari][item]" id="tarkari" value="<?php echo (!empty($menu_item['tarkari']['item']) ? $menu_item['tarkari']['item'] : ''); ?>">
                                    </div>
                                    <div class="col-xs-2">
                                        <input type="number" class="form-control" name="menu_item[tarkari][qty]" id="tarkariqty" min="1" value="<?php echo (!empty($menu_item['tarkari']['qty']) ? $menu_item['tarkari']['qty'] : '1'); ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="rice" class="col-xs-4 control-label">Rice Item</label>
                                    <div class="col-xs-6">
                                        <input list="rice-item" type="text" class="form-control" name="menu_item[rice][item]" id="rice" value="<?php echo (!empty($menu_item['rice']['item']) ? $menu_item['rice']['item'] : ''); ?>">
                                    </div>
                                    <div class="col-xs-2">
                                        <input type="number" class="form-control" name="menu_item[rice][qty]" id="riceqty" min="1" value="<?php echo (!empty($menu_item['rice']['qty']) ? $menu_item['rice']['qty'] : '2'); ?>">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="roti" class="col-xs-4 control-label">Roti/Bread Item</label>
                                    <div class="col-xs-8">
                                        <input list="roti-item" class="form-control" name="menu_item[roti][item]" id="roti" value="<?php echo (!empty($menu_item['roti']['item']) ?  $menu_item['roti']['item'] : ''); ?>">
                                        <div class="form-group row">
                                            <div class="col-xs-3">
                                                <label for="rotitqty" class="control-label">Mini</label>
                                                <input type="number" class="form-control" name="menu_item[roti][tqty]" id="rotitqty" min="1" value="<?php echo (!empty($menu_item['roti']['tqty']) ?  $menu_item['roti']['tqty'] : '1'); ?>">
                                            </div>
                                            <div class="col-xs-3">
                                                <label for="rotisqty" class="control-label">Small</label>
                                                <input type="number" class="form-control" name="menu_item[roti][sqty]" id="rotisqty" min="1" value="<?php echo (!empty($menu_item['roti']['sqty']) ?  $menu_item['roti']['sqty'] : '1'); ?>">
                                            </div>
                                            <div class="col-xs-3">
                                                <label for="rotimqty" class="control-label">Medium</label>
                                                <input type="number" class="form-control" name="menu_item[roti][mqty]" id="rotimqty" min="1" value="<?php echo (!empty($menu_item['roti']['mqty']) ? $menu_item['roti']['mqty'] : '2'); ?>">
                                            </div>
                                            <div class="col-xs-3">
                                                <label for="rotilqty" class="control-label">Large</label>
                                                <input type="number" class="form-control" name="menu_item[roti][lqty]" id="rotilqty" min="1" value="<?php echo (!empty($menu_item['roti']['lqty']) ? $menu_item['roti']['lqty'] : '3'); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php }
    mysqli_free_result($result); ?>

    <?php $result = mysqli_query($link, "SELECT * FROM menu_list order by `menu_date` DESC") or die(mysqli_error($link));
    while ($values = mysqli_fetch_assoc($result)) { ?>
        <div class="modal" id="deletemenu-<?php echo $values['id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="deletefood-<?php echo $values['id']; ?>" class="form-horizontal" method="post" action="savemenu.php">
                        <input type="hidden" name="action" value="delete_menu" />
                        <input type="hidden" name="menu_id" value="<?php echo $values['id']; ?>" />
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">Delete Menu</h4>
                        </div>
                        <div class="modal-body">
                            <p> Are you sure you want to delete <strong>Menu</strong> of <strong><?php echo date('d M Y', strtotime($values['menu_date'])); ?></strong> from database ? </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php }
    mysqli_free_result($result); ?>

    <div class="modal" id="addmenu">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addmenu" class="form-horizontal" method="post" action="savemenu.php">
                    <input type="hidden" name="action" value="add_menu" />
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">Add Menu</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group row">
                            <label for="menu_date" class="col-xs-4 control-label">Menu Date</label>
                            <div class="col-xs-8">
                                <input type="date" class="form-control" min="<?php echo date('Y-m-d'); ?>" name="menu_date" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="menu_type" class="col-xs-4 control-label">Menu Type</label>
                            <div class="col-xs-8">
                                <div class="form-check">
                                    <input class="form-check-input menu_type" type="radio" name="menu_type" id="menu_type1" value="thaali" required>
                                    <label class="form-check-label" for="menu_type1">Thaali</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input menu_type" type="radio" name="menu_type" id="menu_type2" value="miqaat" required>
                                    <label class="form-check-label" for="menu_type2">Miqaat</label>
                                </div>
                            </div>
                        </div>
                        <div class="miqaat hidden">
                            <div class="form-group row">
                                <label for="miqaat" class="col-xs-4 control-label">Miqaat</label>
                                <div class="col-xs-8">
                                    <textarea class="form-control" name="menu_item[miqaat]" id="miqaat"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="thaali hidden">
                            <div class="form-group row">
                                <label for="sabji" class="col-xs-4 control-label">Sabji Item</label>
                                <div class="col-xs-6">
                                    <input list="sabji-item" type="text" class="form-control" name="menu_item[sabji][item]" id="sabji">
                                </div>
                                <div class="col-xs-2">
                                    <input type="number" class="form-control" name="menu_item[sabji][qty]" id="sabjiqty" value="1" min="1">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="tarkari" class="col-xs-4 control-label">Tarkari/Dal Item</label>
                                <div class="col-xs-6">
                                    <input list="tarkari-item" type="text" class="form-control" name="menu_item[tarkari][item]" id="tarkari">
                                </div>
                                <div class="col-xs-2">
                                    <input type="number" class="form-control" name="menu_item[tarkari][qty]" id="tarkariqty" value="1" min="1">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="rice" class="col-xs-4 control-label">Rice Item</label>
                                <div class="col-xs-6">
                                    <input list="rice-item" type="text" class="form-control" name="menu_item[rice][item]" id="rice">
                                </div>
                                <div class="col-xs-2">
                                    <input type="number" class="form-control" name="menu_item[rice][qty]" id="riceqty" value="2" min="1">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="roti" class="col-xs-4 control-label">Roti/Bread Item</label>
                                <div class="col-xs-8">
                                    <input list="roti-item" class="form-control" name="menu_item[roti][item]" id="roti">
                                    <div class="form-group row">
                                    <div class="col-xs-3">
                                            <label for="rotisqty" class="control-label">Mini</label>
                                            <input type="number" class="form-control" name="menu_item[roti][tqty]" id="rotitqty" value="1" min="1">
                                        </div>
                                        <div class="col-xs-3">
                                            <label for="rotisqty" class="control-label">Small</label>
                                            <input type="number" class="form-control" name="menu_item[roti][sqty]" id="rotisqty" value="1" min="1">
                                        </div>
                                        <div class="col-xs-3">
                                            <label for="rotimqty" class="control-label">Medium</label>
                                            <input type="number" class="form-control" name="menu_item[roti][mqty]" id="rotimqty" value="2" min="1">
                                        </div>
                                        <div class="col-xs-3">
                                            <label for="rotilqty" class="control-label">Large</label>
                                            <input type="number" class="form-control" name="menu_item[roti][lqty]" id="rotilqty" value="3" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
        <?php $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_type` = '1' order by `dish_name` ASC") or die(mysqli_error($link));
        if (!empty($result)) {  ?>
            <datalist id="sabji-item">
                <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                    <option value="<?php echo $values['dish_name']; ?>">
                    <?php } ?>
            </datalist>
        <?php }
        mysqli_free_result($result); ?>

        <?php $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_type` = '2' order by `dish_name` ASC") or die(mysqli_error($link));
        if (!empty($result)) {  ?>
            <datalist id="tarkari-item">
                <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                    <option value="<?php echo $values['dish_name']; ?>">
                    <?php } ?>
            </datalist>
        <?php }
        mysqli_free_result($result); ?>

        <?php $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_type` = '3' order by `dish_name` ASC") or die(mysqli_error($link));
        if (!empty($result)) {  ?>
            <datalist id="rice-item">
                <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                    <option value="<?php echo $values['dish_name']; ?>">
                    <?php } ?>
            </datalist>
        <?php }
        mysqli_free_result($result); ?>

        <?php $result = mysqli_query($link, "SELECT * FROM food_list WHERE `dish_type` = '4' order by `dish_name` ASC") or die(mysqli_error($link));
        if (!empty($result)) {  ?>
            <datalist id="roti-item">
                <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                    <option value="<?php echo $values['dish_name']; ?>">
                    <?php } ?>
            </datalist>
        <?php }
        mysqli_free_result($result); ?>
    </div>

    <?php include('_bottomJS.php'); ?>
    <script type="text/javascript">
        $('#my-table').dynatable();
    </script>
</body>

</html>

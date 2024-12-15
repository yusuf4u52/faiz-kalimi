<?php
include('header.php');
include('navbar.php');
$result = mysqli_query($link, "SELECT * FROM food_list order by `dish_name` ASC") or die(mysqli_error($link));
?>
<div class="content mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h2 class="mb-3">Food List</h2>
                            </div>
                            <div class="col-6 text-end">
                                <button type="button" class="btn btn-light mb-3" data-bs-target="#addfood"
                                    data-bs-toggle="modal">Add Food
                                    Item</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'add') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong><?php echo $_GET['dish']; ?></strong> added
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong><?php echo $_GET['dish']; ?></strong> edited
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong><?php echo $_GET['dish']; ?></strong> deleted
                                        successfully.
                                    </div>
                                <?php } ?>
                                <div class="table-responsive">
                                    <table class="table table-striped display" width="100%">
                                        <thead>
                                            <tr>
                                                <th width="50%">Dish Name</th>
                                                <th>Dish Type</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($values = mysqli_fetch_assoc($result)) {
                                                if ($values['dish_type'] == '1') {
                                                    $dish_type = 'Sabji Item';
                                                } elseif ($values['dish_type'] == '2') {
                                                    $dish_type = 'Tarkari/Dal Item';
                                                } elseif ($values['dish_type'] == '3') {
                                                    $dish_type = 'Rice Item';
                                                } elseif ($values['dish_type'] == '4') {
                                                    $dish_type = 'Roti/Bread Item';
                                                } elseif ($values['dish_type'] == '5') {
                                                    $dish_type = 'Extra Item';
                                                } else {
                                                    $dish_type = '';
                                                } ?>
                                                <tr>
                                                    <td><?php echo $values['dish_name']; ?></td>
                                                    <td><?php echo $dish_type; ?></td>
                                                    <td><button type="button" class="btn btn-light"
                                                            data-bs-target="#editfood-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                                            class="btn btn-light"
                                                            data-bs-target="#deletefood-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                            <?php }
                                            mysqli_free_result($result); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <?php $result = mysqli_query($link, "SELECT * FROM food_list order by `dish_name` ASC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="editfood-<?php echo $values['id']; ?>" tabindex="-1"
                                    aria-labelledby="editfood-<?php echo $values['id']; ?>-Label" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="editfood-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savefood.php" autocomplete="off">
                                                <input type="hidden" name="action" value="edit_food" />
                                                <input type="hidden" name="food_id" value="<?php echo $values['id']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Edit Food Item</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3 row">
                                                        <label for="dish_name" class="col-4 control-label">Dish Name</label>
                                                        <div class="col-8">
                                                            <input type="text" class="form-control" name="dish_name"
                                                                value="<?php echo $values['dish_name']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="dish_type" class="col-4 control-label">Dish Type</label>
                                                        <div class="col-8">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="dish_type" id="dish_type1" value="1" <?php echo (($values['dish_type'] == '1') ? 'checked' : ''); ?>>
                                                                <label class="form-check-label" for="dish_type1">Sabji
                                                                    Item</label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="dish_type" id="dish_type2" value="2" <?php echo (($values['dish_type'] == '2') ? 'checked' : ''); ?>>
                                                                <label class="form-check-label" for="dish_type2">Tarkari/Dal
                                                                    Item</label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="dish_type" id="dish_type3" value="3" <?php echo (($values['dish_type'] == '3') ? 'checked' : ''); ?>>
                                                                <label class="form-check-label" for="dish_type3">Rice
                                                                    Item</label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="dish_type" id="dish_type4" value="4" <?php echo (($values['dish_type'] == '4') ? 'checked' : ''); ?>>
                                                                <label class="form-check-label" for="dish_type4">Roti/Bread
                                                                    Item</label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="dish_type" id="dish_type5" value="5" <?php echo (($values['dish_type'] == '5') ? 'checked' : ''); ?>>
                                                                <label class="form-check-label" for="dish_type5">Extra
                                                                    Item</label>
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

                            <?php $result = mysqli_query($link, "SELECT * FROM food_list order by `dish_name` ASC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="deletefood-<?php echo $values['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="deletefood-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savefood.php" autocomplete="off">
                                                <input type="hidden" name="action" value="delete_food" />
                                                <input type="hidden" name="dish_name"
                                                    value="<?php echo $values['dish_name']; ?>" />
                                                <input type="hidden" name="food_id" value="<?php echo $values['id']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Delete Food Item</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p> Are you sure you want to delete
                                                        <strong><?php echo $values['dish_name']; ?></strong>
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

                            <div class="modal fade" id="addfood">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form id="addfood" class="form-horizontal" method="post" action="savefood.php" autocomplete="off">
                                            <input type="hidden" name="action" value="add_food" />
                                            <div class="modal-header">
                                                <h4 class="modal-title fs-5">Add Food Item</h4>
                                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3 row">
                                                    <label for="dish_name" class="col-4 control-label">Dish Name</label>
                                                    <div class="col-8">
                                                        <input type="text" class="form-control" name="dish_name"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="dish_pack" class="col-4 control-label">Dish Type</label>
                                                    <div class="col-8">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                name="dish_type" id="dish_type1" value="1">
                                                            <label class="form-check-label" for="dish_type1">Sabji
                                                                Item</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                name="dish_type" id="dish_type2" value="2">
                                                            <label class="form-check-label" for="dish_type2">Tarkari/Dal
                                                                Item</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                name="dish_type" id="dish_type3" value="3">
                                                            <label class="form-check-label" for="dish_type3">Rice
                                                                Item</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                name="dish_type" id="dish_type4" value="4">
                                                            <label class="form-check-label" for="dish_type4">Roti/Bread
                                                                Item</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio"
                                                                name="dish_type" id="dish_type5" value="5">
                                                            <label class="form-check-label" for="dish_type5">Extra
                                                                Item</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-light">Add Food Item</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
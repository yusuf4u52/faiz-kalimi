<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');

$result = db_query($link, "SELECT * FROM food_list ORDER BY `dish_name` ASC");
$foodItems = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);

$flashAction = $_GET['action'] ?? null;
$flashDish = $_GET['dish'] ?? '';
?>

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
                <?php if ($flashAction === 'add') { ?>
                    <div class="alert alert-success" role="alert">
                        <strong><?php echo e($flashDish); ?></strong> added
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'edit') { ?>
                    <div class="alert alert-info" role="alert">
                        <strong><?php echo e($flashDish); ?></strong> edited
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'delete') { ?>
                    <div class="alert alert-danger" role="alert">
                        <strong><?php echo e($flashDish); ?></strong> deleted
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'duplicate') { ?>
                    <div class="alert alert-warning" role="alert">
                        <strong><?php echo e($flashDish); ?></strong> already exists with that dish type.
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
                            <?php foreach ($foodItems as $values) { ?>
                                <tr>
                                    <td><?php echo e($values['dish_name']); ?></td>
                                    <td><?php echo e(dish_type_label($values['dish_type'])); ?></td>
                                    <td><button type="button" class="btn btn-light"
                                            data-bs-target="#editfood-<?php echo (int) $values['id']; ?>"
                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button
                                            type="button"
                                            class="btn btn-light"
                                            data-bs-target="#deletefood-<?php echo (int) $values['id']; ?>"
                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php foreach ($foodItems as $values) { ?>
                <div class="modal fade" id="editfood-<?php echo (int) $values['id']; ?>" tabindex="-1"
                    aria-labelledby="editfood-<?php echo (int) $values['id']; ?>-Label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="editfood-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savefood.php" autocomplete="off">
                                <input type="hidden" name="action" value="edit_food" />
                                <input type="hidden" name="food_id" value="<?php echo (int) $values['id']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Edit Food Item</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3 row">
                                        <label for="dish_name" class="col-4 control-label">Dish Name</label>
                                        <div class="col-8">
                                            <input type="text" class="form-control" name="dish_name"
                                                value="<?php echo e($values['dish_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="dish_type" class="col-4 control-label">Dish Type</label>
                                        <div class="col-8">
                                            <?php foreach (VALID_DISH_TYPES as $type) { ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio"
                                                        name="dish_type" id="dish_type<?php echo $type; ?>" value="<?php echo $type; ?>" <?php echo ($values['dish_type'] === $type ? 'checked' : ''); ?>>
                                                    <label class="form-check-label" for="dish_type<?php echo $type; ?>"><?php echo e(dish_type_label($type)); ?></label>
                                                </div>
                                            <?php } ?>
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

            <?php foreach ($foodItems as $values) { ?>
                <div class="modal fade" id="deletefood-<?php echo (int) $values['id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="deletefood-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savefood.php" autocomplete="off">
                                <input type="hidden" name="action" value="delete_food" />
                                <input type="hidden" name="dish_name"
                                    value="<?php echo e($values['dish_name']); ?>" />
                                <input type="hidden" name="food_id" value="<?php echo (int) $values['id']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Delete Food Item</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <p> Are you sure you want to delete
                                        <strong><?php echo e($values['dish_name']); ?></strong>
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

            <div class="modal fade" id="addfood">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addfood" class="form-horizontal" method="post" action="savefood.php" autocomplete="off">
                            <input type="hidden" name="action" value="add_food" />
                            <div class="modal-header">
                                <h4 class="modal-title">Add Food Item</h4>
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
                                        <?php foreach (VALID_DISH_TYPES as $type) { ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio"
                                                    name="dish_type" id="dish_type<?php echo $type; ?>" value="<?php echo $type; ?>">
                                                <label class="form-check-label" for="dish_type<?php echo $type; ?>"><?php echo e(dish_type_label($type)); ?></label>
                                            </div>
                                        <?php } ?>
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

<?php include('../footer.php'); ?>

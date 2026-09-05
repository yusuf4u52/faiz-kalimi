<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');

$result = db_query($link, "SELECT * FROM fmb_roti_maker ORDER BY `full_name` ASC");
$makers = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);

$flashAction = $_GET['action'] ?? null;
$flashName = $_GET['full_name'] ?? '';
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-6">
                <h2 class="mb-3">FMB Roti Makers</h2>
            </div>
            <div class="col-6 text-end">
                <button type="button" class="btn btn-light mb-3" data-bs-target="#addrmaker"
                    data-bs-toggle="modal">Add Roti Maker</button>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if ($flashAction === 'add') { ?>
                    <div class="alert alert-success" role="alert">
                        <strong><?php echo e($flashName); ?></strong> is added
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'edit') { ?>
                    <div class="alert alert-info" role="alert">
                        <strong><?php echo e($flashName); ?></strong> is edited
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'delete') { ?>
                    <div class="alert alert-danger" role="alert">
                        <strong><?php echo e($flashName); ?></strong> is deleted
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'duplicate') { ?>
                    <div class="alert alert-warning" role="alert">
                        A roti maker with that code already exists.
                    </div>
                <?php } ?>
                <div class="table-responsive">
                    <table class="table table-striped display" width="100%">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Full Name</th>
                                <th>ITS No</th>
                                <th>Mobile No</th>
                                <th>Bank Details</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($makers as $values) {
                                $paragraphs = explode("\n", $values['bank_details'] ?? ''); ?>
                                <tr>
                                    <td><?php echo e($values['code']); ?></td>
                                    <td><?php echo e($values['full_name']); ?></td>
                                    <td><?php echo e($values['its_no']); ?></td>
                                    <td><?php echo e($values['mobile_no']); ?></td>
                                    <td><?php foreach ($paragraphs as $para) {
                                        echo '<p class="mb-1">' . e($para) . '</p>';
                                    } ?></td>
                                    <td><button type="button" class="btn btn-light"
                                            data-bs-target="#editrmaker-<?php echo (int) $values['id']; ?>"
                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                            class="btn btn-light"
                                            data-bs-target="#deletermaker-<?php echo (int) $values['id']; ?>"
                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php foreach ($makers as $values) { ?>
                <div class="modal fade" id="editrmaker-<?php echo (int) $values['id']; ?>" tabindex="-1"
                    aria-labelledby="editrmaker-<?php echo (int) $values['id']; ?>-Label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="editrmaker-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savemaker.php" autocomplete="off">
                                <input type="hidden" name="action" value="edit_rmaker" />
                                <input type="hidden" name="rmaker_id" value="<?php echo (int) $values['id']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Update Roti Maker</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3 row">
                                        <label for="its_no" class="col-4 control-label">ITS No</label>
                                        <div class="col-8">
                                            <input type="number" class="form-control" name="its_no"
                                                value="<?php echo e($values['its_no']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="full_name" class="col-4 control-label">Full Name</label>
                                        <div class="col-8">
                                            <input type="text" class="form-control" name="full_name"
                                                value="<?php echo e($values['full_name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="code" class="col-4 control-label">Code</label>
                                        <div class="col-8">
                                            <input type="text" class="form-control" name="code"
                                                value="<?php echo e($values['code']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="mobile_no" class="col-4 control-label">Mobile No</label>
                                        <div class="col-8">
                                            <input type="tel" inputmode="numeric" class="form-control" name="mobile_no"
                                                pattern="[0-9]{10}" maxlength="10"
                                                value="<?php echo e($values['mobile_no']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="bank_details" class="col-4 control-label">Bank Details</label>
                                        <div class="col-8">
                                            <textarea class="form-control" name="bank_details" rows="3"
                                                required><?php echo e($values['bank_details']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-light">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php foreach ($makers as $values) { ?>
                <div class="modal fade" id="deletermaker-<?php echo (int) $values['id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="deletermaker-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savemaker.php" autocomplete="off">
                                <input type="hidden" name="action" value="delete_rmaker" />
                                <input type="hidden" name="rmaker_id" value="<?php echo (int) $values['id']; ?>" />
                                <input type="hidden" name="full_name" value="<?php echo e($values['full_name']); ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Delete Roti Maker</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <p> Are you sure you want to delete <strong><?php echo e($values['full_name']); ?></strong>
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

            <div class="modal fade" id="addrmaker">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addrmaker" class="form-horizontal" method="post" action="savemaker.php" autocomplete="off">
                            <input type="hidden" name="action" value="add_rmaker" />
                            <div class="modal-header">
                                <h4 class="modal-title">Add Roti Maker</h4>
                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3 row">
                                <label for="its_no" class="col-4 control-label">ITS No</label>
                                    <div class="col-8">
                                        <input type="number" class="form-control" name="its_no"
                                                required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="full_name" class="col-4 control-label">Full Name</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="full_name" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="code" class="col-4 control-label">Code</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="code" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="mobile_no" class="col-4 control-label">Mobile No</label>
                                    <div class="col-8">
                                        <input type="tel" inputmode="numeric" class="form-control" name="mobile_no"
                                            pattern="[0-9]{10}" maxlength="10" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="bank_details" class="col-4 control-label">Bank Details</label>
                                    <div class="col-8">
                                        <textarea class="form-control" name="bank_details" rows="3"
                                            required></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-light">Add</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

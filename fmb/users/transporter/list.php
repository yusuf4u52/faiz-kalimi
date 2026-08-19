<?php
include('../header.php');
include('../navbar.php');
require_once('../helpers.php');

$result = db_query($link, "SELECT * FROM transporters ORDER BY `Name` ASC");
$transporters = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);

$flashAction = $_GET['action'] ?? null;
$flashName = $_GET['Name'] ?? '';
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-6">
                <h2 class="mb-3">FMB Transporters</h2>
            </div>
            <div class="col-6 text-end">
                <button type="button" class="btn btn-light mb-3" data-bs-target="#addtransporter"
                    data-bs-toggle="modal">Add Transporter</button>
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
                <?php if ($flashAction === 'error') { ?>
                    <div class="alert alert-danger" role="alert">
                        Please check the details you entered and try again (mobile must be 10 digits, email must be a Gmail address).
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'duplicate') { ?>
                    <div class="alert alert-warning" role="alert">
                        A transporter with that email already exists.
                    </div>
                <?php } ?>
                <div class="table-responsive">
                    <table class="table table-striped display" width="100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Mobile No</th>
                                <th>Email Address</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transporters as $values) { ?>
                                <tr>
                                    <td><?php echo e($values['Name']); ?></td>
                                    <td><?php echo e($values['Mobile']); ?></td>
                                    <td><?php echo e($values['Email']); ?></td>
                                    <td><button type="button" class="btn btn-light"
                                        data-bs-target="#edittransporter-<?php echo (int) $values['id']; ?>"
                                        data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                        class="btn btn-light"
                                        data-bs-target="#deletetransporter-<?php echo (int) $values['id']; ?>"
                                        data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php foreach ($transporters as $values) { ?>
                <div class="modal fade" id="edittransporter-<?php echo (int) $values['id']; ?>" tabindex="-1"
                    aria-labelledby="edittransporter-<?php echo (int) $values['id']; ?>-Label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="edittransporter-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savelist.php" autocomplete="off">
                                <input type="hidden" name="action" value="edit_transporter" />
                                <input type="hidden" name="transporter_id" value="<?php echo (int) $values['id']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Update Transporter</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3 row">
                                        <label for="Name" class="col-4 control-label">Full Name</label>
                                        <div class="col-8">
                                            <input type="text" class="form-control" name="Name"
                                                value="<?php echo e($values['Name']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="Mobile" class="col-4 control-label">Mobile No</label>
                                        <div class="col-8">
                                            <input type="tel" inputmode="numeric" class="form-control" name="Mobile"
                                                pattern="[0-9]{10}" maxlength="10"
                                                value="<?php echo e($values['Mobile']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="Email" class="col-4 control-label">Email Address</label>
                                        <div class="col-8">
                                            <input type="email" class="form-control" name="Email"
                                            value="<?php echo e($values['Email']); ?>" pattern="[a-z0-9._%+\-]+@gmail.com$" required>
                                                <p class="help-block mb-0 text-danger text-end"><small>(Only Gmail)</small></p>
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

            <?php foreach ($transporters as $values) { ?>
                <div class="modal fade" id="deletetransporter-<?php echo (int) $values['id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="deletetransporter-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savelist.php" autocomplete="off">
                                <input type="hidden" name="action" value="delete_transporter" />
                                <input type="hidden" name="transporter_id" value="<?php echo (int) $values['id']; ?>" />
                                <input type="hidden" name="Name" value="<?php echo e($values['Name']); ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Delete Transporter</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <p> Are you sure you want to delete <strong><?php echo e($values['Name']); ?></strong>
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

            <div class="modal fade" id="addtransporter">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addtransporter" class="form-horizontal" method="post" action="savelist.php" autocomplete="off">
                            <input type="hidden" name="action" value="add_transporter" />
                            <div class="modal-header">
                                <h4 class="modal-title">Add Transporter</h4>
                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3 row">
                                    <label for="Name" class="col-4 control-label">Full Name</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="Name" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="Mobile" class="col-4 control-label">Mobile No</label>
                                    <div class="col-8">
                                        <input type="tel" inputmode="numeric" class="form-control" name="Mobile"
                                            pattern="[0-9]{10}" maxlength="10" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                        <label for="Email" class="col-4 control-label">Email Address</label>
                                        <div class="col-8">
                                            <input type="email" class="form-control" name="Email"
                                            pattern="[a-z0-9._%+\-]+@gmail.com$" required>
                                            <p class="help-block mb-0 text-danger text-end"><small>(Only Gmail)</small></p>
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

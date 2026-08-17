<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');
include('../getHijriDate.php');

$recievedDate = $_GET['recieved_date'] ?? null;
$isValidDate = $recievedDate && DateTime::createFromFormat('Y-m-d', $recievedDate);

$baseQuery = "SELECT r.*, m.full_name, m.code, u.username
              FROM fmb_roti_recieved r
              LEFT JOIN fmb_roti_maker m ON m.id = r.maker_id
              LEFT JOIN users u ON u.email = r.recieved_by";

if ($isValidDate) {
    $result = db_query($link, $baseQuery . " WHERE r.recieved_date = ? ORDER BY r.maker_id DESC", "s", [$recievedDate]);
} else {
    $result = db_query($link, $baseQuery . " ORDER BY r.recieved_date DESC");
}
$receipts = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);

$fmb_roti_maker_result = db_query($link, "SELECT `id`, `code` FROM fmb_roti_maker ORDER BY `full_name` ASC");
$allMakers = mysqli_fetch_all($fmb_roti_maker_result, MYSQLI_ASSOC);
mysqli_free_result($fmb_roti_maker_result);

$flashAction = $_GET['action'] ?? null;
$flashMakerId = (int) ($_GET['maker'] ?? 0);
$flashRecievedDate = $_GET['recieved_date'] ?? '';
$flashMaker = null;
if ($flashMakerId > 0 && in_array($flashAction, ['add', 'edit', 'delete'], true)) {
    foreach ($allMakers as $m) {
        if ((int) $m['id'] === $flashMakerId) {
            $flashMaker = $m;
            break;
        }
    }
}
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-6">
                <h2 class="mb-3">FMB Roti Recieved <?php echo ($isValidDate ? 'on <strong>' . e(date('d F Y', strtotime($recievedDate))) . '</strong>' : ''); ?></h2>
            </div>
            <div class="col-6 text-end">
                <button type="button" class="btn btn-light mb-3" data-bs-target="#addrrecieved"
                    data-bs-toggle="modal">Add Roti Recieved</button>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if (is_roti_privileged_user()) { ?>
                    <form id="recieveimport" class="form-horizontal my-3" method="POST"
                        action="recieveimport.php" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3 row">
                            <label for="recieve_import" class="col-4 control-label">Import Recieved Sheet</label>
                            <div class="col-4">
                                <input type="file" class="form-control" name="recieve_import" accept=".xlsx" id="recieve_import">
                            </div>
                            <div class="col-4">
                                <button class="btn btn-light" type="submit" name="import">Import</button>
                            </div>
                        </div>
                    </form>
                <?php } ?>
                <form id="rotipayment" class="form-horizontal my-3" method="GET"
                    action="<?php echo e($_SERVER['PHP_SELF']); ?>" autocomplete="off">
                    <div class="mb-3 row">
                        <label for="recieved_date" class="col-4 control-label">Search By Recieved Date</label>
                        <div class="col-4">
                            <input type="date" class="form-control" name="recieved_date" id="recieved_date"
                                value="<?php echo e($recievedDate ?? ''); ?>">
                        </div>
                        <div class="col-4">
                            <button class="btn btn-light mb-2 me-2" type="submit" name="search">Search</button>
                            <button class="btn btn-light mb-2" type="reset" name="reset">Reset</button>
                        </div>
                    </div>
                </form>
                <?php if ($flashAction === 'add' && $flashMaker) { ?>
                    <div class="alert alert-success" role="alert">
                        Roti Recieved from <strong><?php echo e($flashMaker['code']); ?></strong> on <strong><?php echo e($flashRecievedDate); ?></strong> is added
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'edit' && $flashMaker) { ?>
                    <div class="alert alert-info" role="alert">
                        Roti Recieved from <strong><?php echo e($flashMaker['code']); ?></strong> on <strong><?php echo e($flashRecievedDate); ?></strong> is edited
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'delete' && $flashMaker) { ?>
                    <div class="alert alert-danger" role="alert">
                        Roti Recieved from <strong><?php echo e($flashMaker['code']); ?></strong> on <strong><?php echo e($flashRecievedDate); ?></strong> is deleted
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'upload') { ?>
                    <div class="alert alert-success" role="alert">
                        Roti Recieved for whole week is uploaded
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'error') { ?>
                    <div class="alert alert-danger" role="alert">Please check the details you entered and try again.</div>
                <?php } ?>
                <div class="table-responsive">
                    <table id="roti" class="table table-striped" width="100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Code</th>
                                <th>Full Name</th>
                                <th>Recieved</th>
                                <th>Status</th>
                                <th>Recieved By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($receipts as $values) {
                                $hijridate = getHijriDate($values['recieved_date']);
                                $day = date('l', strtotime($values['recieved_date'])); ?>
                                <tr>
                                    <td data-sort="<?php echo strtotime($values['recieved_date']); ?>"><?php echo e(date('d M Y', strtotime($values['recieved_date'])) . ' - ' . $hijridate . ' (' . $day . ')'); ?></td>
                                    <td><?php echo e($values['code']); ?></td>
                                    <td><?php echo e($values['full_name']); ?></td>
                                    <td><strong>Packet: </strong> <?php echo (int) $values['roti_recieved'] / 4; ?> <br/>
                                    <strong>Total Roti: </strong><?php echo (int) $values['roti_recieved']; ?></td>
                                    <td class="<?php echo ($values['roti_status'] === 'pending' ? 'text-danger' : 'text-success'); ?>"><?php echo e(ucfirst($values['roti_status'])); ?></td>
                                    <td><?php echo e($values['username']); ?></td>
                                    <td><button type="button" class="btn btn-light"
                                        data-bs-target="#editrrecieved-<?php echo (int) $values['id']; ?>"
                                        data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                        class="btn btn-light"
                                        data-bs-target="#deleterrecieved-<?php echo (int) $values['id']; ?>"
                                        data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php foreach ($receipts as $values) { ?>
                <div class="modal fade" id="editrrecieved-<?php echo (int) $values['id']; ?>" tabindex="-1"
                    aria-labelledby="editrrecieved-<?php echo (int) $values['id']; ?>-Label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="editrrecieved-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="saverecieved.php" autocomplete="off">
                                <input type="hidden" name="action" value="edit_rrecieved" />
                                <input type="hidden" name="rrecieved_id" value="<?php echo (int) $values['id']; ?>" />
                                <input type="hidden" name="maker_id" value="<?php echo (int) $values['maker_id']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Update Roti Recieved</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3 row">
                                        <label for="recieved_date" class="col-4 control-label">Date</label>
                                        <div class="col-8">
                                            <input type="date" class="form-control" name="recieved_date"
                                                value="<?php echo e($values['recieved_date']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="col-4 control-label">Roti Maker</label>
                                        <div class="col-8">
                                            <input type="text" class="form-control" value="<?php echo e($values['code']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="roti_recieved" class="col-4 control-label">Roti Recieved</label>
                                        <div class="col-8">
                                            <input type="number" class="form-control" name="roti_recieved" min="0" value="<?php echo (int) $values['roti_recieved']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="roti_status" class="col-4 control-label">Roti Status</label>
                                        <div class="col-8">
                                            <div class="form-check">
                                                <input class="form-check-input roti_status" type="radio"
                                                    name="roti_status" id="roti_status1" value="pending" <?php echo ($values['roti_status'] === 'pending' ? 'checked' : ''); ?>
                                                    required>
                                                <label class="form-check-label" for="roti_status1">
                                                    Pending
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input roti_status" type="radio"
                                                    name="roti_status" id="roti_status2" value="recieved" <?php echo ($values['roti_status'] === 'recieved' ? 'checked' : ''); ?>
                                                    required>
                                                <label class="form-check-label" for="roti_status2">
                                                    Recieved
                                                </label>
                                            </div>
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

            <?php foreach ($receipts as $values) { ?>
                <div class="modal fade" id="deleterrecieved-<?php echo (int) $values['id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="deleterrecieved-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="saverecieved.php" autocomplete="off">
                                <input type="hidden" name="action" value="delete_rrecieved" />
                                <input type="hidden" name="rrecieved_id" value="<?php echo (int) $values['id']; ?>" />
                                <input type="hidden" name="recieved_date" value="<?php echo e($values['recieved_date']); ?>" />
                                <input type="hidden" name="maker_id" value="<?php echo (int) $values['maker_id']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Delete Roti Recieved</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <p> Are you sure you want to delete roti recieved on <strong><?php echo e($values['recieved_date']); ?></strong>
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

            <div class="modal fade" id="addrrecieved">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addrrecieved" class="form-horizontal" method="post" action="saverecieved.php" autocomplete="off">
                            <input type="hidden" name="action" value="add_rrecieved" />
                            <div class="modal-header">
                                <h4 class="modal-title">Add Roti Recieved</h4>
                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3 row">
                                    <label for="recieved_date" class="col-4 control-label">Date</label>
                                    <div class="col-8">
                                        <input type="date" class="form-control" name="recieved_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo e($recievedDate ?? date('Y-m-d')); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="maker_id" class="col-4 control-label">Roti Maker</label>
                                    <div class="col-8">
                                        <select class="form-select" name="maker_id" id="maker_id" required>
                                            <option value="">Select Roti Maker</option>
                                            <?php foreach ($allMakers as $roti_maker) { ?>
                                                <option value="<?php echo (int) $roti_maker['id']; ?>"><?php echo e($roti_maker['code']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="roti_recieved" class="col-4 control-label">Roti Recieved</label>
                                    <div class="col-8">
                                        <input type="number" class="form-control" name="roti_recieved" min="0" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="roti_status" class="col-4 control-label">Roti Status</label>
                                    <div class="col-8">
                                        <div class="form-check">
                                            <input class="form-check-input roti_status" type="radio"
                                                name="roti_status" id="roti_status1" value="pending"
                                                required>
                                            <label class="form-check-label" for="roti_status1">
                                                Pending
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input roti_status" type="radio"
                                                name="roti_status" id="roti_status2" value="recieved" checked
                                                required>
                                            <label class="form-check-label" for="roti_status2">
                                                Recieved
                                            </label>
                                        </div>
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

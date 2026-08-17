<?php
include('../header.php');
include('../navbar.php');
require_once('helpers.php');
include('../getHijriDate.php');

$distributionDate = $_GET['distribution_date'] ?? null;
$isValidDate = $distributionDate && DateTime::createFromFormat('Y-m-d', $distributionDate);

$baseQuery = "SELECT d.*, m.full_name, m.code, u.username
              FROM fmb_roti_distribution d
              LEFT JOIN fmb_roti_maker m ON m.id = d.maker_id
              LEFT JOIN users u ON u.email = d.distributed_by";

if ($isValidDate) {
    $result = db_query($link, $baseQuery . " WHERE d.distribution_date = ? ORDER BY d.distribution_date DESC", "s", [$distributionDate]);
} else {
    $result = db_query($link, $baseQuery . " ORDER BY d.distribution_date DESC");
}
$distributions = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);

$fmb_roti_maker_result = db_query($link, "SELECT `id`, `code` FROM fmb_roti_maker ORDER BY `full_name` ASC");
$allMakers = mysqli_fetch_all($fmb_roti_maker_result, MYSQLI_ASSOC);
mysqli_free_result($fmb_roti_maker_result);

$flashAction = $_GET['action'] ?? null;
$flashMakerId = (int) ($_GET['maker'] ?? 0);
$flashDistributionDate = $_GET['distribution_date'] ?? '';
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
                <h2 class="mb-3">FMB Roti Distribution <?php echo ($isValidDate ? 'on <strong>' . e(date('d F Y', strtotime($distributionDate))) . '</strong>' : ''); ?></h2>
            </div>
            <div class="col-6 text-end">
                <button type="button" class="btn btn-light mb-3" data-bs-target="#addrdistribute"
                    data-bs-toggle="modal">Add Roti Distribution</button>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <?php if (is_roti_privileged_user()) { ?>
                    <form id="distributeimport" class="form-horizontal my-3" method="POST"
                        action="distributeimport.php" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3 row">
                            <label for="distribute_import" class="col-4 control-label">Import Distribution Sheet</label>
                            <div class="col-4">
                                <input type="file" class="form-control" name="distribute_import" accept=".xlsx" id="distribute_import">
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
                        <label for="distribution_date" class="col-4 control-label">Search By Distribution Date</label>
                        <div class="col-4">
                            <input type="date" class="form-control" name="distribution_date" id="distribution_date"
                                value="<?php echo e($distributionDate ?? ''); ?>">
                        </div>
                        <div class="col-4">
                            <button class="btn btn-light mb-2 me-2" type="submit" name="search">Search</button>
                            <button class="btn btn-light mb-2" type="reset" name="reset">Reset</button>
                        </div>
                    </div>
                </form>
                <?php if ($flashAction === 'add' && $flashMaker) { ?>
                    <div class="alert alert-success" role="alert">
                        Flour & Oil distributed to <strong><?php echo e($flashMaker['code']); ?></strong> on <strong><?php echo e($flashDistributionDate); ?></strong> is added
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'edit' && $flashMaker) { ?>
                    <div class="alert alert-info" role="alert">
                        Flour & Oil distributed to <strong><?php echo e($flashMaker['code']); ?></strong> on <strong><?php echo e($flashDistributionDate); ?></strong> is edited
                        successfully.
                    </div>
                <?php } ?>
                <?php if ($flashAction === 'delete' && $flashMaker) { ?>
                    <div class="alert alert-danger" role="alert">
                        Flour & Oil distributed to <strong><?php echo e($flashMaker['code']); ?></strong> on <strong><?php echo e($flashDistributionDate); ?></strong> is deleted
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
                                <th>Flour Stock</th>
                                <th>Oil Stock</th>
                                <th>Packets Stock</th>
                                <th>Distributed By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($distributions as $values) {
                                $hijridate = getHijriDate($values['distribution_date']);
                                $day = date('l', strtotime($values['distribution_date'])); ?>
                                <tr>
                                    <td data-sort="<?php echo strtotime($values['distribution_date']); ?>"><?php echo e(date('d M Y', strtotime($values['distribution_date'])) . ' - ' . $hijridate . ' (' . $day . ')'); ?></td>
                                    <td><?php echo e($values['code']); ?></td>
                                    <td><?php echo e($values['full_name']); ?></td>
                                    <td><strong>Distributed:</strong> <?php echo (float) $values['flour_distributed']; ?> KG <br/><strong>Left:</strong> <?php echo (float) $values['flour_left']; ?> KG<br/><strong>Total :</strong> <?php echo (float) $values['flour_distributed'] + (float) $values['flour_left']; ?> KG</td>
                                    <td><strong>Distributed:</strong> <?php echo (float) $values['oil_distributed']; ?> Ltr<br/><strong>Left:</strong> <?php echo (float) $values['oil_left']; ?> Ltr<br/><strong>Total:</strong> <?php echo (float) $values['oil_distributed'] + (float) $values['oil_left']; ?> Ltr</td>
                                    <td><strong>Distributed:</strong> <?php echo (float) ($values['packets_distributed'] ?? 0); ?><br/><strong>Left:</strong> <?php echo (float) ($values['packets_left'] ?? 0); ?><br/><strong>Total:</strong> <?php echo (float) ($values['packets_distributed'] ?? 0) + (float) ($values['packets_left'] ?? 0); ?></td>
                                    <td><?php echo e($values['username']); ?></td>
                                    <td><button type="button" class="btn btn-light"
                                            data-bs-target="#editrdistribute-<?php echo (int) $values['id']; ?>"
                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                            class="btn btn-light"
                                            data-bs-target="#deleterdistribute-<?php echo (int) $values['id']; ?>"
                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php foreach ($distributions as $values) { ?>
                <div class="modal fade" id="editrdistribute-<?php echo (int) $values['id']; ?>" tabindex="-1"
                    aria-labelledby="editrdistribute-<?php echo (int) $values['id']; ?>-Label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="editrdistribute-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savedistribute.php" autocomplete="off">
                                <input type="hidden" name="action" value="edit_rdistribute" />
                                <input type="hidden" name="rdistribute_id" value="<?php echo (int) $values['id']; ?>" />
                                <input type="hidden" name="maker_id" value="<?php echo (int) $values['maker_id']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Update Roti Distribution</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3 row">
                                        <label for="distribution_date" class="col-4 control-label">Date</label>
                                        <div class="col-8">
                                            <input type="date" class="form-control" name="distribution_date"
                                                value="<?php echo e($values['distribution_date']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label class="col-4 control-label">Roti Maker</label>
                                        <div class="col-8">
                                            <input type="text" class="form-control" value="<?php echo e($values['code']); ?>" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="flour_distributed" class="col-4 control-label">Flour Distribution</label>
                                        <div class="col-8">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="flour_distributed" value="<?php echo (float) $values['flour_distributed']; ?>" step="0.01" min="0" required>
                                                <span class="input-group-text">KG</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="oil_distributed" class="col-4 control-label">Oil Distribution</label>
                                        <div class="col-8">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="oil_distributed" value="<?php echo (float) $values['oil_distributed']; ?>" step="0.01" min="0" required>
                                                <span class="input-group-text">Ltr</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="packets_distributed" class="col-4 control-label">Packets Distribution</label>
                                        <div class="col-8">
                                            <input type="number" class="form-control" name="packets_distributed" value="<?php echo (float) ($values['packets_distributed'] ?? 0); ?>" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <?php if (is_roti_privileged_user()) { ?>
                                        <div class="mb-3 row">
                                            <label for="flour_left" class="col-4 control-label">Flour Left</label>
                                            <div class="col-8">
                                                <div class="input-group">
                                                    <input type="number" class="form-control" name="flour_left" step="0.01" value="0">
                                                    <span class="input-group-text">KG</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label for="oil_left" class="col-4 control-label">Oil Left</label>
                                            <div class="col-8">
                                                <div class="input-group">
                                                    <input type="number" class="form-control" name="oil_left" step="0.01" value="0">
                                                    <span class="input-group-text">Ltr</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label for="packets_left" class="col-4 control-label">Packets Left</label>
                                            <div class="col-8">
                                                <input type="number" class="form-control" name="packets_left" step="0.01" value="0">
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-light">Update</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php foreach ($distributions as $values) { ?>
                <div class="modal fade" id="deleterdistribute-<?php echo (int) $values['id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="deleterdistribute-<?php echo (int) $values['id']; ?>" class="form-horizontal"
                                method="post" action="savedistribute.php" autocomplete="off">
                                <input type="hidden" name="action" value="delete_rdistribute" />
                                <input type="hidden" name="rdistribute_id" value="<?php echo (int) $values['id']; ?>" />
                                <input type="hidden" name="maker_id" value="<?php echo (int) $values['maker_id']; ?>" />
                                <input type="hidden" name="distribution_date" value="<?php echo e($values['distribution_date']); ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Delete Roti Distribute</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <p> Are you sure you want to delete distribution of <strong><?php echo e($values['distribution_date']); ?></strong>
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

            <div class="modal fade" id="addrdistribute">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="addrdistribute" class="form-horizontal" method="post" action="savedistribute.php" autocomplete="off">
                            <input type="hidden" name="action" value="add_rdistribute" />
                            <div class="modal-header">
                                <h4 class="modal-title">Add Roti Distribute</h4>
                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3 row">
                                <label for="distribution_date" class="col-4 control-label">Date</label>
                                    <div class="col-8">
                                        <input type="date" class="form-control" name="distribution_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo e($distributionDate ?? date('Y-m-d')); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="maker_id" class="col-4 control-label">Roti Maker</label>
                                    <div class="col-8">
                                        <select class="form-select" name="maker_id" required>
                                            <option value="">Select Roti Maker</option>
                                            <?php foreach ($allMakers as $roti_maker) { ?>
                                                <option value="<?php echo (int) $roti_maker['id']; ?>"><?php echo e($roti_maker['code']); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="flour_distributed" class="col-4 control-label">Flour Distribute</label>
                                    <div class="col-8">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="flour_distributed" step="0.01" min="0" required>
                                            <span class="input-group-text">KG</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="oil_distributed" class="col-4 control-label">Oil Distribute</label>
                                    <div class="col-8">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="oil_distributed" step="0.01" min="0" required>
                                            <span class="input-group-text">Ltr</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="packets_distributed" class="col-4 control-label">Packets Distribute</label>
                                    <div class="col-8">
                                        <input type="number" class="form-control" name="packets_distributed" step="0.01" min="0" value="0">
                                    </div>
                                </div>
                                <?php if (is_roti_privileged_user()) { ?>
                                    <div class="mb-3 row">
                                        <label for="flour_left" class="col-4 control-label">Flour Left</label>
                                        <div class="col-8">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="flour_left" step="0.01" value="0" required>
                                                <span class="input-group-text">KG</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="oil_left" class="col-4 control-label">Oil Left</label>
                                        <div class="col-8">
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="oil_left" step="0.01" value="0" required>
                                                <span class="input-group-text">Ltr</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 row">
                                        <label for="packets_left" class="col-4 control-label">Packets Left</label>
                                        <div class="col-8">
                                            <input type="number" class="form-control" name="packets_left" step="0.01" value="0">
                                        </div>
                                    </div>
                                <?php } ?>
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

<?php
include('header.php');
include('navbar.php');
$result = mysqli_query($link, "SELECT * FROM fmb_roti_distribution order by `distribution_date` DESC") or die(mysqli_error($link));
?>
<div class="content mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h2 class="mb-3">FMB Roti Distribution</h2>
                            </div>
                            <div class="col-6 text-end">
                                <button type="button" class="btn btn-light mb-3" data-bs-target="#addrdistribute"
                                    data-bs-toggle="modal">Add Roti Distribution</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'add') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong>Flour & Oil distributed on <?php echo $_GET['distribution_date']; ?></strong> is added
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong>Flour & Oil distributed on <?php echo $_GET['distribution_date']; ?></strong> is edited
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong>Flour & Oil distributed on <?php echo $_GET['distribution_date']; ?></strong> is deleted
                                        successfully.
                                    </div>
                                <?php } ?>
                                <div class="table-responsive">
                                    <table class="table table-striped display" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Full Name</th>
                                                <th>Flour Distributed</th>
                                                <th>Flour Left</th>
                                                <th>Total Flour</th>
                                                <th>Oil Distributed</th>
                                                <th>Oil Left</th>
                                                <th>Total Oil</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($values = mysqli_fetch_assoc($result)) {
                                                $fmb_roti_maker = mysqli_query($link, "SELECT `full_name` FROM fmb_roti_maker WHERE `id` = '".$values['maker_id']."'") or die(mysqli_error($link));
                                                $roti_maker = $fmb_roti_maker->fetch_assoc(); ?>
                                                <tr>
                                                <td><?php echo date('d M Y', strtotime($values['distribution_date'])); ?></td>
                                                    <td><?php echo $roti_maker['full_name']; ?></td>
                                                    <td><?php echo $values['flour_distributed']; ?> KG</td>
                                                    <td><?php echo $values['flour_left']; ?> KG</td>
                                                    <td><?php echo $values['flour_distributed'] + $values['flour_left']; ?> KG</td>
                                                    <td><?php echo $values['oil_distributed']; ?> Ltr</td>
                                                    <td><?php echo $values['oil_left']; ?> Ltr</td>
                                                    <td><?php echo $values['oil_distributed'] + $values['oil_left']; ?> Ltr</td>
                                                    <td><button type="button" class="btn btn-light"
                                                            data-bs-target="#editrdistribute-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                                            class="btn btn-light"
                                                            data-bs-target="#deleterdistribute-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                            <?php }
                                            mysqli_free_result($result); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <?php $result = mysqli_query($link, "SELECT * FROM fmb_roti_distribution order by `distribution_date` DESC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { 
                                $fmb_roti_maker = mysqli_query($link, "SELECT * FROM fmb_roti_maker order by `full_name` ASC") or die(mysqli_error($link)); ?>
                                <div class="modal fade" id="editrdistribute-<?php echo $values['id']; ?>" tabindex="-1"
                                    aria-labelledby="editrdistribute-<?php echo $values['id']; ?>-Label" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="editrdistribute-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="saverdistribute.php" autocomplete="off">
                                                <input type="hidden" name="action" value="edit_rdistribute" />
                                                <input type="hidden" name="rdistribution_id" value="<?php echo $values['id']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Update Roti Distribution</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3 row">
                                                        <label for="distribution_date" class="col-4 control-label">Date</label>
                                                        <div class="col-8">
                                                            <input type="date" class="form-control" name="distribution_date"
                                                                value="<?php echo $values['distribution_date']; ?>" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="maker_id" class="col-4 control-label">Roti Maker</label>
                                                        <div class="col-8">
                                                            <select type="text" class="form-select" name="maker_id" required>
                                                                <option value="">Select Roti Maker</option>
                                                                <?php while ($roti_maker = mysqli_fetch_assoc($fmb_roti_maker)) { ?>
                                                                    <option value="<?php echo $roti_maker['id']; ?>" <?php echo ($roti_maker['id'] == $values['maker_id'] ? 'selected' : ''); ?> ><?php echo $roti_maker['full_name']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="flour_distributed" class="col-4 control-label">Flour Distribution</label>
                                                        <div class="col-8">
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" name="flour_distributed" value="<?php echo $values['flour_distributed']; ?>" required>
                                                                <span class="input-group-text">KG</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="oil_distributed" class="col-4 control-label">Oil Distribution</label>
                                                        <div class="col-8">
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" name="oil_distributed" value="<?php echo $values['oil_distributed']; ?>" required>
                                                                <span class="input-group-text">Ltr</span>
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
                            <?php }
                            mysqli_free_result($result);
                            mysqli_free_result($fmb_roti_maker); ?>

                            <?php $result = mysqli_query($link, "SELECT * FROM fmb_roti_distribution order by `distribution_date` DESC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="deleterdistribute-<?php echo $values['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="deleterdistribute-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="saverdistribute.php" autocomplete="off">
                                                <input type="hidden" name="action" value="delete_rdistribute" />
                                                <input type="hidden" name="rdistribute_id" value="<?php echo $values['id']; ?>" />
                                                <input type="hidden" name="distribution_date" value="<?php echo $values['distribution_date']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Delete Roti Distribute</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p> Are you sure you want to delete distribution of <strong><?php echo $values['distribution_date']; ?></strong>
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
                            mysqli_free_result($result);  ?>

                            <?php $fmb_roti_maker = mysqli_query($link, "SELECT * FROM fmb_roti_maker order by `full_name` ASC") or die(mysqli_error($link)); ?>
                            <div class="modal fade" id="addrdistribute">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form id="addrdistribute" class="form-horizontal" method="post" action="saverdistribute.php" autocomplete="off">
                                            <input type="hidden" name="action" value="add_rdistribute" />
                                            <div class="modal-header">
                                                <h4 class="modal-title fs-5">Add Roti Distribute</h4>
                                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3 row">
                                                <label for="its_no" class="col-4 control-label">Date</label>
                                                    <div class="col-8">
                                                        <input type="date" class="form-control" name="distribution_date"
                                                                required>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="maker_id" class="col-4 control-label">Roti Maker</label>
                                                    <div class="col-8">
                                                        <select type="text" class="form-select" name="maker_id" required>
                                                            <option value="">Select Roti Maker</option>
                                                            <?php while ($roti_maker = mysqli_fetch_assoc($fmb_roti_maker)) { 
                                                                echo '<option value="'.$roti_maker['id'].'">'.$roti_maker['full_name'].'</option>';
                                                            } mysqli_free_result($fmb_roti_maker); ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="flour_distributed" class="col-4 control-label">Flour Distribute</label>
                                                    <div class="col-8">
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" name="flour_distributed" required>
                                                            <span class="input-group-text">KG</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="oil_distributed" class="col-4 control-label">Oil Distribute</label>
                                                    <div class="col-8">
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" name="oil_distributed" required>
                                                            <span class="input-group-text">Ltr</span>
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
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
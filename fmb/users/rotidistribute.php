<?php
include('header.php');
include('navbar.php');
include('getHijriDate.php');

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
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'add') {
                                    $add_roti_maker = mysqli_query($link, "SELECT `code` FROM fmb_roti_maker WHERE `id` = '".$_GET['maker']."'") or die(mysqli_error($link));
                                    $add_maker = $add_roti_maker->fetch_assoc(); ?>
                                    <div class="alert alert-success" role="alert">
                                        Flour & Oil distributed</strong> to <strong><?php echo $add_maker['code']; ?></strong> on <strong><?php echo $_GET['distribution_date']; ?></strong> is added
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') {
                                     $edit_roti_maker = mysqli_query($link, "SELECT `code` FROM fmb_roti_maker WHERE `id` = '".$_GET['maker']."'") or die(mysqli_error($link));
                                     $edit_maker = $edit_roti_maker->fetch_assoc(); ?>
                                    <div class="alert alert-info" role="alert">
                                        Flour & Oil distributed to <strong><?php echo $edit_maker['code']; ?></strong> on <strong><?php echo $_GET['distribution_date']; ?></strong> is edited
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') {
                                    $delete_roti_maker = mysqli_query($link, "SELECT `code` FROM fmb_roti_maker WHERE `id` = '".$_GET['maker']."'") or die(mysqli_error($link));
                                    $delete_maker = $delete_roti_maker->fetch_assoc(); ?>
                                    <div class="alert alert-danger" role="alert">
                                        Flour & Oil distributed to <strong><?php echo $delete_maker['code']; ?></strong> on <strong><?php echo $_GET['distribution_date']; ?></strong> is deleted
                                        successfully.
                                    </div>
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
                                                <th>Distributed By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($values = mysqli_fetch_assoc($result)) {
                                                $fmb_roti_maker = mysqli_query($link, "SELECT `full_name`, `code` FROM fmb_roti_maker WHERE `id` = '".$values['maker_id']."'") or die(mysqli_error($link));
                                                $roti_maker = $fmb_roti_maker->fetch_assoc();
                                                $user_row = mysqli_query($link, "SELECT `username` FROM users WHERE `email` = '".$values['distributed_by']."'") or die(mysqli_error($link));
                                                $user = $user_row->fetch_assoc();
                                                $hijridate = getHijriDate($values['distribution_date']);
                                                $day = date('l', strtotime($values['distribution_date'])); ?>
                                                <tr>
                                                <td><?php echo date('d M Y', strtotime($values['distribution_date'])).' - '.$hijridate . ' (' . $day . ')'; ?></td>
                                                    <td><?php echo $roti_maker['code']; ?></td>
                                                    <td><?php echo $roti_maker['full_name']; ?></td>
                                                    <td><strong>Distributed:</strong> <?php echo $values['flour_distributed']; ?> KG <br/><strong>Left:</strong> <?php echo $values['flour_left']; ?> KG<br/><strong>Total :</strong> <?php echo $values['flour_distributed'] + $values['flour_left']; ?> KG</td>
                                                    <td><strong>Distributed:</strong> <?php echo $values['oil_distributed']; ?> Ltr<br/><strong>Left:</strong> <?php echo $values['oil_left']; ?> Ltr<br/><strong>Total:</strong> <?php echo $values['oil_distributed'] + $values['oil_left']; ?> Ltr</td>
                                                    <td><?php echo $user['username']; ?></td>
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
                                                <input type="hidden" name="rdistribute_id" value="<?php echo $values['id']; ?>" />
                                                <input type="hidden" name="distributed_by" value="<?php echo $_SESSION['email']; ?>" />
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
                                                                    <option value="<?php echo $roti_maker['id']; ?>" <?php echo ($roti_maker['id'] == $values['maker_id'] ? 'selected' : ''); ?> ><?php echo $roti_maker['code']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="flour_distributed" class="col-4 control-label">Flour Distribution</label>
                                                        <div class="col-8">
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" name="flour_distributed" value="<?php echo $values['flour_distributed']; ?>" step="0.01" min="0" required>
                                                                <span class="input-group-text">KG</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="oil_distributed" class="col-4 control-label">Oil Distribution</label>
                                                        <div class="col-8">
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" name="oil_distributed" value="<?php echo $values['oil_distributed']; ?>" step="0.01" min="0" required>
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
                            <?php mysqli_free_result($fmb_roti_maker); }
                            mysqli_free_result($result); ?>

                            <?php $result = mysqli_query($link, "SELECT * FROM fmb_roti_distribution order by `distribution_date` DESC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="deleterdistribute-<?php echo $values['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="deleterdistribute-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="saverdistribute.php" autocomplete="off">
                                                <input type="hidden" name="action" value="delete_rdistribute" />
                                                <input type="hidden" name="rdistribute_id" value="<?php echo $values['id']; ?>" />
                                                <input type="hidden" name="maker_id" value="<?php echo $values['maker_id']; ?>" />
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
                                            <input type="hidden" name="distributed_by" value="<?php echo $_SESSION['email']; ?>" />
                                            <div class="modal-header">
                                                <h4 class="modal-title fs-5">Add Roti Distribute</h4>
                                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3 row">
                                                <label for="its_no" class="col-4 control-label">Date</label>
                                                    <div class="col-8">
                                                        <input type="date" class="form-control" name="distribution_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="maker_id" class="col-4 control-label">Roti Maker</label>
                                                    <div class="col-8">
                                                        <select type="text" class="form-select" name="maker_id" required>
                                                            <option value="">Select Roti Maker</option>
                                                            <?php while ($roti_maker = mysqli_fetch_assoc($fmb_roti_maker)) { 
                                                                echo '<option value="'.$roti_maker['id'].'">'.$roti_maker['code'].'</option>';
                                                            } mysqli_free_result($fmb_roti_maker); ?>
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
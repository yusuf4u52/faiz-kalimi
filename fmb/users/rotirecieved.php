<?php
include('header.php');
include('navbar.php');
$result = mysqli_query($link, "SELECT * FROM fmb_roti_recieved order by `recieved_date` DESC") or die(mysqli_error($link));
?>
<div class="content mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h2 class="mb-3">FMB Roti Recieved</h2>
                            </div>
                            <div class="col-6 text-end">
                                <button type="button" class="btn btn-light mb-3" data-bs-target="#addrrecieved"
                                    data-bs-toggle="modal">Add Roti Recieved</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'add') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong>Roti recieved on <?php echo $_GET['recieved_date']; ?></strong> is added
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong>Roti recieved on <?php echo $_GET['recieved_date']; ?></strong> is edited
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong>Roti recieved on <?php echo $_GET['recieved_date']; ?></strong> is deleted
                                        successfully.
                                    </div>
                                <?php } ?>
                                <div class="table-responsive">
                                    <table class="table table-striped display" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Full Name</th>
                                                <th>Roti Recieved</th>
                                                <th>Flour Left</th>
                                                <th>Oil Left</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($values = mysqli_fetch_assoc($result)) {
                                                $fmb_roti_maker = mysqli_query($link, "SELECT `full_name` FROM fmb_roti_maker WHERE `id` = '".$values['maker_id']."'") or die(mysqli_error($link));
                                                $roti_maker = $fmb_roti_maker->fetch_assoc(); ?>
                                                <tr>
                                                <td><?php echo date('d M Y', strtotime($values['recieved_date'])); ?></td>
                                                    <td><?php echo $roti_maker['full_name']; ?></td>
                                                    <td><?php echo $values['roti_recieved']; ?> Rotis</td>
                                                    <td><?php echo $values['flour_left']; ?> KG</td>
                                                    <td><?php echo $values['oil_left']; ?> Ltr</td>
                                                    <td><button type="button" class="btn btn-light"
                                                            data-bs-target="#editrrecieved-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                                            class="btn btn-light"
                                                            data-bs-target="#deleterrecieved-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                            <?php }
                                            mysqli_free_result($result); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <?php $result = mysqli_query($link, "SELECT * FROM fmb_roti_recieved order by `recieved_date` DESC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) {
                                $fmb_roti_maker = mysqli_query($link, "SELECT * FROM fmb_roti_maker order by `full_name` ASC") or die(mysqli_error($link)); ?>
                                <div class="modal fade" id="editrrecieved-<?php echo $values['id']; ?>" tabindex="-1"
                                    aria-labelledby="editrrecieved-<?php echo $values['id']; ?>-Label" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="editrrecieved-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="saverrecieved.php" autocomplete="off">
                                                <input type="hidden" name="action" value="edit_rrecieved" />
                                                <input type="hidden" name="rrecieved_id" value="<?php echo $values['id']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Update Roti Recieved</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3 row">
                                                        <label for="recieved_date" class="col-4 control-label">Date</label>
                                                        <div class="col-8">
                                                            <input type="date" class="form-control" name="recieved_date"
                                                                value="<?php echo $values['recieved_date']; ?>" readonly>
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
                                                        <label for="roti_recieved" class="col-4 control-label">Aato Distribution</label>
                                                        <div class="col-8">
                                                            <input type="number" class="form-control" name="roti_recieved" value="<?php echo $values['roti_recieved']; ?>" required>
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

                            <?php $result = mysqli_query($link, "SELECT * FROM fmb_roti_recieved order by `recieved_date` DESC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="deleterrecieved-<?php echo $values['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="deleterrecieved-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="saverrecieved.php" autocomplete="off">
                                                <input type="hidden" name="action" value="delete_rrecieved" />
                                                <input type="hidden" name="rrecieved_id" value="<?php echo $values['id']; ?>" />
                                                <input type="hidden" name="recieved_date" value="<?php echo $values['recieved_date']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Delete Roti Recieved</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p> Are you sure you want to delete roti recieved on <strong><?php echo $values['recieved_date']; ?></strong>
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
                            <div class="modal fade" id="addrrecieved">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form id="addrrecieved" class="form-horizontal" method="post" action="saverrecieved.php" autocomplete="off">
                                            <input type="hidden" name="action" value="add_rrecieved" />
                                            <div class="modal-header">
                                                <h4 class="modal-title fs-5">Add Roti Recieved</h4>
                                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3 row">
                                                <label for="its_no" class="col-4 control-label">Date</label>
                                                    <div class="col-8">
                                                        <input type="date" class="form-control" name="recieved_date"
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
                                                    <label for="roti_recieved" class="col-4 control-label">Roti Recieved</label>
                                                    <div class="col-8">
                                                        <input type="number" class="form-control" name="roti_recieved" required>
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
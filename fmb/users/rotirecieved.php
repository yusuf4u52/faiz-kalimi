<?php
include('header.php');
include('navbar.php');
include('getHijriDate.php');

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
                            <?php if (isset($_GET['action']) && $_GET['action'] == 'add') {
                                    $add_roti_maker = mysqli_query($link, "SELECT `code` FROM fmb_roti_maker WHERE `id` = '".$_GET['maker']."'") or die(mysqli_error($link));
                                    $add_maker = $add_roti_maker->fetch_assoc(); ?>
                                    <div class="alert alert-success" role="alert">
                                        Roti Recieved from <strong><?php echo $add_maker['code']; ?></strong> on <strong><?php echo $_GET['recieved_date']; ?></strong> is added
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') {
                                     $edit_roti_maker = mysqli_query($link, "SELECT `code` FROM fmb_roti_maker WHERE `id` = '".$_GET['maker']."'") or die(mysqli_error($link));
                                     $edit_maker = $edit_roti_maker->fetch_assoc(); ?>
                                    <div class="alert alert-info" role="alert">
                                    Roti Recieved from <strong><?php echo $edit_maker['code']; ?></strong> on <strong><?php echo $_GET['recieved_date']; ?></strong> is edited
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') {
                                    $delete_roti_maker = mysqli_query($link, "SELECT `code` FROM fmb_roti_maker WHERE `id` = '".$_GET['maker']."'") or die(mysqli_error($link));
                                    $delete_maker = $delete_roti_maker->fetch_assoc(); ?>
                                    <div class="alert alert-danger" role="alert">
                                    Roti Recieved from <strong><?php echo $delete_maker['code']; ?></strong> on <strong><?php echo $_GET['recieved_date']; ?></strong> is deleted
                                        successfully.
                                    </div>
                                <?php } ?>
                                <div class="table-responsive">
                                    <table id="roti" class="table table-striped" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Full Name</th>
                                                <th>Code</th>
                                                <th>Roti Recieved</th>
                                                <th>Stock Left</th>
                                                <th>Recieved By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($values = mysqli_fetch_assoc($result)) {
                                                $fmb_roti_maker = mysqli_query($link, "SELECT `full_name`, `code` FROM fmb_roti_maker WHERE `id` = '".$values['maker_id']."'") or die(mysqli_error($link));
                                                $roti_maker = $fmb_roti_maker->fetch_assoc();
                                                $user_row = mysqli_query($link, "SELECT `username` FROM users WHERE `email` = '".$values['recieved_by']."'") or die(mysqli_error($link));
                                                $user = $user_row->fetch_assoc();
                                                $hijridate = getHijriDate($values['recieved_date']);
                                                $day = date('l', strtotime($values['recieved_date'])); ?>
                                                <tr>
                                                <td><?php echo date('d M Y', strtotime($values['recieved_date'])) .' - '.$hijridate . ' (' . $day . ')'; ?></td>
                                                    <td><?php echo $roti_maker['full_name']; ?></td>
                                                    <td><?php echo $roti_maker['code']; ?></td>
                                                    <td><?php echo $values['roti_recieved']; ?> Rotis</td>
                                                    <td><strong>FLour: </strong><?php echo $values['flour_left']; ?> KG <br/> <strong>Oil: </strong><?php echo $values['oil_left']; ?> Ltr</td>
                                                    <td><?php echo $user['username']; ?></td>
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
                                                <input type="hidden" name="recieved_by" value="<?php echo $_SESSION['email']; ?>" />
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
                                                                    <option value="<?php echo $roti_maker['id']; ?>" <?php echo ($roti_maker['id'] == $values['maker_id'] ? 'selected' : ''); ?> ><?php echo $roti_maker['code']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="roti_recieved" class="col-4 control-label">Roti Recieved</label>
                                                        <div class="col-8">
                                                            <input type="number" class="form-control" name="roti_recieved" value="<?php echo $values['roti_recieved']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="flour_left" class="col-4 control-label">Flour Left</label>
                                                        <div class="col-8">
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" name="flour_left" min="0" value="<?php echo $values['flour_left']; ?>" step="0.01" required>
                                                                <span class="input-group-text">KG</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="oil_left" class="col-4 control-label">Oil Left</label>
                                                        <div class="col-8">
                                                            <div class="input-group">
                                                                <input type="number" class="form-control" name="oil_left" value="<?php echo $values['oil_left']; ?>" step="0.01" required>
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
                                                <input type="hidden" name="maker_id" value="<?php echo $values['maker_id']; ?>" />
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
                                            <input type="hidden" name="recieved_by" value="<?php echo $_SESSION['email']; ?>" />
                                            <div class="modal-header">
                                                <h4 class="modal-title fs-5">Add Roti Recieved</h4>
                                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3 row">
                                                <label for="its_no" class="col-4 control-label">Date</label>
                                                    <div class="col-8">
                                                        <input type="date" class="form-control" name="recieved_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
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
                                                    <label for="roti_recieved" class="col-4 control-label">Roti Recieved</label>
                                                    <div class="col-8">
                                                        <input type="number" class="form-control" name="roti_recieved" min="0" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="flour_left" class="col-4 control-label">Flour Left</label>
                                                    <div class="col-8">
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" name="flour_left" step="0.01" required>
                                                            <span class="input-group-text">KG</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="oil_left" class="col-4 control-label">Oil Left</label>
                                                    <div class="col-8">
                                                        <div class="input-group">
                                                            <input type="number" class="form-control" name="oil_left" step="0.01" required>
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
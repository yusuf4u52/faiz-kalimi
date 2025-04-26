<?php
include('header.php');
include('navbar.php');
include('getHijriDate.php');

$result = mysqli_query($link, "SELECT * FROM transporter_thali_count order by `count_date` DESC") or die(mysqli_error($link));
?>
<div class="content mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h2 class="mb-3">Transporter Thali Count</h2>
                            </div>
                            <div class="col-6 text-end">
                                <button type="button" class="btn btn-light mb-3" data-bs-target="#addtcount"
                                    data-bs-toggle="modal">Add Thali Count</button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                            <?php if (isset($_GET['action']) && $_GET['action'] == 'add') {
                                    $add_transporter = mysqli_query($link, "SELECT `Name` FROM transporters WHERE `id` = '".$_GET['transporter']."'") or die(mysqli_error($link));
                                    $transporter = $add_transporter->fetch_assoc(); ?>
                                    <div class="alert alert-success" role="alert">
                                        Thali Count of <strong><?php echo $transporter['Name']; ?></strong> on <strong><?php echo $_GET['count_date']; ?></strong> is added
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') {
                                    $edit_transporter = mysqli_query($link, "SELECT `Name` FROM transporters WHERE `id` = '".$_GET['transporter']."'") or die(mysqli_error($link));
                                    $transporter = $edit_transporter->fetch_assoc(); ?>
                                    <div class="alert alert-info" role="alert">
                                    Thali Count of <strong><?php echo $transporter['Name']; ?></strong> on <strong><?php echo $_GET['count_date']; ?></strong> is edited
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') {
                                    $delete_transporter = mysqli_query($link, "SELECT `Name` FROM transporters WHERE `id` = '".$_GET['transporter']."'") or die(mysqli_error($link));
                                    $transporter = $delete_transporter->fetch_assoc(); ?>
                                    <div class="alert alert-danger" role="alert">
                                    Thali Count of <strong><?php echo $transporter['Name']; ?></strong> on <strong><?php echo $_GET['count_date']; ?></strong> is deleted
                                        successfully.
                                    </div>
                                <?php } ?>
                                <div class="table-responsive">
                                    <table id="roti" class="table table-striped" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Full Name</th>
                                                <th>Thali Count</th>
                                                <th>Counted By</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($values = mysqli_fetch_assoc($result)) {
                                                $transporters = mysqli_query($link, "SELECT `Name` FROM transporters WHERE `id` = '".$values['transporter_id']."'") or die(mysqli_error($link));
                                                $transporter = $transporters->fetch_assoc();
                                                $user_row = mysqli_query($link, "SELECT `username` FROM users WHERE `email` = '".$values['counted_by']."'") or die(mysqli_error($link));
                                                $user = $user_row->fetch_assoc();
                                                $hijridate = getHijriDate($values['count_date']);
                                                $day = date('l', strtotime($values['count_date'])); ?>
                                                <tr>
                                                    <td><?php echo date('d M Y', strtotime($values['count_date'])) .' - '.$hijridate . ' (' . $day . ')'; ?></td>
                                                    <td><?php echo $transporter['Name']; ?></td>
                                                    <td><?php echo $values['thali_count']; ?> Thali</td>
                                                    <td><?php echo $user['username']; ?></td>
                                                    <td><button type="button" class="btn btn-light"
                                                            data-bs-target="#edittcount-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                                            class="btn btn-light"
                                                            data-bs-target="#deletetcount-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                            <?php }
                                            mysqli_free_result($result); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <?php $result = mysqli_query($link, "SELECT * FROM transporter_thali_count order by `count_date` DESC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) {
                                $transporters = mysqli_query($link, "SELECT * FROM transporters order by `Name` ASC") or die(mysqli_error($link)); ?>
                                <div class="modal fade" id="edittcount-<?php echo $values['id']; ?>" tabindex="-1"
                                    aria-labelledby="edittcount-<?php echo $values['id']; ?>-Label" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="edittcount-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savethalicount.php" autocomplete="off">
                                                <input type="hidden" name="action" value="edit_tcount" />
                                                <input type="hidden" name="tcount_id" value="<?php echo $values['id']; ?>" />
                                                <input type="hidden" name="counted_by" value="<?php echo $_SESSION['email']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Update Thali Count</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3 row">
                                                        <label for="count_date" class="col-4 control-label">Date</label>
                                                        <div class="col-8">
                                                            <input type="date" class="form-control" name="count_date"
                                                                value="<?php echo $values['count_date']; ?>" readonly>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="transporter_id" class="col-4 control-label">Transporter</label>
                                                        <div class="col-8">
                                                            <select type="text" class="form-select" name="transporter_id" required>
                                                                <option value="">Select Transporter</option>
                                                                <?php while ($transporter = mysqli_fetch_assoc($transporters)) { ?>
                                                                    <option value="<?php echo $transporter['id']; ?>" <?php echo ($transporter['id'] == $values['transporter_id'] ? 'selected' : ''); ?> ><?php echo $transporter['Name']; ?></option>
                                                                <?php } ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="thali_count" class="col-4 control-label">Thali Count</label>
                                                        <div class="col-8">
                                                            <input type="number" class="form-control" name="thali_count" value="<?php echo $values['thali_count']; ?>" required>
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
                            <?php mysqli_free_result($transporters); }
                            mysqli_free_result($result); ?>

                            <?php $result = mysqli_query($link, "SELECT * FROM transporter_thali_count order by `count_date` DESC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="deletetcount-<?php echo $values['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="deletetcount-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savethalicount.php" autocomplete="off">
                                                <input type="hidden" name="action" value="delete_tcount" />
                                                <input type="hidden" name="tcount_id" value="<?php echo $values['id']; ?>" />
                                                <input type="hidden" name="count_date" value="<?php echo $values['count_date']; ?>" />
                                                <input type="hidden" name="transporter_id" value="<?php echo $values['transporter_id']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Delete Thali Count</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p> Are you sure you want to delete thali count on <strong><?php echo $values['count_date']; ?></strong>
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

                            <?php $transporters = mysqli_query($link, "SELECT * FROM transporters order by `Name` ASC") or die(mysqli_error($link)); ?>
                            <div class="modal fade" id="addtcount">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form id="addtcount" class="form-horizontal" method="post" action="savethalicount.php" autocomplete="off">
                                            <input type="hidden" name="action" value="add_tcount" />
                                            <input type="hidden" name="counted_by" value="<?php echo $_SESSION['email']; ?>" />
                                            <div class="modal-header">
                                                <h4 class="modal-title fs-5">Add Thali Count</h4>
                                                <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                    aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3 row">
                                                <label for="its_no" class="col-4 control-label">Date</label>
                                                    <div class="col-8">
                                                        <input type="date" class="form-control" name="count_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="transporter_id" class="col-4 control-label">Transporter</label>
                                                    <div class="col-8">
                                                        <select type="text" class="form-select" name="transporter_id" required>
                                                            <option value="">Select Transporter</option>
                                                            <?php while ($transporter = mysqli_fetch_assoc($transporters)) { 
                                                                echo '<option value="'.$transporter['id'].'">'.$transporter['Name'].'</option>';
                                                            } mysqli_free_result($transporters); ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="thali_count" class="col-4 control-label">Thali Count</label>
                                                    <div class="col-8">
                                                        <input type="number" class="form-control" name="thali_count" required>
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
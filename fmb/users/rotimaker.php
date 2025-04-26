<?php
include('header.php');
include('navbar.php');
$result = mysqli_query($link, "SELECT * FROM fmb_roti_maker order by `full_name` ASC") or die(mysqli_error($link));
?>
<div class="content mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
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
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'add') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong><?php echo $_GET['full_name']; ?></strong> is added
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong><?php echo $_GET['full_name']; ?></strong> is edited
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong><?php echo $_GET['full_name']; ?></strong> is deleted
                                        successfully.
                                    </div>
                                <?php } ?>
                                <div class="table-responsive">
                                    <table class="table table-striped display" width="100%">
                                        <thead>
                                            <tr>
                                                <th>ITS No</th>
                                                <th>Full Name</th>
                                                <th>Code</th>
                                                <th>Mobile No</th>
                                                <th>Bank Details</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($values = mysqli_fetch_assoc($result)) {
                                                $bank_details = htmlspecialchars( $values['bank_details'] );
                                                $paragraphs = explode( "\n", $bank_details ); ?>
                                                <tr>
                                                    <td><?php echo $values['its_no']; ?></td>
                                                    <td><?php echo $values['full_name']; ?></td>
                                                    <td><?php echo $values['code']; ?></td>
                                                    <td><?php echo $values['mobile_no']; ?></td>
                                                    <td><?php foreach( $paragraphs as $para ) {
                                                        echo '<p class="mb-1">'.$para.'</p>';
                                                    } ?></td>
                                                    <td><button type="button" class="btn btn-light"
                                                            data-bs-target="#editrmaker-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                                            class="btn btn-light"
                                                            data-bs-target="#deletermaker-<?php echo $values['id']; ?>"
                                                            data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button></td>
                                                </tr>
                                            <?php }
                                            mysqli_free_result($result); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <?php $result = mysqli_query($link, "SELECT * FROM fmb_roti_maker order by `full_name` ASC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="editrmaker-<?php echo $values['id']; ?>" tabindex="-1"
                                    aria-labelledby="editrmaker-<?php echo $values['id']; ?>-Label" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="editrmaker-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savermaker.php" autocomplete="off">
                                                <input type="hidden" name="action" value="edit_rmaker" />
                                                <input type="hidden" name="rmaker_id" value="<?php echo $values['id']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Update Roti Maker</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3 row">
                                                        <label for="its_no" class="col-4 control-label">ITS No</label>
                                                        <div class="col-8">
                                                            <input type="number" class="form-control" name="its_no"
                                                                value="<?php echo $values['its_no']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="full_name" class="col-4 control-label">Full Name</label>
                                                        <div class="col-8">
                                                            <input type="text" class="form-control" name="full_name"
                                                                value="<?php echo $values['full_name']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="code" class="col-4 control-label">Code</label>
                                                        <div class="col-8">
                                                            <input type="text" class="form-control" name="code"
                                                                value="<?php echo $values['code']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="mobile_no" class="col-4 control-label">Mobile No</label>
                                                        <div class="col-8">
                                                            <input type="number" class="form-control" name="mobile_no"
                                                            value="<?php echo $values['mobile_no']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="bank_details" class="col-4 control-label">Bank Details</label>
                                                        <div class="col-8">
                                                            <textarea class="form-control" name="bank_details"  rows="3"
                                                                required><?php echo $values['bank_details']; ?></textarea>
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
                            mysqli_free_result($result); ?>

                            <?php $result = mysqli_query($link, "SELECT * FROM fmb_roti_maker order by `full_name` ASC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="deletermaker-<?php echo $values['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="deletermaker-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savermaker.php" autocomplete="off">
                                                <input type="hidden" name="action" value="delete_rmaker" />
                                                <input type="hidden" name="rmaker_id" value="<?php echo $values['id']; ?>" />
                                                <input type="hidden" name="full_name" value="<?php echo $values['full_name']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Delete Roti Maker</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p> Are you sure you want to delete <strong><?php echo $values['full_name']; ?></strong>
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
                            mysqli_free_result($result); ?>

                            <div class="modal fade" id="addrmaker">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form id="addrmaker" class="form-horizontal" method="post" action="savermaker.php" autocomplete="off">
                                            <input type="hidden" name="action" value="add_rmaker" />
                                            <div class="modal-header">
                                                <h4 class="modal-title fs-5">Add Roti Maker</h4>
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
                                                        <input type="number" class="form-control" name="mobile_no" required>
                                                    </div>
                                                </div>
                                                <div class="mb-3 row">
                                                    <label for="bank_details" class="col-4 control-label">Bank Details</label>
                                                    <div class="col-8">
                                                        <textarea class="form-control" name="bank_details"  rows="3"
                                                            required><?php echo $values['bank_details']; ?></textarea>
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
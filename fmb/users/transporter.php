<?php
include('header.php');
include('navbar.php');
$result = mysqli_query($link, "SELECT * FROM transporters order by `Name` ASC") or die(mysqli_error($link));
?>
<div class="content mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
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
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'add') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong><?php echo $_GET['Name']; ?></strong> is added
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong><?php echo $_GET['Name']; ?></strong> is edited
                                        successfully.
                                    </div>
                                <?php } ?>
                                <?php if (isset($_GET['action']) && $_GET['action'] == 'delete') { ?>
                                    <div class="alert alert-success" role="alert">
                                        <strong><?php echo $_GET['Name']; ?></strong> is deleted
                                        successfully.
                                    </div>
                                <?php } ?>
                                <div class="table-responsive">
                                    <table class="table table-striped display" width="100%">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Mobile No</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                                                <tr>
                                                    <td><?php echo $values['Name']; ?></td>
                                                    <td><?php echo $values['Mobile']; ?></td>
                                                    <td><button type="button" class="btn btn-light"
                                                        data-bs-target="#edittransporter-<?php echo $values['id']; ?>"
                                                        data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-pencil-square"></i></button> <button type="button"
                                                        class="btn btn-light"
                                                        data-bs-target="#deletetransporter-<?php echo $values['id']; ?>"
                                                        data-bs-toggle="modal" style="margin-bottom:5px"><i class="bi bi-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php }
                                            mysqli_free_result($result); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <?php $result = mysqli_query($link, "SELECT * FROM transporters order by `Name` ASC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="edittransporter-<?php echo $values['id']; ?>" tabindex="-1"
                                    aria-labelledby="edittransporter-<?php echo $values['id']; ?>-Label" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="edittransporter-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savetransporters.php" autocomplete="off">
                                                <input type="hidden" name="action" value="edit_transporter" />
                                                <input type="hidden" name="transporter_id" value="<?php echo $values['id']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Update Transporter</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3 row">
                                                        <label for="Name" class="col-4 control-label">Full Name</label>
                                                        <div class="col-8">
                                                            <input type="text" class="form-control" name="Name"
                                                                value="<?php echo $values['Name']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3 row">
                                                        <label for="Mobile" class="col-4 control-label">Mobile No</label>
                                                        <div class="col-8">
                                                            <input type="number" class="form-control" name="Mobile"
                                                            value="<?php echo $values['Mobile']; ?>" required>
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

                            <?php $result = mysqli_query($link, "SELECT * FROM transporters order by `Name` ASC") or die(mysqli_error($link));
                            while ($values = mysqli_fetch_assoc($result)) { ?>
                                <div class="modal fade" id="deletetransporter-<?php echo $values['id']; ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="deletetransporter-<?php echo $values['id']; ?>" class="form-horizontal"
                                                method="post" action="savetransporters.php" autocomplete="off">
                                                <input type="hidden" name="action" value="delete_transporter" />
                                                <input type="hidden" name="transporter_id" value="<?php echo $values['id']; ?>" />
                                                <input type="hidden" name="Name" value="<?php echo $values['Name']; ?>" />
                                                <div class="modal-header">
                                                    <h4 class="modal-title fs-5">Delete Transporter</h4>
                                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p> Are you sure you want to delete <strong><?php echo $values['Name']; ?></strong>
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

                            <div class="modal fade" id="addtransporter">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form id="addtransporter" class="form-horizontal" method="post" action="savetransporters.php" autocomplete="off">
                                            <input type="hidden" name="action" value="add_transporter" />
                                            <div class="modal-header">
                                                <h4 class="modal-title fs-5">Add Transporter</h4>
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
                                                        <input type="number" class="form-control" name="Mobile" required>
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
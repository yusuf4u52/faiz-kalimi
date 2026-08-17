<?php
include('../header.php');
include('../navbar.php');
require_once('../helpers.php');

$result = db_query(
    $link,
    "SELECT id, Thali, tiffinno, NAME, CONTACT, Active, Transporter, thalisize, extraRoti, yearly_hub, ITS_No,
            Email_ID, SEmail_ID, thalicount, Thali_start_date, Thali_stop_date, wingflat, society, Full_Address,
            musaid, Paid, (Previous_Due + yearly_hub - Paid) AS Total_Pending
     FROM thalilist WHERE thalisize = 'Barnamaj' AND hardstop != 1 ORDER BY tiffinno ASC"
);
$max_days = mysqli_fetch_row(db_query($link, "SELECT MAX(thalicount) AS max FROM thalilist"));
?>
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-6">
                <h2 class="mb-3">Barnamaj Thalis</h2>
            </div>
            <div class="col-6 text-end">
                <button type="button" class="btn btn-light mb-3" data-bs-target="#stopall"
                    data-bs-toggle="modal">Stop All</button>
            </div>
        </div>
        <?php if (($_GET['action'] ?? null) === 'delete') { ?>
            <div class="alert alert-danger" role="alert">
                All Barnamaj Thalis are stopped successfully.
            </div>
        <?php } ?>
        <?php
        // BUG FIX: this used to be `if (mysqli_num_rows($result) > 1): ...
        // endif;` wrapping everything including the "Stop All" modal and
        // the footer include — so with 0 or exactly 1 Friday thali, the
        // page rendered *nothing at all*: no table, no modal, no footer,
        // no closing HTML tags.
        if ($result->num_rows > 0) { ?>
            <div class="table-responsive">
                <table class="table table-striped display" width="100%">
                    <thead>
                        <tr>
                            <th>Tiffin No</th>
                            <th>Sabeel No</th>
                            <th>Thali Status</th>
                            <th>Name</th>
                            <th>Mobile No</th>
                            <th>Active</th>
                            <th>Transporter</th>
                            <th>Address</th>
                            <th>Thali Delivered</th>
                            <th>Current Hub</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($values = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo e($values['tiffinno']); ?></td>
                                <td><?php echo e($values['Thali']); ?></td>
                                <?php if ($values['Active'] == '1') { ?>
                                    <td><a href="#" onclick="stopThali_admin('<?php echo e($values['Thali']); ?>', '0')">Stop Thaali</a></td>
                                <?php } else { ?>
                                    <td><a href="#" onclick="stopThali_admin('<?php echo e($values['Thali']); ?>', '1')">Start Thaali</a></td>
                                <?php } ?>
                                <td><?php echo e($values['NAME']); ?></td>
                                <td><a href="tel:<?php echo e($values['CONTACT']); ?>"><?php echo e($values['CONTACT']); ?></a></td>
                                <td><?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></td>
                                <td><?php echo e($values['Transporter']); ?></td>
                                <td><?php echo e($values['wingflat'] . ', ' . $values['society']); ?></td>
                                <td><?php echo (($max_days[0] ?? 0) > 0) ? round($values['thalicount'] * 100 / $max_days[0]) . '%' : '0%'; ?> of days</td>
                                <td><?php echo e((string) $values['yearly_hub']); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php } else { ?>
            <h4 class="text-center mt-5">No Barnamaj thalis found.</h4>
        <?php } ?>
    </div>
</div>

<div class="modal fade" id="stopall">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="stopall" class="form-horizontal"
                method="post" action="stopall.php" autocomplete="off">
                <input type="hidden" name="action" value="stop_barnamaj" />
                <div class="modal-header">
                    <h4 class="modal-title">Stop Barnamaj Thali</h4>
                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to stop all Barnamaj Thalis ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No</button>
                    <button type="submit" class="btn btn-light">Yes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

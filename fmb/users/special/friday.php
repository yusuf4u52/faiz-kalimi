<?php 
include('../header.php');
include('../navbar.php');

$query = "SELECT id, Thali, tiffinno, NAME, CONTACT, Active, Transporter, thalisize, extraRoti, yearly_hub, ITS_No, Email_ID, SEmail_ID, thalicount, Thali_start_date, Thali_stop_date, Full_Address, musaid, Paid, (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending FROM thalilist WHERE thalisize LIKE 'Friday' ORDER BY tiffinno ASC";
$result = mysqli_query($link, $query);
$max_days = mysqli_fetch_row(mysqli_query($link, "SELECT MAX(thalicount) as max FROM thalilist"));
if (mysqli_num_rows($result) > 1): ?>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h2 class="mb-3">Friday Thalis</h2>
                </div>
                <div class="col-6 text-end">
                    <button type="button" class="btn btn-light mb-3" data-bs-target="#stopall"
                    data-bs-toggle="modal">Stop All</button>
                </div>
            </div>
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
                    <?php while($values = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo $values['tiffinno']; ?></td>
                            <td><?php echo $values['Thali']; ?></td>
                            <?php if ($values['Active'] == '1') { ?>
                                <td><a href="#" onclick="stopThali_admin('<?php echo $values['Thali']; ?>', '0')">Stop Thaali</a></td>
                            <?php } else { ?>
                                <td><a href="#" onclick="stopThali_admin('<?php echo $values['Thali']; ?>', '1')">Start Thaali</a></td>
                            <?php } ?>
                            <td><?php echo $values['NAME']; ?></td>
                            <td><a href="tel:<?php echo $values['CONTACT']; ?>"><?php echo $values['CONTACT']; ?></a></td>
                            <td><?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $values['Transporter']; ?></td>
                            <td><?php echo $values['Full_Address']; ?></td>
                            <td><?php echo round($values['thalicount'] * 100 / $max_days[0]); ?>% of days</td>
                            <td><?php echo $values['yearly_hub']; ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="modal fade" id="stopall">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="stopall" class="form-horizontal"
                method="post" action="stopall.php" autocomplete="off">
                <input type="hidden" name="action" value="stop_friday" />
                <div class="modal-header">
                    <h4 class="modal-title">Stop Friday Thali</h4>
                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to stop all Friday Thalis ?</p>
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
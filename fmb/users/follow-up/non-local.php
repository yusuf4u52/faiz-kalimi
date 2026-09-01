<?php
include('../header.php');
include('../navbar.php');
require_once('../helpers.php');
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <h2 class="mb-0">Non Local Current Year Pending Hoob</h2>
                    <form action="email.php" method="post" data-follow-up-email>
                        <input type="hidden" name="report" value="non-local">
                        <button type="submit" class="btn btn-light"><i class="bi bi-envelope me-1"></i>Email Members</button>
                    </form>
                </div>
                <?php if (!empty($_GET['status'])) { ?>
                    <div class="alert alert-info" role="alert"><?php echo e($_GET['status']); ?></div>
                <?php } ?>
                <?php $pendinghoob = db_query( $link, "SELECT *, (Previous_Due + yearly_hub - Paid) AS Total_Pending FROM thalilist WHERE (Previous_Due + yearly_hub - Paid) > 0 AND thalisize IS NOT NULL AND hardstop != 1 AND yearly_hub > 0 AND sabeelType NOT LIKE '%Kalimi ITS%' ORDER BY Total_Pending DESC");
                if ($pendinghoob->num_rows > 0) { ?>
                    <div class="table-responsive">
                        <table id="transporterlist" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">Sabeel No</th>
                                    <th scope="col">Thali No</th>
                                    <th scope="col">Contact</th>
                                    <th scope="col">Whatsapp</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Previous Hub</th>
                                    <th scope="col">Current Hub</th>
                                    <th scope="col">Pending</th>
                                    <th scope="col">Paid %</th>
                                    <th scope="col">Thali Size</th>
                                    <th scope="col">Sabeel Type</th>
                                    <th scope="col">Transporter</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($values = mysqli_fetch_assoc($pendinghoob)) { ?>
                                    <tr>
                                        <td><?php echo e($values['Thali']); ?></td>
                                        <td><?php echo e($values['tiffinno']); ?></td>
                                        <td><a href="tel:<?php echo e($values['CONTACT']); ?>">Call</a></td>
                                        <td><a href="https://wa.me/<?php echo e($values['WhatsApp']); ?>" target="_blank">Whatsapp</a></td>
                                        <td><?php echo e($values['NAME']); ?></td>
                                        <td><?php echo e((string) $values['previous_hub']); ?></td>
                                        <td><?php echo e((string) $values['yearly_hub']); ?></td>
                                        <td><strong><?php echo e((string) ($values['Total_Pending'] ?? '')); ?></strong></td>
                                        <td><?php echo e($values['Paid %']); ?>%</td>
                                        <td><?php echo e($values['thalisize']); ?></td>
                                        <td><?php echo e($values['sabeelType']); ?></td>
                                        <td><?php echo e($values['Transporter']); ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th scope="col">Sabeel No</th>
                                    <th scope="col">Thali No</th>
                                    <th scope="col">Contact</th>
                                    <th scope="col">Whatsapp</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Previous Hub</th>
                                    <th scope="col">Current Hub</th>
                                    <th scope="col">Pending</th>
                                    <th scope="col">Paid %</th>
                                    <th scope="col">Thali Size</th>
                                    <th scope="col">Sabeel Type</th>
                                    <th scope="col">Transporter</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php } else {
                    echo '<h4 class="text-center mt-5">No Previous Year Pending Hoob.</h4>';
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

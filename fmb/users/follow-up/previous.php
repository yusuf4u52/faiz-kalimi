<?php
include('../header.php');
include('../navbar.php');
require_once('../helpers.php');
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">Previous Year Pending Hoob</h2>
                <?php $pendinghoob = db_query($link, "SELECT * FROM thalilist WHERE Previous_Due > 4 AND thalisize IS NOT NULL AND hardstop != 1 ORDER BY Previous_Due DESC");
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
                                    <th scope="col">Previous Due</th>
                                    <th scope="col">Previous Hub</th>
                                    <th scope="col">Current Hub</th>
                                    <th scope="col">Pending</th>
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
                                        <td><?php echo e((string) $values['Previous_Due']); ?></td>
                                        <td><?php echo e((string) $values['previous_hub']); ?></td>
                                        <td><?php echo e((string) $values['yearly_hub']); ?></td>
                                        <td><strong><?php echo e((string) ($values['Total_Pending'] ?? '')); ?></strong></td>
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
                                    <th scope="col">Previous Due</th>
                                    <th scope="col">Previous Hub</th>
                                    <th scope="col">Current Hub</th>
                                    <th scope="col">Pending</th>
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

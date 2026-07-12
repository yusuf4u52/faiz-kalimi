<?php
include('header.php');
include('navbar.php');
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">Pending Hoob</h2>
                <?php $pendinghoob = mysqli_query($link, "SELECT * FROM thalilist WHERE Previous_Due > 4 AND thalisize is NOT NULL AND hardstop !=1 ORDER BY Previous_Due DESC");
                if ($pendinghoob->num_rows > 0) { ?>
                    <div class="table-responsive">
                        <table id="transporterlist" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">Sabeel No</th>
                                    <th scope="col">Thali No</th>
                                    <th scope="col">Contact</th>
                                    <th scope="col">Whatsapp</th>
                                    <th scope="col">Thali Size</th>
                                    <th scope="col">Sabeel Type</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Previous Due</th>
                                    <th scope="col">Previous Hub</th>
                                    <th scope="col">Current Hub</th>
                                    <th scope="col">Pending</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($values = mysqli_fetch_assoc($pendinghoob)) { ?>
                                    <tr>
                                        <td><?php echo $values['Thali']; ?></td>
                                        <td><?php echo $values['tiffinno']; ?></td>
                                        <td><?php echo $values['CONTACT']; ?></td>
                                        <td><?php echo $values['WhatsApp']; ?></td>
                                        <td><?php echo $values['thalisize']; ?></td>
                                        <td><?php echo $values['sabeelType'] ?></td>
                                        <td><?php echo $values['NAME']; ?></td>
                                        <td><?php echo $values['Previous_Due']; ?></td>
                                        <td><?php echo $values['previous_hub']; ?></td>
                                        <td><?php echo $values['yearly_hub']; ?></td>
                                        <td><?php echo $values['Total_Pending']; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Sr No.</th>
                                    <th>Transporter</th>
                                    <th>Tiffin No</th>
                                    <th>Tiffin Size</th>
                                    <th>Flat</th>
                                    <th>Society</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Sabeel No</th>
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

<?php include('footer.php'); ?>
<?php
include('header.php');
include('navbar.php');
?>

<div class="content mt-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <h2 class="mb-3">Active Thali</h2>
                                <?php $start_thali = mysqli_query($link, "SELECT * FROM thalilist WHERE Active = 1 AND Transporter != '' ORDER BY Transporter ASC");
                                if($start_thali->num_rows > 0) { ?>
                                    <div class="table-responsive">
                                        <table id="userfeedmenu" class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Transporter</th>
                                                    <th>Sabeel No</th>
                                                    <th>Tiffin No</th>
                                                    <th>Tiffin Size</th>
                                                    <th>Name</th>
                                                    <th>Address</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($start_list = mysqli_fetch_assoc($start_thali)) { ?>
                                                    <tr>
                                                        <td><?php echo $start_list['Transporter']; ?></td>
                                                        <td><?php echo $start_list['Thali']; ?></td>
                                                        <td><?php echo $start_list['tiffinno']; ?></td>
                                                        <td><?php echo $start_list['thalisize']; ?></td>
                                                        <td class="text-capitalize"><?php echo strtolower($start_list['NAME']); ?></td>
                                                        <td><?php echo $start_list['wingflat'] . ' ' . $start_list['society']; ?></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th>Transporter</th>
                                                    <th>Sabeel No</th>
                                                    <th>Tiffin No</th>
                                                    <th>Tiffin Size</th>
                                                    <th>Name</th>
                                                    <th>Address</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                <?php } else {
                                    echo '<h4 class="text-center mt-5">No thali is started on this date.</h4>';
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>
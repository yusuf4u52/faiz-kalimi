<?php
include('../header.php');
include('../navbar.php');
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">HardStop Thali</h2>
                <?php $hardstop_thali = mysqli_query($link, "SELECT * FROM thalilist WHERE hardstop = 1 ORDER BY id ASC");
                if ($hardstop_thali->num_rows > 0) { ?>
                    <div class="table-responsive">
                        <table id="userfeedmenu" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Sabeel No</th>
                                    <th>Tiffin No</th>
                                    <th>Tiffin Size</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Flat</th>
                                    <th>Society</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($hardstop_list = mysqli_fetch_assoc($hardstop_thali)) { ?>
                                    <tr>
                                        <td><?php echo $hardstop_list['Thali']; ?></td>
                                        <td><?php echo $hardstop_list['tiffinno']; ?></td>
                                        <td><?php echo $hardstop_list['thalisize']; ?></td>
                                        <td class="text-capitalize"><?php echo strtolower($hardstop_list['NAME']); ?></td>
                                        <td><a href="tel:<?php echo $hardstop_list['CONTACT']; ?>"><?php echo $hardstop_list['CONTACT']; ?></a></td>
                                        <td><?php echo $hardstop_list['wingflat']; ?></td>
                                        <td><?php echo $hardstop_list['society']; ?></td>
                                        <td><?php echo $hardstop_list['hardstop_comment']; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Sabeel No</th>
                                    <th>Tiffin No</th>
                                    <th>Tiffin Size</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Flat</th>
                                    <th>Society</th>
                                    <th>Comment</th>
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

<?php include('../footer.php'); ?>
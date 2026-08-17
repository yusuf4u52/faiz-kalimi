<?php
include('../header.php');
include('../navbar.php');
require_once('../helpers.php');

$start_thali = db_query(
    $link,
    "SELECT `Transporter`, `tiffinno`, `thalisize`, `wingflat`, `society`, `NAME`, `CONTACT`, `Thali`
     FROM thalilist WHERE Active = 1 AND hardstop != 1 AND Transporter != '' ORDER BY Transporter ASC"
);
$i = 0;
?>

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3">Active Thali</h2>
                <?php if ($start_thali->num_rows > 0) { ?>
                    <div class="table-responsive">
                        <table id="transporterlist" class="table table-striped table-hover">
                            <thead>
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
                            </thead>
                            <tbody>
                                <?php while ($start_list = mysqli_fetch_assoc($start_thali)) { ?>
                                    <tr>
                                        <td><?php echo ++$i; ?></td>
                                        <td><?php echo e($start_list['Transporter']); ?></td>
                                        <td><?php echo e($start_list['tiffinno']); ?></td>
                                        <td><?php echo e($start_list['thalisize']); ?></td>
                                        <td><?php echo e($start_list['wingflat']); ?></td>
                                        <td><?php echo e($start_list['society']); ?></td>
                                        <td class="text-capitalize"><?php echo e(strtolower($start_list['NAME'])); ?></td>
                                        <td><a href="tel:<?php echo e($start_list['CONTACT']); ?>"><?php echo e($start_list['CONTACT']); ?></a></td>
                                        <td><?php echo e($start_list['Thali']); ?></td>
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
                <?php
                    mysqli_free_result($start_thali);
                } else {
                    echo '<h4 class="text-center mt-5">No thali is started on this date.</h4>';
                } ?>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>

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
                                <h2 class="mb-3">User Start Thali</h2>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <form id="usermenu" class="form-horizontal" method="GET"
                                    action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                                    <div class="mb-3 row align-items-center">
                                        <label for="start_date" class="col-4 control-label">Start Date</label>
                                        <div class="col-4">
                                            <input type="date" class="form-control"
                                                min="<?php echo date('Y-m-d', strtotime('- 1 week')); ?>"
                                                name="start_date"
                                                value="<?php echo (!empty($_GET['start_date']) ? $_GET['start_date'] : ''); ?>"
                                                required>
                                        </div>
                                        <div class="col-4 col-md-3">
                                            <button class="btn btn-light me-2" type="submit"
                                                name="search">Search</button>
                                        </div>
                                    </div>
                                </form>
                                <?php if (isset($_GET['start_date'])) {
                                    $stop_date = date('Y-m-d', strtotime($_GET['start_date'] . "- 1 day"));
                                    $stop_thali = mysqli_query($link, "SELECT DISTINCT s.thali, t.tiffinno, t.thalisize, t.Transporter FROM stop_thali as s left join thalilist as t on (s.thali = t.Thali) WHERE s.stop_date = '" . $stop_date . "' AND t.hardstop != 1 ORDER BY t.Transporter");
                                    if($stop_thali->num_rows > 0) { ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover display">
                                                <thead>
                                                    <tr>
                                                        <th>Sabeel No</th>
                                                        <th>Tiffin No</th>
                                                        <th>Tiffin Size</th>
                                                        <th>Transporter</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($stop_list = mysqli_fetch_assoc($stop_thali)) {
                                                        $start_thali = mysqli_query($link, "SELECT DISTINCT `thali` FROM stop_thali WHERE `stop_date` = '" . $_GET['start_date'] . "' AND `thali` = '" . $stop_list['thali'] . "'");
                                                        if ($start_thali->num_rows <= 0) { ?>
                                                            <tr>
                                                                <td><?php echo $stop_list['thali']; ?></td>
                                                                <td><?php echo $stop_list['tiffinno']; ?></td>
                                                                <td><?php echo $stop_list['thalisize']; ?></td>
                                                                <td><?php echo $stop_list['Transporter']; ?></td>
                                                            </tr>
                                                        <?php }
                                                    } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } else {
                                        echo '<h4 class="text-center mt-5">No thali is started on this date.</h4>';
                                    }
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
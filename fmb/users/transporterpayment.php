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
                                <?php if (isset($_GET['payment_week'])) { 
                                    list($year, $week) = explode('-W', $_GET['payment_week']);
                                    $start_date = date('Y-m-d', strtotime($year . 'W' . $week));
                                    $end_date = date('Y-m-d', strtotime($year . 'W' . $week . '+6 days')); ?>
                                    <h2 class="mb-3">Transporter Payment from <?php echo $start_date; ?> to <?php echo $end_date; ?></h2>
                                <?php } else { ?>   
                                    <h2 class="mb-3">Transporter Payment Module</h2>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <form id="rotipayment" class="form-horizontal" method="GET"
                                    action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                                    <div class="mb-3 row">
                                        <label for="payment_week" class="col-3 control-label">Payment Week</label>
                                        <div class="col-6">
                                            <input type="week" class="form-control" name="payment_week" id="payment_week" placeholder="Payment Week" value="<?php echo (!empty($_GET['payment_week']) ? $_GET['payment_week'] : ''); ?>">
                                        </div>
                                        <div class="col-3 col-md-3">
                                            <button class="btn btn-light" type="submit" name="search">Search</button>
                                        </div>
                                    </div>
                                </form>
                                <?php if (isset($_GET['payment_week'])) {
                                    list($year, $week) = explode('-W', $_GET['payment_week']);
                                    $start_date = date('Y-m-d', strtotime($year . 'W' . $week));
                                    $end_date = date('Y-m-d', strtotime($year . 'W' . $week . '+6 days'));
                                    $thali_count = mysqli_query($link, "SELECT `transporter_id`, SUM(`thali_count`) as total_thali_count FROM transporter_thali_count WHERE `count_date` BETWEEN '" . $start_date . "' AND '" . $end_date . "' GROUP BY `transporter_id`");
                                    if ($thali_count->num_rows > 0) { ?>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-striped table-hover display">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th style="text-align:left;">Mobile No</th>
                                                        <th>Thali Count</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = mysqli_fetch_assoc($thali_count)) {
                                                        $transporter_id = $row['transporter_id'];
                                                        $total_thali_count = $row['total_thali_count'];
                                                        $transporter = mysqli_query($link, "SELECT `Name`, `Mobile` FROM transporters WHERE `id` = '" . $transporter_id . "' LIMIT 1");
                                                        $transporter = mysqli_fetch_assoc($transporter);
                                                        ?>
                                                        <tr>
                                                            <td><?php echo $transporter['Name']; ?></td>
                                                            <td style="text-align:left;"><?php echo $transporter['Mobile']; ?></td>
                                                            <td><?php echo $total_thali_count; ?> Thali</td>
                                                        </tr>
                                                    <?php } ?>  
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php } else { ?>
                                        <div class="alert alert-danger" role="alert">
                                            No data found for the selected date range.
                                        </div>
                                    <?php }
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

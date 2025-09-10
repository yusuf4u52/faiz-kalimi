<?php
include('../header.php');
include('../navbar.php');
?>

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
                    $thali_count = mysqli_query($link, "SELECT `name`, SUM(`mini`) as total_mini, SUM(`small`) as total_small, SUM(`medium`) as total_medium, SUM(`large`) as total_large, SUM(`friday`) as total_friday, SUM(`barnamaj`) as total_barnamaj, SUM(`count`) as total_count FROM transporter_daily_count WHERE `date` BETWEEN '" . $start_date . "' AND '" . $end_date . "' GROUP BY `name`");
                    if ($thali_count->num_rows > 0) { ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-striped table-hover display">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Mini</th>
                                        <th>Small</th>
                                        <th>Medium</th>
                                        <th>Large</th>
                                        <th>Friday</th>
                                        <th>Barnamaj</th>
                                        <th>Thali</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($thali_count)) { ?>
                                        <tr>
                                            <td data-order="ASC"><?php echo $row['name']; ?></td>
                                            <td><?php echo $row['total_mini']; ?></td>
                                            <td><?php echo $row['total_small']; ?></td>
                                            <td><?php echo $row['total_medium']; ?></td>
                                            <td><?php echo $row['total_large']; ?></td>
                                            <td><?php echo $row['total_friday']; ?></td>
                                            <td><?php echo $row['total_barnamaj']; ?></td>
                                            <td><?php echo $row['total_count']; ?></td>
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

<?php include('../footer.php'); ?>

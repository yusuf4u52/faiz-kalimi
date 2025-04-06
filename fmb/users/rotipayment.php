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
                                <h2 class="mb-3">Payment</h2>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <form id="rotipayment" class="form-horizontal" method="GET"
                                    action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                                    <div class="mb-3 row">
                                        <label for="payment_week" class="col-3 control-label">Payment Week</label>
                                        <div class="col-6">
                                            <div class="input-group input-daterange mb-3">
                                                <input type="text" class="form-control" name="start_date" id="start_date" placeholder="Start Date" value="<?php echo (!empty($_GET['start_date']) ? $_GET['start_date'] : ''); ?>">
                                                <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                                <input type="text" class="form-control" name="end_date" id="end_date" placeholder="End Date" value="<?php echo (!empty($_GET['end_date']) ? $_GET['end_date'] : ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-3 col-md-3">
                                            <button class="btn btn-light" type="submit" name="search">Search</button>
                                        </div>
                                    </div>
                                </form>
                                <?php if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
                                    $roti_recieved = mysqli_query($link, "SELECT `maker_id`, SUM(`roti_recieved`) as total_roti_recieved FROM fmb_roti_recieved WHERE `recieved_date` BETWEEN '" . date('Y-m-d', strtotime($_GET['start_date'])) . "' AND '" . date('Y-m-d', strtotime($_GET['end_date'])) . "' GROUP BY `maker_id`");
                                    if ($roti_recieved->num_rows > 0) { ?>
                                        <div class="table-responsive mb-3">
                                            <table class="table table-striped table-hover display">
                                                <thead>
                                                    <tr>
                                                        <th>Roti Maker</th>
                                                        <th>Roti Recieved</th>
                                                        <th>Payment</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = mysqli_fetch_assoc($roti_recieved)) {
                                                        $maker_id = $row['maker_id'];
                                                        $total_roti_recieved = $row['total_roti_recieved'];
                                                        $total_payment = $row['total_roti_recieved'] * 2.5;
                                                        $roti_maker = mysqli_query($link, "SELECT `full_name` FROM fmb_roti_maker WHERE `id` = '" . $maker_id . "' LIMIT 1");
                                                        if ($roti_maker->num_rows > 0) {
                                                            $roti_maker_name = mysqli_fetch_assoc($roti_maker);
                                                        } else {
                                                            $roti_maker_name = "Unknown Maker";
                                                        } ?>
                                                        <tr>
                                                            <td><?php echo $roti_maker_name['full_name']; ?></td>
                                                            <td><?php echo $total_roti_recieved; ?> Roti</td>
                                                            <td>₹ <?php echo $total_payment; ?></td>
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

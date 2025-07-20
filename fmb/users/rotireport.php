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
                                    <h2 class="mb-3">Roti Report from <strong><?php echo date('d M Y', strtotime($start_date) ); ?></strong> to <strong><?php echo date('d M Y', strtotime($end_date) ); ?></strong></h2>
                                <?php } else { ?>   
                                    <h2 class="mb-3">Roti Report</h2>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <form id="rotipayment" class="form-horizontal my-3" method="GET"
                                    action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                                    <div class="mb-3 row">
                                        <label for="payment_week" class="col-3 control-label">Payment Week</label>
                                        <div class="col-6">
                                            <input type="week" class="form-control" name="payment_week" id="payment_week" placeholder="Payment Week" value="<?php echo (!empty($_GET['payment_week']) ? $_GET['payment_week'] : ''); ?>">
                                        </div>
                                        <div class="col-3">
                                            <button class="btn btn-light" type="submit" name="search">Search</button>
                                        </div>
                                    </div>
                                </form>
                                <?php if (isset($_GET['payment_week'])) {
                                    list($year, $week) = explode('-W', $_GET['payment_week']);
                                    $start_date = date('Y-m-d', strtotime($year . 'W' . $week));
                                    $end_date = date('Y-m-d', strtotime($year . 'W' . $week . '+5 days'));
                                    $roti_recieved = mysqli_query($link, "SELECT `maker_id`, SUM(`roti_recieved`) as total_roti_recieved FROM fmb_roti_recieved WHERE `recieved_date` BETWEEN '" . $start_date . "' AND '" . $end_date . "' AND `roti_status` = 'recieved' GROUP BY `maker_id`");
                                    if ($roti_recieved->num_rows > 0) { ?>
                                        <div class="table-responsive mb-3">
                                            <table id="rotireport" class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Full Name</th>
                                                        <th>Stock Distributed</th>
                                                        <th>Roti Recieved</th>
                                                        <th>Stock Left</th>
                                                        <th>Payment</th>
                                                        <th>Bank Details</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($row = mysqli_fetch_assoc($roti_recieved)) {
                                                        $maker_id = $row['maker_id'];
                                                        $total_roti_recieved = $row['total_roti_recieved'];
                                                        $total_payment = $row['total_roti_recieved'] * 2.5;
                                                        $roti_maker = mysqli_query($link, "SELECT `full_name`, `code`, `bank_details` FROM fmb_roti_maker WHERE `id` = '" . $maker_id . "' LIMIT 1");
                                                        if ($roti_maker->num_rows > 0) {
                                                            $maker = mysqli_fetch_assoc($roti_maker);
                                                            $bank_details = htmlspecialchars( $maker['bank_details'] );
                                                            $paragraphs = explode( "\n", $bank_details );
                                                        } else {
                                                            $maker = "Unknown Maker";
                                                        } 
                                                        $adistribution = mysqli_query($link, "SELECT * FROM fmb_roti_distribution WHERE `distribution_date` < '".$end_date."' AND `maker_id` = '" . $maker_id . "' order by `distribution_date` DESC LIMIT 1") or die(mysqli_error($link));
                                                        if ($adistribution->num_rows > 0) {
                                                            $row_adistribution = $adistribution->fetch_assoc();
                                                            $flour_per_roti = 0.025;
                                                            $oil_per_roti = 0.0025;
                                                            $flour_required = $total_roti_recieved * $flour_per_roti;
                                                            $oil_required = $total_roti_recieved * $oil_per_roti;
                                                            $flour_distributed = $row_adistribution['flour_distributed'] + $row_adistribution['flour_left'];
                                                            $oil_distributed = $row_adistribution['oil_distributed'] + $row_adistribution['oil_left'];
                                                            $flour_left = $flour_distributed - $flour_required;
                                                            $oil_left = $oil_distributed - $oil_required;
                                                        } ?>
                                                        <tr>
                                                            <td><?php echo $maker['code']; ?></td>
                                                            <td><?php echo $maker['full_name']; ?></td>
                                                            <td><strong>Flour:</strong> <?php echo $flour_distributed; ?> KG <br/> <strong>Oil:</strong> <?php echo $oil_distributed; ?> Ltr</td>
                                                            <td><?php echo $total_roti_recieved; ?> Roti</td>
                                                            <td><strong>Flour:</strong> <?php echo $flour_left; ?> KG <br/> <strong>Oil:</strong> <?php echo $oil_left; ?> Ltr</td>
                                                            <td>₹ <?php echo $total_payment; ?></td>
                                                            <td><?php foreach( $paragraphs as $para ) {
                                                                echo '<p class="mb-1">'.$para.'</p>';
                                                            } ?></td>
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

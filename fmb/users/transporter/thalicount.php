<?php
include('../header.php');
include('../navbar.php');
include('getHijriDate.php');

$result = mysqli_query($link, "SELECT * FROM transporter_daily_count order by `date` DESC") or die(mysqli_error($link));
?>

<div class="card">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-12">
                <h2 class="mb-3">Transporter Thali Count</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="table-responsive">
                    <table id="roti" class="table table-striped" width="100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Full Name</th>
                                <th>Mini</th>
                                <th>Small</th>
                                <th>Medium</th>
                                <th>Large</th>
                                <th>Friday</th>
                                <th>Barnamaj</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($values = mysqli_fetch_assoc($result)) {
                                $hijridate = getHijriDate($values['date']);
                                $day = date('l', strtotime($values['date'])); ?>
                                <tr>
                                    <td data-sort="<?php echo strtotime($values['date']); ?>"><?php echo date('d M Y', strtotime($values['date'])) .' - '.$hijridate . ' (' . $day . ')'; ?></td>
                                    <td><?php echo $values['name']; ?></td>
                                    <td><?php echo $values['mini']; ?></td>
                                    <td><?php echo $values['small']; ?></td>
                                    <td><?php echo $values['medium']; ?></td>
                                    <td><?php echo $values['large']; ?></td>
                                    <td><?php echo $values['friday']; ?></td>
                                    <td><?php echo $values['barnamaj']; ?></td>
                                    <td><?php echo $values['count']; ?></td>
                                </tr>
                            <?php }
                            mysqli_free_result($result); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../footer.php'); ?>
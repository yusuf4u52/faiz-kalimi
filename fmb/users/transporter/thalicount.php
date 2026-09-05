<?php
include('../header.php');
include('../navbar.php');
require_once('../helpers.php');
include('../getHijriDate.php');

$result = db_query($link, "SELECT * FROM transporter_daily_count ORDER BY `date` DESC");
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
                    <table id="thalicount" class="table table-striped" width="100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Full Name</th>
                                <th>Mini</th>
                                <th>Small</th>
                                <th>Medium</th>
                                <th>Large</th>
                                <th>Friday</th>
                                <th>Roti</th>
                                <th>Barnamaj</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($values = mysqli_fetch_assoc($result)) {
                                $hijridate = getHijriDate($values['date']);
                                $day = date('l', strtotime($values['date'])); ?>
                                <tr>
                                    <td data-sort="<?php echo strtotime($values['date']); ?>"><?php echo e(date('d M Y', strtotime($values['date'])) . ' - ' . $hijridate . ' (' . $day . ')'); ?></td>
                                    <td><?php echo e($values['name']); ?></td>
                                    <td><?php echo (int) $values['mini']; ?></td>
                                    <td><?php echo (int) $values['small']; ?></td>
                                    <td><?php echo (int) $values['medium']; ?></td>
                                    <td><?php echo (int) $values['large']; ?></td>
                                    <td><?php echo (int) $values['friday']; ?></td>
                                    <td><?php echo (int) $values['roti']; ?></td>
                                    <td><?php echo (int) $values['barnamaj']; ?></td>
                                    <td><?php echo (int) $values['count']; ?></td>
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

<?php
include('header.php');
include('navbar.php');
?>

<div class="card">
    <div class="card-body">
        <?php if (!empty($values['yearly_hub'])) { 
            if (!empty($_SESSION['thali'])) { ?>
                <div class="row">
                    <div class="col-6">
                        <h2 class="mb-5">Stop Dates</h2>
                    </div>
                    <div class="col-6 text-end">
                        <?php if ($values['hardstop'] == 1) { ?>
                            <h4>Your thali is currently stopped: <?php echo $values['hardstop_comment']; ?></h4>
                        <?php } else { ?>
                            <button type="button" class="btn btn-light" data-bs-target="#stop_thali"
                                data-bs-toggle="modal">Stop
                                Thali</button>
                        <?php } ?>
                    </div>
                </div>

                <?php if (isset($_GET['action']) && $_GET['action'] == 'srange') { ?>
                    <div class="alert alert-success" role="alert">Your thali is stopped from <strong>
                            <?php echo date('d M Y', strtotime($_GET['sdate'])); ?>
                        </strong> to <strong>
                            <?php echo date('d M Y', strtotime($_GET['edate'])); ?>
                        </strong>. Click <a href="/fmb/users/stop_dates.php">here</a> to view stopped dates.</div>
                <?php }
                if (isset($_GET['action']) && $_GET['action'] == 'srsvp') { ?>
                    <div class="alert alert-warning" role="alert">RSVP ended to stop thali of <strong>
                            <?php echo date('d M Y', strtotime($_GET['sdate'])); ?>
                        </strong>.</div>
                <?php }
                if (isset($_GET['action']) && $_GET['action'] == 'start') { ?>
                    <div class="alert alert-success" role="alert">Your stop thali dates from <strong>
                            <?php echo date('d M Y', strtotime($_GET['sdate'])); ?>
                        </strong> to <strong>
                            <?php echo date('d M Y', strtotime($_GET['edate'])); ?>
                        </strong> is deleted successfully.</div>
                <?php } ?>

                <div class="modal fade" id="stop_thali">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="user_stop" class="form-horizontal" method="post" action="stopthali.php" autocomplete="off">
                                <input type="hidden" name="action" value="stop_date_thali" />
                                <input type="hidden" id="thali" name="thali"
                                    value="<?php echo $_SESSION['thali']; ?>" />
                                <div class="modal-header">
                                    <h4 class="modal-title">Stop Thali</h4>
                                    <button type="button" class="btn ms-auto" data-bs-dismiss="modal"
                                        aria-label="Close"><i class="bi bi-x-lg"></i></button>
                                </div>
                                <div class="modal-body">
                                    <div class="input-group input-daterange mb-3">
                                        <input type="text" class="form-control" name="start_date"
                                            id="start_date" placeholder="Start Date">
                                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                                        <input type="text" class="form-control" name="end_date" id="end_date"
                                            placeholder="End Date">
                                    </div>
                                    <p class="text-danger mb-0"><strong>Note:</strong> RSVP will end at 5 PM one day before start date.<p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-light">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php
                date_default_timezone_set('Asia/Kolkata');
                $stop_dates = mysqli_query($link, "WITH ranked_dates AS (
                    SELECT `id`, `thali`, `stop_date`, ROW_NUMBER() OVER (PARTITION BY `thali` ORDER BY `stop_date`) AS row_num FROM `stop_thali` where `Thali` = '" . $_SESSION['thali'] . "'
                ),
                grouped_dates AS (
                    SELECT `id`, `thali`, `stop_date`, DATE_SUB(`stop_date`, INTERVAL row_num DAY) AS group_key FROM ranked_dates
                )
                SELECT `id`, `thali`, MIN(`stop_date`) AS start_date, MAX(`stop_date`) AS end_date FROM grouped_dates GROUP BY `thali`, group_key ORDER BY start_date DESC;") or die(mysqli_error($link));
                if (isset($stop_dates) && $stop_dates->num_rows > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-striped display" width="100%">
                            <thead>
                                <tr>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($values = mysqli_fetch_assoc($stop_dates)) {
                                    $stop_date = new DateTime($values['start_date'] . '17:00:00');
                                    $stop_date->modify('-1 day');
                                    $stop_date = $stop_date->format('Y-m-d H:i:s'); ?>
                                    <tr>
                                        <td data-sort="<?php echo strtotime($values['start_date']); ?>"><?php echo date('d M Y', strtotime($values['start_date'])); ?></td>
                                        <td data-sort="<?php echo strtotime($values['end_date']); ?>"><?php echo date('d M Y', strtotime($values['end_date'])); ?></td>
                                        <td><?php if (date('Y-m-d H:i:s') < $stop_date) { ?><button type="button"
                                                    class="btn btn-light"
                                                    data-bs-target="#startthali-<?php echo $values['id']; ?>"
                                                    data-bs-toggle="modal" style="margin-bottom:5px">Delete</button><?php } else { ?> <button type="button"
                                                    class="btn btn-light" disabled>RSVP Ended</button> <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                <?php } else {
                    echo '<h5 class="text-center mb-3">Currently you has no stop dates.</h5>';
                } mysqli_free_result($stop_dates);
            } else { ?>
                <h5 class="mb-3">Sabeel no is not assigned yet. Please contact Moiz Bhai Mulla at <a href="https://api.whatsapp.com/send?phone=+919096778753">9096778753</a> to view this page.</h5>
            <?php }
        } else { ?>
            <h5 class=" text-center mb-3">You dont see anything here probably because you are not taking barakat of thali
                or
                dont have a transporter assigned yet.</h5>
        <?php } ?>
    </div>
</div>

<?php
$stop_dates = mysqli_query($link, "WITH ranked_dates AS (
    SELECT `id`, `thali`, `stop_date`, ROW_NUMBER() OVER (PARTITION BY `thali` ORDER BY `stop_date`) AS row_num FROM `stop_thali` where `Thali` = '" . $_SESSION['thali'] . "'
),
grouped_dates AS (
    SELECT `id`, `thali`, `stop_date`, DATE_SUB(`stop_date`, INTERVAL row_num DAY) AS group_key FROM ranked_dates
)
SELECT `id`, `thali`, MIN(`stop_date`) AS start_date, MAX(`stop_date`) AS end_date FROM grouped_dates GROUP BY `thali`, group_key ORDER BY start_date DESC;") or die(mysqli_error($link));
if (isset($stop_dates) && $stop_dates->num_rows > 0) {
    while ($values = mysqli_fetch_assoc($stop_dates)) { ?>
        <div class="modal fade" id="startthali-<?php echo $values['id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="startthali-<?php echo $values['id']; ?>" class="form-horizontal" method="post"
                        action="stopthali.php" autocomplete="off">
                        <input type="hidden" name="action" value="start_thali" />
                        <input type="hidden" name="thali" value="<?php echo $values['thali']; ?>" />
                        <input type="hidden" name="start_date" value="<?php echo $values['start_date']; ?>" />
                        <input type="hidden" name="end_date" value="<?php echo $values['end_date']; ?>" />
                        <div class="modal-header">
                            <h4 class="modal-title">Delete Stop Thali Dates</h4>
                            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                                    class="bi bi-x-lg"></i></button>
                        </div>
                        <div class="modal-body">
                            <p> Are you sure you want to delete the stop dates?
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-light">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php }
} mysqli_free_result($stop_dates); ?>

<?php include('footer.php'); ?>
<?php
include ('connection.php');
include ('_authCheck.php');

if (isset($_POST['fromLogin'])) {
  $_SESSION['fromLogin'] = $_POST['fromLogin'];
  $_SESSION['thaliid'] = $_POST['thaliid'];
  $_SESSION['thali'] = $_POST['thali'];
}

if (is_null($_SESSION['fromLogin'])) {
  header("Location: login.php");
}

?>

<html>

<head>
  <?php include ('_head.php'); ?>
</head>

<body>

  <?php include ('_nav.php'); ?>
  <div class="container">
    <div class="row">
      <div class="col-lg-12">
          <div class="page-header">
              <h2 id="forms">Stop Thali</h2>
          </div>
      </div>
    </div>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'add') { ?>
      <div class="alert alert-warning" role="alert">Your Thali from <strong>
          <?php echo date('d M Y', strtotime($_GET['from'])); ?> to <?php echo date('d M Y', strtotime($_GET['to'])); ?>
        </strong> is successfully stopped.</div>
    <?php } ?>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'nochange') { ?>
      <div class="alert alert-warning" role="alert">No change found for Thali of <strong>
          <?php echo date('d M Y', strtotime($_GET['date'])); ?>
        </strong> or you have revert to the original quantity.</div>
    <?php } ?>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'rsvp') { ?>
      <div class="alert alert-danger" role="alert">You can't edit the thali now because RSVP time for editing Thali of
        <strong>
          <?php echo date('d M Y', strtotime($_GET['date'])); ?>
        </strong> is finished.</div>
    <?php } ?>
    <form id="changemenu" class="form-horizontal" method="post" action="thalistatus.php">
      <input type="hidden" name="action" value="add_stop" />
      <input type="hidden" id="thaliid" name="thaliid" value="<?php echo $_SESSION['thaliid']; ?>" />
      <input type="hidden" id="thali" name="thali" value="<?php echo $_SESSION['thali']; ?>" />
      <div class="form-group row">
        <label for="from_date" class="col-xs-4 control-label">From Date</label>
        <div class="col-xs-8">
        <div class="input-group">
          <input type="text" class="form-control" name="from_date" id="from_date" readonly required>
          <span class="input-group-btn">
              <button class="btn btn-primary btn-plus" type="button">&nbsp;<i class="fas fa-calendar-alt"></i>&nbsp;</button>
            </span>
          </div>
        </div>
      </div>
      <div class="form-group row">
        <label for="to_date" class="col-xs-4 control-label">To Date</label>
        <div class="col-xs-8">
          <div class="input-group">
            <input type="text" class="form-control" name="to_date" id="to_date" readonly required>
            <span class="input-group-btn">
              <button class="btn btn-primary btn-plus" type="button">&nbsp;<i class="fas fa-calendar-alt"></i>&nbsp;</button>
            </span>
          </div>
        </div>
      </div>
      <div class="form-group row">
        <div class="col-xs-12 text-center">
          <button type="submit" class="btn btn-primary">Submit Request</button>
        </div>
      </div>
    </form>

    <?php $result = mysqli_query($link, "SELECT * FROM stop_thali WHERE `thali` = '".$_SESSION['thali']."' AND `to_date` > '".date('Y-m-d')."' order by `to_date` ASC") or die(mysqli_error($link));
    if($result->num_rows > 0) { ?>
      <div class="page-header">
          <h2 id="forms">Stop Thali List</h2>
      </div>
      <table class="table table-striped table-hover" id="my-table">
        <thead>
            <tr>
                <th>From Date</th>
                <th>To Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
          <?php while ($values = mysqli_fetch_assoc($result)) { ?>
            <tr>
              <td><?php echo date('d M Y', strtotime($values['from_date'])); ?></td>
              <td><?php echo date('d M Y', strtotime($values['to_date'])); ?></td>
              <td><button type="button" class="btn btn-danger" data-target="#deletestop-<?php echo $values['id']; ?>" data-toggle="modal"><i class="fas fa-trash"></i></button></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    <?php } mysqli_free_result($result); ?>

    <?php $result = mysqli_query($link, "SELECT * FROM stop_thali WHERE `thali` = '".$_SESSION['thali']."' AND `to_date` > '".date('Y-m-d')."' order by `to_date` ASC") or die(mysqli_error($link));
    while ($values = mysqli_fetch_assoc($result)) { ?>
        <div class="modal" id="deletestop-<?php echo $values['id']; ?>">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="deletefood-<?php echo $values['id']; ?>" class="form-horizontal" method="post" action="thalistatus.php">
                        <input type="hidden" name="action" value="delete_stop" />
                        <input type="hidden" name="from_date" value="<?php echo $values['from_date']; ?>" />
                        <input type="hidden" name="to_date" value="<?php echo $values['to_date']; ?>" />
                        <input type="hidden" name="stop_id" value="<?php echo $values['id']; ?>" />
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title">Delete Stop Thali</h4>
                        </div>
                        <div class="modal-body">
                            <p> Are you sure you want to delete stop thali from <strong><?php echo date('d M Y', strtotime($values['from_date'])); ?> to <?php echo date('d M Y', strtotime($values['to_date'])); ?></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php }
    mysqli_free_result($result); ?>

    <?php include ('_bottomJS.php'); ?>
    <script type="text/javascript">
        $('#my-table').dynatable();
    </script>

    <div class="text-center">
      <a href="mailto:kalimimohallapoona@gmail.com">kalimimohallapoona@gmail.com</a><br><br>
    </div>
</body>

</html>
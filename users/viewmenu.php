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

$takesFmb = mysqli_query($link, "SELECT * FROM thalilist where `Thali` = '" . $_SESSION['thali'] . "' AND `hardstop` != 1 AND Active != 0") or die(mysqli_error($link));

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
          <h2 id="forms">View Menu</h2>
        </div>
      </div>
    </div>
    <?php if (isset($takesFmb) && $takesFmb->num_rows > 0) {
      $takesFmb = $takesFmb->fetch_assoc();
      $thalisize = $takesFmb['thalisize'];
      $result = mysqli_query($link, "SELECT * FROM menu_list order by `menu_date` DESC") or die(mysqli_error($link));

      if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
        <div class="alert alert-success" role="alert">Thali of <strong>
            <?php echo date('d M Y', strtotime($_GET['date'])); ?>
          </strong> is edited successfully.</div>
      <?php } ?>
      <?php if (isset($_GET['action']) && $_GET['action'] == 'nochange') { ?>
        <div class="alert alert-warning" role="alert">No change found for Thali of <strong>
            <?php echo date('d M Y', strtotime($_GET['date'])); ?>
          </strong>.</div>
      <?php } ?>
      <?php if (isset($_GET['action']) && $_GET['action'] == 'rsvp') { ?>
        <div class="alert alert-danger" role="alert">You can't edit the thali now because RSVP time for editing Thali of
          <strong>
            <?php echo date('d M Y', strtotime($_GET['date'])); ?>
          </strong> is finished.
        </div>
      <?php } ?>

      <?php $sched_res = [];
      while ($values = mysqli_fetch_assoc($result)) {
        $stop_thali = mysqli_query($link, "SELECT * FROM stop_thali WHERE `thali` = '".$_SESSION['thali']."' AND '".$values['menu_date']."' BETWEEN `from_date` AND `to_date`") or die(mysqli_error($link));
        if ($stop_thali->num_rows == 0) {
          $user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $values['menu_date'] . "' AND `thali` = '" . $_SESSION['thali'] . "'") or die(mysqli_error($link));
          if ($user_menu->num_rows > 0) {
            $row = $user_menu->fetch_assoc();
            $values['menu_item'] = unserialize($row['menu_item']);
            $values['sdate'] = date("F d, Y h:i A", strtotime($row['menu_date']));
          } else {
            $values['menu_item'] = unserialize($values['menu_item']);
            $values['sdate'] = date("F d, Y h:i A", strtotime($values['menu_date']));
          }
          $values['thalisize'] = $thalisize;
          $sched_res[$values['id']] = $values;
        } else {
          $values['menu_type'] = 'stop_thali';
          $values['menu_item'] = 'Stopped Thali';
          $values['sdate'] = date("F d, Y h:i A", strtotime($values['menu_date']));
          $values['thalisize'] = $thalisize;
          $sched_res[$values['id']] = $values;
        }
      }
      $sched_res = json_encode($sched_res); ?>

      <div id="calendar"></div>

      <div class="modal" id="editmenu">
        <div class="modal-dialog">
          <div class="modal-content">
            <form id="changemenu" class="form-horizontal" method="post" action="changemenu.php">
              <input type="hidden" name="action" value="change_menu" />
              <input type="hidden" id="menu_id" name="menu_id" value="" />
              <input type="hidden" id="thali" name="thali" value="<?php echo $_SESSION['thali']; ?>" />
              <input type="hidden" id="thalisize" name="thalisize" value="<?php echo $thalisize; ?>" />
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title"></h4>
              </div>
              <div class="modal-body">
                <div id="miqaat" class="row text-center" style="display:none;"></div>
                <div id="sabji" class="form-group row" style="display:none;">
                  <label for="sabji" class="col-xs-6 control-label" id="sabji"></label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[sabji][item]" id="sabji" value="">
                    <div class="input-group">
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-minus" type="button">-</button>
                      </span>
                      <input type="number" class="form-control" name="menu_item[sabji][qty]" id="sabjiqty" value="" min="0" readonly>
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-plus disabled" type="button">+</button>
                      </span>
                    </div>
                    <small class="text-info"><strong>Note:</strong> 0.5 means half dabba.</small>
                  </div>
                </div>
                <div id="tarkari" class="form-group row" style="display:none;">
                  <label for="tarkari" class="col-xs-6 control-label" id="tarkari">Tarkari/Dal Item</label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[tarkari][item]" id="tarkari" value="">
                    <div class="input-group">
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-minus" type="button">-</button>
                      </span>
                      <input type="number" class="form-control" name="menu_item[tarkari][qty]" id="tarkariqty" value="" min="0" readonly>
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-plus disabled" type="button">+</button>
                      </span>
                    </div>
                    <small class="text-info"><strong>Note:</strong> 0.5 means half dabba.</small>
                  </div>
                </div>
                <div id="rice" class="form-group row" style="display:none;">
                  <label for="rice" class="col-xs-6 control-label" id="rice">Rice Item</label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[rice][item]" id="rice" value="">
                    <div class="input-group">
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-minus" type="button">-</button>
                      </span>
                      <input type="number" class="form-control" name="menu_item[rice][qty]" id="riceqty" value="" min="0" readonly>
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-plus disabled" type="button">+</button>
                      </span>
                    </div>
                    <small class="text-info"><strong>Note:</strong> 0.5 means half dabba.</small>
                  </div>
                </div>
                <div id="roti" class="form-group row" style="display:none;">
                  <label for="roti" class="col-xs-6 control-label" id="roti">Roti/Bread Item</label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[roti][item]" id="roti" value="">
                    <input type="text" class="form-control" name="menu_item[roti][qty]" id="rotiqty" value="" readonly>
                    <small class="text-info">Please contact admin to change quantity.</small>
                  </div>
                </div>
                <div id="extra" class="form-group row" style="display:none;">
                  <label for="extra" class="col-xs-6 control-label" id="extra">Extra Item</label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[extra][item]" id="extra" value="">
                    <input type="text" class="form-control" name="menu_item[extra][qty]" id="extraqty" value="" readonly>
                    <small class="text-info">Please contact admin to change quantity.</small>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary edit-menu hidden">Save Changes</button>
                <button type="submit" class="btn btn-primary rsvp-end hidden" disabled>RSVP Ended</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <script>
        var scheds = JSON.parse('<?php echo $sched_res; ?>');
      </script>
    <?php } else {
      echo '<h3>You are not allowed to view menu as your thali is stopped.</h3>';
    } ?>

    <?php include ('_bottomJS.php'); ?>
</body>

</html>
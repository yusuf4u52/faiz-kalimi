<?php
include('connection.php');
include('_authCheck.php');


if (isset($_POST['fromLogin'])) {
  $_SESSION['fromLogin'] = $_POST['fromLogin'];
  $_SESSION['thaliid'] = $_POST['thaliid'];
  $_SESSION['thali'] = $_POST['thali'];
}

if (is_null($_SESSION['fromLogin'])) {
  header("Location: login.php");
}

$thalisize = mysqli_query($link, "SELECT `thalisize` FROM thalilist where `id` = '" . $_SESSION['thaliid'] . "'") or die(mysqli_error($link));
if(isset($thalisize) && $thalisize->num_rows > 0) {
  $thalisize = $thalisize->fetch_column();
}

$result = mysqli_query($link, "SELECT * FROM menu_list order by `menu_date` DESC") or die(mysqli_error($link));

?>

<html>

<head>
  <?php include('_head.php'); ?>
</head>

<body>

  <?php include('_nav.php'); ?>
  <div class="container">
    <?php $sched_res = [];
    while ($values = mysqli_fetch_assoc($result)) {
      $user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '".$values['menu_date']."' AND `thali` = '".$_SESSION['thali']."'") or die(mysqli_error($link));
      if($user_menu->num_rows > 0) {
        $row = $user_menu->fetch_assoc();
        $values['menu_item'] = unserialize($row['menu_item']);
        $values['sdate'] = date("F d, Y h:i A", strtotime($row['menu_date']));
      } else {
        $values['menu_item'] = unserialize($values['menu_item']);
        $values['sdate'] = date("F d, Y h:i A", strtotime($values['menu_date']));
      }
      $values['thalisize'] = $thalisize;
      $sched_res[$values['id']] = $values;
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
              <div id="miqaat" style="display:none;">
              </div>
              <div id="sabji" class="form-group row" style="display:none;">
                <label for="sabji" class="col-xs-6 control-label" id="sabji"></label>
                <div class="col-xs-6">
                  <input type="hidden" class="form-control" name="menu_item[sabji][item]" id="sabji" value="">
                  <div class="input-group">
                    <span class="input-group-btn">
                      <button class="btn btn-primary btn-minus" type="button">-</button>
                    </span>
                    <input type="number" class="form-control" name="menu_item[sabji][qty]" id="sabjiqty" value="" min="0" max="2" readonly>
                    <span class="input-group-btn">
                      <button class="btn btn-primary btn-plus" type="button">+</button>
                    </span>
                  </div>
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
                    <input type="number" class="form-control" name="menu_item[tarkari][qty]" id="tarkariqty" value="" min="0" max="2" readonly>
                    <span class="input-group-btn">
                      <button class="btn btn-primary btn-plus" type="button">+</button>
                    </span>
                  </div>
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
                    <input type="number" class="form-control" name="menu_item[rice][qty]" id="riceqty" value="" min="0" max="3" readonly>
                    <span class="input-group-btn">
                      <button class="btn btn-primary btn-plus" type="button">+</button>
                    </span>
                  </div>
                </div>
              </div>
              <div id="roti" class="form-group row" style="display:none;">
                <label for="roti" class="col-xs-6 control-label" id="roti">Roti/Bread Item</label>
                <div class="col-xs-6">
                  <input type="hidden" class="form-control" name="menu_item[roti][item]" id="roti" value="">
                  <input type="text" class="form-control" name="menu_item[roti][qty]" id="rotiqty" value="" min="1" max="2" readonly>
                  <small>Please contact group admin to change the above menu item.</small>
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

    <?php include('_bottomJS.php'); ?>

    <div class="text-center">
      <a href="mailto:kalimimohallapoona@gmail.com">kalimimohallapoona@gmail.com</a><br><br>
    </div>
</body>

</html

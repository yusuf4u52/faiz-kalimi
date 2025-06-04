<?php
$takesFmb = mysqli_query($link, "SELECT * FROM thalilist where `Thali` = '" . $_SESSION['thali'] . "' AND `hardstop` != 1") or die(mysqli_error($link));
?>

<?php if (isset($takesFmb) && $takesFmb->num_rows > 0) {

  if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
    <div class="alert alert-success" role="alert">Thali of <strong>
        <?php echo date('d M Y', strtotime($_GET['date'])); ?>
      </strong> is edited successfully.</div>
  <?php } 
  if (isset($_GET['action']) && $_GET['action'] == 'sedit') { ?>
    <div class="alert alert-success" role="alert">Thali of <strong>
        <?php echo date('d M Y', strtotime($_GET['date'])); ?>
      </strong> is started & edited successfully.</div>
  <?php } 
  if (isset($_GET['action']) && $_GET['action'] == 'nochange') { ?>
    <div class="alert alert-warning" role="alert">You have't change anything on thali of <strong>
        <?php echo date('d M Y', strtotime($_GET['date'])); ?>
      </strong>.</div>
  <?php } 
  if (isset($_GET['action']) && $_GET['action'] == 'snochange') { ?>
    <div class="alert alert-success" role="alert">Thali of <strong>
        <?php echo date('d M Y', strtotime($_GET['date'])); ?>
      </strong> is started successfully.</div>
  <?php } 
  if (isset($_GET['action']) && $_GET['action'] == 'astop') { ?>
    <div class="alert alert-warning" role="alert">Thali of <strong>
        <?php echo date('d M Y', strtotime($_GET['date'])); ?>
      </strong> is already stopped.</div>
  <?php } 
  if (isset($_GET['action']) && $_GET['action'] == 'stop') { ?>
    <div class="alert alert-danger" role="alert">Thali of <strong>
        <?php echo date('d M Y', strtotime($_GET['date'])); ?>
      </strong> is stopped successfully.</div>
  <?php } 
  if (isset($_GET['action']) && $_GET['action'] == 'rsvp') { ?>
    <div class="alert alert-danger" role="alert">You can't edit the thali now because RSVP time for editing thali of
      <strong>
        <?php echo date('d M Y', strtotime($_GET['date'])); ?>
      </strong> is finished.
    </div>
  <?php } 
  if (isset($_GET['action']) && $_GET['action'] == 'addfeed') { ?>
    <div class="alert alert-success" role="alert">Thank you for your valuable feedback for thali on
      <strong>
        <?php echo date('d M Y', strtotime($_GET['date'])); ?>
      </strong>.
    </div>
  <?php } 
  if (isset($_GET['action']) && $_GET['action'] == 'editfeed') { ?>
    <div class="alert alert-success" role="alert">Thank you for your valuable feedback for thali on
      <strong>
        <?php echo date('d M Y', strtotime($_GET['date'])); ?>
      </strong>.
    </div>
  <?php } ?>

  <?php $takesFmb = $takesFmb->fetch_assoc();
  $thalisize = $takesFmb['thalisize'];
  $result = mysqli_query($link, "SELECT * FROM menu_list order by `menu_date` DESC") or die(mysqli_error($link));
  $sched_res = [];
  while ($menu = mysqli_fetch_assoc($result)) {
    $user_menu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $menu['menu_date'] . "' AND `thali` = '" . $_SESSION['thali'] . "'") or die(mysqli_error($link));
    if ($user_menu->num_rows > 0) {
      $row = $user_menu->fetch_assoc();
      $menu_item = mysqli_query($link, "SELECT `menu_item` FROM menu_list WHERE `menu_date` = '" . $row['menu_date'] . "'") or die(mysqli_error($link));
      $max_item = $menu_item->fetch_assoc();
      $menu['max_item'] = unserialize($max_item['menu_item']);
      $menu['menu_item'] = unserialize($row['menu_item']);
      $menu['sdate'] = date("F d, Y h:i A", strtotime($row['menu_date']));
    } else {
      $menu['max_item'] = unserialize($menu['menu_item']);
      $menu['menu_item'] = unserialize($menu['menu_item']);
      $menu['sdate'] = date("F d, Y h:i A", strtotime($menu['menu_date']));
    }
    $user_feedmenu = mysqli_query($link, "SELECT * FROM user_feedmenu WHERE `menu_date` = '" . $menu['menu_date'] . "' AND `thali` = '" . $_SESSION['thali'] . "'") or die(mysqli_error($link));
    if ($user_feedmenu->num_rows > 0) {
      $rowfeed = $user_feedmenu->fetch_assoc();
      $menu['menu_feed'] = unserialize($rowfeed['menu_feed']);
      $menu['feedback'] = $rowfeed['feedback'];
    }
    $stop_thali = mysqli_query($link, "SELECT * FROM stop_thali WHERE `stop_date` = '" . $menu['menu_date'] . "' AND `thali` = '" . $_SESSION['thali'] . "'") or die(mysqli_error($link));
    if ($stop_thali->num_rows > 0) {
      $menu['status'] = 'stop';
    } else {
      $menu['status'] = 'start';
    }
    $menu['thalisize'] = $thalisize;
    $sched_res[$menu['id']] = $menu;
  }
  $sched_res = json_encode($sched_res); ?>

  <div id="calendar"></div>

  <div class="modal fade" id="editmenu">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="changemenu" class="form-horizontal" method="post" action="changemenu.php" autocomplete="off">
          <input type="hidden" name="action" value="change_menu" />
          <input type="hidden" id="menu_id" name="menu_id" value="" />
          <input type="hidden" id="thali" name="thali" value="<?php echo $_SESSION['thali']; ?>" />
          <input type="hidden" id="thalisize" name="thalisize" value="<?php echo $thalisize; ?>" />
          <div class="modal-header">
            <h4 class="modal-title"></h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <div id="miqaat" class="row text-center d-none"></div>
            <div id="status" class="mb-3 row d-none">
              <label for="status" class="col-6 control-label">Thali Status</label>
              <div class="col-6">
                <div class="form-check form-switch d-flex align-items-center">
                  <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" checked>
                  <label id="status" class="form-check-label ms-1" for="status"></label>
                </div>
              </div>
            </div>
            <div id="thali" class="d-none">
              <div id="sabji" class="mb-3 row d-none">
                <label for="sabji" class="col-6 control-label" id="sabji"></label>
                <div class="col-6">
                  <input type="hidden" class="form-control" name="menu_item[sabji][item]" id="sabji" value="">
                  <div class="input-group">
                    <button class="btn btn-light btn-minus" type="button">-</button>
                    <input type="number" class="form-control" name="menu_item[sabji][qty]" id="sabjiqty" value="" min="0"
                      readonly>
                    <button class="btn btn-light btn-plus" type="button">+</button>
                  </div>
                </div>
              </div>
              <div id="tarkari" class="mb-3 row d-none">
                <label for="tarkari" class="col-6 control-label" id="tarkari">Tarkari/Dal Item</label>
                <div class="col-6">
                  <input type="hidden" class="form-control" name="menu_item[tarkari][item]" id="tarkari" value="">
                  <div class="input-group">
                    <button class="btn btn-light btn-minus" type="button">-</button>
                    <input type="number" class="form-control" name="menu_item[tarkari][qty]" id="tarkariqty" value=""
                      min="0" readonly>
                    <button class="btn btn-light btn-plus" type="button">+</button>
                  </div>
                </div>
              </div>
              <div id="rice" class="mb-3 row d-none">
                <label for="rice" class="col-6 control-label" id="rice">Rice Item</label>
                <div class="col-6">
                  <input type="hidden" class="form-control" name="menu_item[rice][item]" id="rice" value="">
                  <div class="input-group">
                    <button class="btn btn-light btn-minus" type="button">-</button>
                    <input type="number" class="form-control" name="menu_item[rice][qty]" id="riceqty" value="" min="0"
                      readonly>
                    <button class="btn btn-light btn-plus" type="button">+</button>
                  </div>
                </div>
              </div>
              <div id="roti" class="mb-3 row d-none">
                <label for="roti" class="col-6 control-label" id="roti">Roti/Bread Item</label>
                <div class="col-6">
                  <input type="hidden" class="form-control" name="menu_item[roti][item]" id="roti" value="">
                  <input type="text" class="form-control" name="menu_item[roti][qty]" id="rotiqty" value="" readonly>
                </div>
              </div>
              <div id="extra" class="mb-3 row d-none">
                <label for="extra" class="col-6 control-label" id="extra">Extra Item</label>
                <div class="col-6">
                  <input type="hidden" class="form-control" name="menu_item[extra][item]" id="extra" value="">
                  <input type="text" class="form-control" name="menu_item[extra][qty]" id="extraqty" value="" readonly>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-light rsvp-end d-none" disabled>RSVP Ended</button>
            <button type="button" class="btn btn-light feedback d-none" data-bs-target="#feedbackmenu" data-bs-toggle="modal" data-bs-dismiss="modal">Feedback</button>
            <button type="submit" class="btn btn-light edit-menu d-none">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="feedbackmenu">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <form id="feedbackmenu" class="form-horizontal" method="post" action="changemenu.php" autocomplete="off">
          <input type="hidden" name="action" value="feedback_menu" />
          <input type="hidden" id="menu_id" name="menu_id" value="" />
          <input type="hidden" id="thali" name="thali" value="<?php echo $_SESSION['thali']; ?>" />
          <input type="hidden" id="thalisize" name="thalisize" value="<?php echo $thalisize; ?>" />
          <div class="modal-header">
            <h4 class="modal-title"></h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <div id="sabji" class="mb-3 row d-none">
              <label for="sabji" class="col-4 control-label" id="sabji"></label>
              <div class="col-8">
                <input type="hidden" class="form-control" name="menu_item[sabji][item]" id="sabji" value="">
                <div class="form-check form-check-inline">
                  <input class="form-check-input sabjirating" type="radio" name="menu_item[sabji][rating]" id="sabjirating1" value="1" >
                  <label class="form-check-label" for="sabjirating1">1</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input sabjirating" type="radio" name="menu_item[sabji][rating]" id="sabjirating2" value="2" >
                  <label class="form-check-label" for="sabjirating2">2</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input sabjirating" type="radio" name="menu_item[sabji][rating]" id="sabjirating3" value="3" >
                  <label class="form-check-label" for="sabjirating3">3</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input sabjirating" type="radio" name="menu_item[sabji][rating]" id="sabjirating4" value="4" >
                  <label class="form-check-label" for="sabjirating4">4</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input sabjirating" type="radio" name="menu_item[sabji][rating]" id="sabjirating5" value="5" >
                  <label class="form-check-label" for="sabjirating5">5</label>
                </div>
              </div>
            </div>
            <div id="tarkari" class="mb-3 row d-none">
              <label for="tarkari" class="col-4 control-label" id="tarkari">Tarkari/Dal Item</label>
              <div class="col-8">
                <input type="hidden" class="form-control" name="menu_item[tarkari][item]" id="tarkari" value="">
                <div class="form-check form-check-inline">
                  <input class="form-check-input tarkarirating" type="radio" name="menu_item[tarkari][rating]" id="tarkarirating1" value="1" >
                  <label class="form-check-label" for="tarkarirating1">1</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input tarkarirating" type="radio" name="menu_item[tarkari][rating]" id="tarkarirating2" value="2" >
                  <label class="form-check-label" for="tarkarirating2">2</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input tarkarirating" type="radio" name="menu_item[tarkari][rating]" id="tarkarirating3" value="3" >
                  <label class="form-check-label" for="tarkarirating3">3</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input tarkarirating" type="radio" name="menu_item[tarkari][rating]" id="tarkarirating4" value="4" >
                  <label class="form-check-label" for="tarkarirating4">4</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input tarkarirating" type="radio" name="menu_item[tarkari][rating]" id="tarkarirating5" value="5" >
                  <label class="form-check-label" for="tarkarirating5">5</label>
                </div>
              </div>
            </div>
            <div id="rice" class="mb-3 row d-none">
              <label for="rice" class="col-4 control-label" id="rice">Rice Item</label>
              <div class="col-8">
                <input type="hidden" class="form-control" name="menu_item[rice][item]" id="rice" value="">
                <div class="form-check form-check-inline">
                  <input class="form-check-input ricerating" type="radio" name="menu_item[rice][rating]" id="ricerating1" value="1">
                  <label class="form-check-label" for="ricerating1">1</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input ricerating" type="radio" name="menu_item[rice][rating]" id="ricerating2" value="2">
                  <label class="form-check-label" for="ricerating2">2</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input ricerating" type="radio" name="menu_item[rice][rating]" id="ricerating3" value="3">
                  <label class="form-check-label" for="ricerating3">3</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input ricerating" type="radio" name="menu_item[rice][rating]" id="ricerating4" value="4">
                  <label class="form-check-label" for="ricerating4">4</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input ricerating" type="radio" name="menu_item[rice][rating]" id="ricerating5" value="5" >
                  <label class="form-check-label" for="ricerating5">5</label>
                </div>
              </div>
            </div>
            <div id="roti" class="mb-3 row d-none">
              <label for="roti" class="col-4 control-label" id="roti">Roti/Bread Item</label>
              <div class="col-8">
                <input type="hidden" class="form-control" name="menu_item[roti][item]" id="roti" value="">
                <div class="form-check form-check-inline">
                  <input class="form-check-input rotirating" type="radio" name="menu_item[roti][rating]" id="rotirating1" value="1" >
                  <label class="form-check-label" for="rotirating1">1</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input rotirating" type="radio" name="menu_item[roti][rating]" id="rotirating2" value="2" >
                  <label class="form-check-label" for="rotirating2">2</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input rotirating" type="radio" name="menu_item[roti][rating]" id="rotirating3" value="3" >
                  <label class="form-check-label" for="rotirating3">3</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input rotirating" type="radio" name="menu_item[roti][rating]" id="rotirating4" value="4" >
                  <label class="form-check-label" for="rotirating4">4</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input rotirating" type="radio" name="menu_item[roti][rating]" id="rotirating5" value="5" >
                  <label class="form-check-label" for="rotirating5">5</label>
                </div>
              </div>
            </div>
            <div id="extra" class="mb-3 row d-none">
              <label for="extra" class="col-4 control-label" id="extra">Extra Item</label>
              <div class="col-8">
                <input type="hidden" class="form-control" name="menu_item[extra][item]" id="extra" value="">
                <div class="form-check form-check-inline">
                  <input class="form-check-input extrarating" type="radio" name="menu_item[extra][rating]" id="extrarating1" value="1" >
                  <label class="form-check-label" for="extrarating1">1</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input extrarating" type="radio" name="menu_item[extra][rating]" id="extrarating2" value="2" >
                  <label class="form-check-label" for="extrarating2">2</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input extrarating" type="radio" name="menu_item[extra][rating]" id="extrarating3" value="3" >
                  <label class="form-check-label" for="extrarating3">3</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input extrarating" type="radio" name="menu_item[extra][rating]" id="extrarating4" value="4" >
                  <label class="form-check-label" for="extrarating4">4</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input extrarating" type="radio" name="menu_item[extra][rating]" id="extrarating5" value="5" >
                  <label class="form-check-label" for="extrarating5">5</label>
                </div>
              </div>
            </div>
            <div id="feedback" class="mb-3 row">
              <label for="feedback" class="col-4 control-label" id="feedback">Feedback</label>
              <div class="col-8">
                <textarea class="form-control" id="feedback" name="feedback" rows="3"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-light view-menu" data-bs-target="#editmenu" data-bs-toggle="modal" data-bs-dismiss="modal">View Menu</button>
            <button type="submit" class="btn btn-light submit-feedback d-none">Submit Feedback</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <script>
    var scheds = '<?php echo $sched_res; ?>';
  </script>
<?php } else {
  echo '<h3>You are not allowed to view menu as your thali is stopped.</h3>';
} ?>

<?php
include('header.php');
include('navbar.php');
include('getHijriDate.php');

$today = getTodayDateHijri();

$action = '';
if (isset($_POST)) {
  if (isset($_POST['comment'])) {
    $clean_comment = htmlentities(strip_tags($_POST['comment']), ENT_QUOTES, 'UTF-8');
    $comment_insert_query = "INSERT INTO `comments`(`author_id`,`user_id`,`comment`) values('" . $_SESSION['thaliid'] . "','" . $_POST['user_id'] . "','$clean_comment')";
    mysqli_query($link, $comment_insert_query);
    $action = 'comment';
  }

  if (isset($_POST['musaid'])) {
    $clean_musaid = htmlentities(strip_tags($_POST['musaid']), ENT_QUOTES, 'UTF-8');
    $change_musaid_query = "UPDATE `thalilist` SET `musaid` = '$clean_musaid' WHERE Thali = '" . $_GET['thalino'] . "'";
    mysqli_query($link, $change_musaid_query);
    $action = 'cmusaid';
    $cmusaid = $clean_musaid;
  }

  if (isset($_POST['thalisize'])) {
    mysqli_query($link, "update change_table set processed = 1 where userid = '" . $_POST['id'] . "' and `Operation` in ('Change Size') and processed = 0") or die(mysqli_error($link));
    mysqli_query($link, "UPDATE thalilist set thalisize='" . $_POST['thalisize'] . "' WHERE id = '" . $_POST['id'] . "'") or die(mysqli_error($link));
    mysqli_query($link, "INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`,`processed`) VALUES ('" . $_POST['Thali'] . "','" . $_POST['id'] . "', 'Change Size','" . $today . "',0)") or die(mysqli_error($link));
    $action = 'csize';
    $csize = $_POST['thalisize'];
  }

  unset($_POST);

}

$current_year = mysqli_fetch_assoc(mysqli_query($link, "SELECT value FROM settings where `key`='current_year'"));
$musaid_list = mysqli_query($link, "SELECT username, email FROM users");

if (isset($_GET['year'])) {
  if ($current_year['value'] == $_GET['year']) {
    $thalilist_tablename = "thalilist";
    $receipts_tablename = "receipts";
  } else {
    $thalilist_tablename = "thalilist_" . $_GET['year'];
    $receipts_tablename = "receipts_" . $_GET['year'];
  }

  $query = "SELECT id, Thali, tiffinno, NAME, CONTACT,musaid, thalisize, Active, Transporter, thalicount, Full_Address, Thali_start_date, Thali_stop_date, Paid, (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending FROM thalilist";
  if (!empty($_GET['thalino'])) {
    $query .= " WHERE Thali LIKE '%" . addslashes($_GET['thalino']) . "%'";
  } else if (!empty($_GET['tiffinno'])) {
    $query .= " WHERE tiffinno LIKE '%" . addslashes($_GET['tiffinno']) . "%'";
  } else if (!empty($_GET['general'])) {
    $query .= " WHERE 
                Email_ID LIKE '%" . addslashes($_GET['general']) . "%'
                or NAME LIKE '%" . addslashes($_GET['general']) . "%'
                or CONTACT LIKE '%" . addslashes($_GET['general']) . "%'
                or ITS_No LIKE '%" . addslashes($_GET['general']) . "%'
                ";
  }
  $result = mysqli_query($link, $query);
  $max_days = mysqli_fetch_row(mysqli_query($link, "SELECT MAX(thalicount) as max FROM `$thalilist_tablename`"));
}
?>
<div class="fmb-content mt-5">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-body">
            <h2 class="mb-3">Thali Search</h2>
            <?php if (isset($action) && $action == 'cmusaid') { ?>
              <div class="alert alert-success" role="alert">Musaid change to <strong><?php echo $cmusaid; ?></strong>.
              </div>
            <?php }
            if (isset($action) && $action == 'comment') { ?>
              <div class="alert alert-success" role="alert">A new comment is added for sabeel no
              <strong><?php echo $_GET['thalino']; ?></strong>.</div>
            <?php }
            if (isset($action) && $action == 'csize') { ?>
              <div class="alert alert-success" role="alert">Thali size is changes to
                <strong><?php echo $csize; ?></strong> for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong>.
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'srange') { ?>
              <div class="alert alert-success" role="alert">Thali from <strong>
                  <?php echo date('d M Y', strtotime($_GET['sdate'])); ?>
                </strong> to <strong>
                  <?php echo date('d M Y', strtotime($_GET['edate'])); ?></strong> for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong> is stopped successfully.
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'start') { ?>
              <div class="alert alert-success" role="alert">Stop thali dates from <strong>
                  <?php echo date('d M Y', strtotime($_GET['sdate'])); ?>
                </strong> to <strong>
                  <?php echo date('d M Y', strtotime($_GET['edate'])); ?>
                </strong> for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong> is deleted successfully.
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'spermanant') { ?>
              <div class="alert alert-danger" role="alert">Thali is stopped permanently for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong>
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'sedit') { ?>
              <div class="alert alert-warning" role="alert">RSVP ended to stop thali of <strong>
                  <?php echo date('d M Y', strtotime($_GET['sdate'])); ?> for sabeel no
                  <strong><?php echo $_GET['thalino']; ?></strong>
                </strong>.</div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'edit') { ?>
              <div class="alert alert-success" role="alert">Thali of
                <strong><?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> is edited successfully for sabeel
                no
                <strong><?php echo $_GET['thalino']; ?></strong>
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'sedit') { ?>
              <div class="alert alert-success" role="alert">Thali of <strong>
                  <?php echo date('d M Y', strtotime($_GET['date'])); ?>
                </strong> is started & edited successfully for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong>.
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'nochange') { ?>
              <div class="alert alert-warning" role="alert">No change found for Thali of
                <strong><?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong>.
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'snochange') { ?>
              <div class="alert alert-success" role="alert">Thali of <strong>
                  <?php echo date('d M Y', strtotime($_GET['date'])); ?>
                </strong> is started successfully for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong>.
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'astop') { ?>
              <div class="alert alert-warning" role="alert">Thali of <strong>
                  <?php echo date('d M Y', strtotime($_GET['date'])); ?>
                </strong> is already stopped for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong>.
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'stop') { ?>
              <div class="alert alert-success" role="alert">Thali of <strong>
                  <?php echo date('d M Y', strtotime($_GET['date'])); ?>
                </strong> is stopped successfully for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong>.
              </div>
            <?php }
            if (isset($_GET['action']) && $_GET['action'] == 'rsvp') { ?>
              <div class="alert alert-danger" role="alert">You can't edit the thali now because RSVP time for editing
                Thali
                of
                <strong>
                  <?php echo date('d M Y', strtotime($_GET['date'])); ?></strong> for sabeel no
                <strong><?php echo $_GET['thalino']; ?></strong> is finished.
              </div>
            <?php } ?>
            <form class="form-horizontal" autocomplete="off">
              <div class="mb-3 row">
                <label for="inputThalino" class="col-3 control-label">Sabeel No</label>
                <div class="col-9">
                  <input type="text" class="form-control" id="inputThalino" placeholder="Sabeel No" name="thalino" value="<?php echo (!empty($_GET['thalino']) ? $_GET['thalino'] : ''); ?>">
                </div>
              </div>
              <div class="mb-3 row">
                <label for="inputThalino" class="col-3 control-label">Tiffin No</label>
                <div class="col-9">
                  <input type="text" class="form-control" id="inputTiffinno" placeholder="Tiffin No" name="tiffinno" value="<?php echo (!empty($_GET['tiffinno']) ? $_GET['tiffinno'] : ''); ?>">
                </div>
              </div>
              <div class="mb-3 row">
                <label for="inputGeneral" class="col-3 control-label">Other</label>
                <div class="col-9">
                  <input type="text" class="form-control" id="inputGeneral" placeholder="Contact/ ITS no / Email / Name"
                    name="general" value="<?php echo (!empty($_GET['general']) ? $_GET['general'] : ''); ?>">
                </div>
              </div>
              <div class="mb-3 row">
                <label for="year" class="col-3 control-label">Year</label>
                <div class="col-9">
                  <select class="form-select" id="year" name="year">
                    <?php for ($i = 1438; $i <= 1450; $i++) { ?>
                      <option value="<?php echo $i; ?>" <?php if ($current_year['value'] == $i)
                           echo "selected"; ?>>
                        <?php echo $i; ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="mb-3 row">
                <div class="col-9 offset-3">
                  <button type="submit" class="btn btn-light">Submit</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <?php if (isset($_GET['year'])): ?>
          <div class="card">
            <div class="card-body">
              <h2 class="mb-3">Thali Info</h2>
              <form id="receiptForm" action="post" autocomplete="off">
                <div class="mb-3 row">
                  <div class="col-5 col-md-6">
                    <input class="form-control" type="number" name="receipt_amount" placeholder="Receipt Amount"
                      required />
                  </div>
                  <div class="col-3">
                    <select class="form-select" name="payment_type" id="payment_type" required>
                      <option value="">Select</option>
                      <option value="Cash">Cash</option>
                      <option value="Online">Online</option>
                      <option value="Cheque">Cheque</option>
                    </select>
                  </div>
                  <div class="col-4 col-md-3">
                    <input type="text" style="display:none" name="transaction_id" id="transaction_id"
                      placeholder="Transaction ID" />
                    <input type="hidden" name="receipt_thali" />
                    <input class="btn btn-light" type="button" name="cancel" value="Cancel" />
                    <input class="btn btn-light" type="button" name="save" value="Save" />
                  </div>
                </div>
              </form>
              <?php
              if (mysqli_num_rows($result) > 1)
                include('_thalisearch_multiple.php');
              else if (mysqli_num_rows($result) == 1)
                include('_thalisearch_single.php');
              else
                echo "No records found";
              ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="stop_thali">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="admin_stop" class="form-horizontal" method="post" action="stopthali.php" autocomplete="off">
        <input type="hidden" name="action" value="admin_stop_thali" />
        <input type="hidden" name="thali" value="<?php echo $values['Thali']; ?>" />
        <input type="hidden" name="general" value="<?php echo $_GET['general']; ?>" />
        <input type="hidden" name="year" value="<?php echo $_GET['year']; ?>" />
        <div class="modal-header">
          <h4 class="modal-title fs-5">Stop Thali of sabeel no <?php echo $values['Thali']; ?></h4>
          <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
              class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
          <div class="input-group input-daterange mb-3">
            <input type="text" class="form-control" name="start_date" id="start_date" placeholder="Start Date">
            <span class="input-group-text"><i class="bi bi-calendar"></i></span>
            <input type="text" class="form-control" name="end_date" id="end_date" placeholder="End Date">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-light">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="stop_permanent">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="stop_permanent" class="form-horizontal" method="post" action="stop_permanant.php" autocomplete="off">
        <input type="hidden" name="action" value="stop_permanant" />
        <input type="hidden" name="thali" value="<?php echo $values['Thali']; ?>" />
        <input type="hidden" name="thalino" value="<?php echo $_GET['thalino']; ?>" />
        <input type="hidden" name="general" value="<?php echo $_GET['general']; ?>" />
        <input type="hidden" name="year" value="<?php echo $_GET['year']; ?>" />
        <div class="modal-header">
          <h4 class="modal-title fs-5">Stop Permanent Thali of sabeel no <?php echo $values['Thali']; ?></h4>
          <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
              class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
          <p> Are you sure you want to permanently stop thali of sabeel no
            <strong><?php echo $values['Thali']; ?></strong>?
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">No</button>
          <button type="submit" class="btn btn-light">Yes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="changeMusaid">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" autocomplete="off">
        <div class="modal-header">
          <h4 class="modal-title fs-5">Change Musaid</h4>
          <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
              class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
          <select name="musaid" required='required' class="form-select">
            <option value=''>Select</option>
            <?php
            while ($musaid = mysqli_fetch_assoc($musaid_list)) {
              ?>
              <option value='<?php echo $musaid['email']; ?>'><?php echo $musaid['username']; ?></option>
              <?php
            }
            ?>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-light">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="changeThalisize">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" autocomplete="off">
        <div class="modal-header">
          <h4 class="modal-title fs-5">Change Thali Size</h4>
          <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
              class="bi bi-x-lg"></i></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?php echo $values['id']; ?>">
          <input type="hidden" name="Thali" value="<?php echo $values['Thali']; ?>">
          <input type="hidden" name="Active" value="<?php echo $values['Active']; ?>">
          <select name="thalisize" required='required' class="form-select">
            <option value=''>Select</option>
            <option value='Mini'>Mini</option>
            <option value='Small'>Small</option>
            <option value='Medium'>Medium</option>
            <option value='Large'>Large</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-light">Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php $adminmenu = mysqli_query($link, "SELECT * FROM menu_list WHERE `menu_date` >= '" . date('Y-m-d') . "' AND `menu_type` = 'thaali' order by `menu_date` DESC") or die(mysqli_error($link));
while ($amenu_values = mysqli_fetch_assoc($adminmenu)) {
  $menu_id = $amenu_values['id'];
  $menu_date = $amenu_values['menu_date'];
  if (!empty($values['Thali'])) {
    $adminumenu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '" . $amenu_values['menu_date'] . "' AND `thali` = '" . $values['Thali'] . "'") or die(mysqli_error($link));
    if (isset($adminumenu) && $adminumenu->num_rows > 0) {
      $rowaumenu = $adminumenu->fetch_assoc();
      $menu_item = unserialize($rowaumenu['menu_item']);
      if (!empty($menu_item['roti']['qty'])) {
        $roti_qty = $menu_item['roti']['qty'];
      }
      $target = 'adminusermenu-' . $menu_id;
    } else {
      $menu_item = unserialize($amenu_values['menu_item']);
      if (!empty($values['thalisize'])) {
        if ($values['thalisize'] == 'Mini' && !empty($menu_item['roti']['tqty'])) {
          $roti_qty = $menu_item['roti']['tqty'];
        } elseif ($values['thalisize'] == 'Small' && !empty($menu_item['roti']['sqty'])) {
          $roti_qty = $menu_item['roti']['sqty'];
        } elseif ($values['thalisize'] == 'Medium' && !empty($menu_item['roti']['mqty'])) {
          $roti_qty = $menu_item['roti']['mqty'];
        } elseif ($values['thalisize'] == 'Large' && !empty($menu_item['roti']['lqty'])) {
          $roti_qty = $menu_item['roti']['lqty'];
        }
      }
      $target = 'adminmenu-' . $menu_id;
    }
    $adminstopthali = mysqli_query($link, "SELECT * FROM stop_thali WHERE `stop_date` = '" . $amenu_values['menu_date'] . "' AND `thali` = '" . $values['Thali'] . "'") or die(mysqli_error($link));
    if ($adminstopthali->num_rows > 0) {
      $status = 'stop';
    } else {
      $status = 'start';
    }
  } ?>
  <div class="modal fade" id="<?php echo $target; ?>">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="changemenu-<?php echo $menu_id; ?>" class="form-horizontal" method="post" action="changemenu.php"
          autocomplete="off">
          <input type="hidden" name="action" value="admin_change_menu" />
          <input type="hidden" name="menu_id" value="<?php echo $menu_id; ?>" />
          <input type="hidden" name="thali" value="<?php echo $values['Thali']; ?>" />
          <input type="hidden" name="thalino" value="<?php echo $_GET['thalino']; ?>" />
          <input type="hidden" name="general" value="<?php echo $_GET['general']; ?>" />
          <input type="hidden" name="year" value="<?php echo $_GET['year']; ?>" />
          <div class="modal-header">
            <h4 class="modal-title fs-5">Edit Menu of
              <?php echo date('D d M y', strtotime($amenu_values['menu_date'])); ?> for Thaali no
              <?php echo $values['Thali']; ?>
            </h4>
            <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i
                class="bi bi-x-lg"></i></button>
          </div>
          <div class="modal-body">
            <div id="status" class="mb-3 row">
              <label for="status" class="col-6 control-label">Thali Status</label>
              <div class="col-6">
                <div class="form-check form-switch d-flex align-items-center">
                  <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" <?php echo ($status == 'start' ? 'checked' : ''); ?>>
                  <label id="status" class="form-check-label ms-1" for="status" <?php echo ($status == 'start' ? 'style=color:#3C5A05;' : 'style=color:#ff0000;'); ?>><?php echo ($status == 'start' ? 'Start' : 'Stop'); ?></label>
                </div>
              </div>
            </div>
            <div id="thali" class="<?php echo ($status == 'stop' ? 'd-none' : ''); ?>">
              <?php if (!empty($menu_item['sabji']['item'])) { ?>
                <div class="mb-3 row row">
                  <label for="sabji" class="col-6 control-label"><?php echo $menu_item['sabji']['item']; ?></label>
                  <div class="col-6">
                    <input type="hidden" class="form-control" name="menu_item[sabji][item]" id="sabji"
                      value="<?php echo (!empty($menu_item['sabji']['item']) ? $menu_item['sabji']['item'] : ''); ?>">
                    <div class="input-group">
                      <button class="btn btn-light btn-minus" type="button">-</button>
                      <input type="number" class="form-control" name="menu_item[sabji][qty]" id="sabjiqty"
                        value="<?php echo (!empty($menu_item['sabji']['qty']) ? $menu_item['sabji']['qty'] : '1'); ?>"
                        min="0" readonly>
                      <button class="btn btn-light btn-plus" type="button">+</button>
                    </div>
                  </div>
                </div>
              <?php } ?>
              <?php if (!empty($menu_item['tarkari']['item'])) { ?>
                <div class="mb-3 row row">
                  <label for="tarkari" class="col-6 control-label"><?php echo $menu_item['tarkari']['item']; ?></label>
                  <div class="col-6">
                    <input type="hidden" class="form-control" name="menu_item[tarkari][item]" id="tarkari"
                      value="<?php echo (!empty($menu_item['tarkari']['item']) ? $menu_item['tarkari']['item'] : ''); ?>">
                    <div class="input-group">
                      <button class="btn btn-light btn-minus" type="button">-</button>
                      <input type="number" class="form-control" name="menu_item[tarkari][qty]" id="tarkariqty"
                        value="<?php echo (!empty($menu_item['tarkari']['qty']) ? $menu_item['tarkari']['qty'] : '1'); ?>"
                        min="0" readonly>
                      <button class="btn btn-light btn-plus" type="button">+</button>
                    </div>
                  </div>
                </div>
              <?php } ?>
              <?php if (!empty($menu_item['rice']['item'])) { ?>
                <div class="mb-3 row row">
                  <label for="rice" class="col-6 control-label"><?php echo $menu_item['rice']['item']; ?></label>
                  <div class="col-6">
                    <input type="hidden" class="form-control" name="menu_item[rice][item]" id="rice"
                      value="<?php echo (!empty($menu_item['rice']['item']) ? $menu_item['rice']['item'] : ''); ?>">
                    <div class="input-group">
                      <button class="btn btn-light btn-minus" type="button">-</button>
                      <input type="number" class="form-control" name="menu_item[rice][qty]" id="riceqty"
                        value="<?php echo (!empty($menu_item['rice']['qty']) ? $menu_item['rice']['qty'] : '1'); ?>" min="0"
                        readonly>
                      <button class="btn btn-light btn-plus" type="button">+</button>
                    </div>
                  </div>
                </div>
              <?php } ?>
              <?php if (!empty($menu_item['roti']['item'])) { ?>
                <div class="mb-3 row row">
                  <label for="roti" class="col-6 control-label"><?php echo $menu_item['roti']['item']; ?></label>
                  <div class="col-6">
                    <input type="hidden" class="form-control" name="menu_item[roti][item]" id="roti"
                      value="<?php echo (!empty($menu_item['roti']['item']) ? $menu_item['roti']['item'] : ''); ?>">
                    <input type="number" class="form-control" name="menu_item[roti][qty]" id="rotiqty"
                      value="<?php echo (!empty($roti_qty) ? $roti_qty : '1'); ?>" min="0" readonly>
                  </div>
                </div>
              <?php } ?>
              <?php if (!empty($menu_item['extra']['item'])) { ?>
                <div class="mb-3 row row">
                  <label for="roti" class="col-6 control-label"><?php echo $menu_item['extra']['item']; ?></label>
                  <div class="col-6">
                    <input type="hidden" class="form-control" name="menu_item[extra][item]" id="extra"
                      value="<?php echo (!empty($menu_item['extra']['item']) ? $menu_item['extra']['item'] : ''); ?>">
                    <input type="number" class="form-control" name="menu_item[extra][qty]" id="extraqty"
                      value="<?php echo (!empty($menu_item['extra']['qty']) ? $menu_item['extra']['qty'] : '1'); ?>" min="0"
                      readonly>
                  </div>
                </div>
              <?php } ?>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-light">Save changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php }
mysqli_free_result($adminmenu); ?>

<?php
$stop_dates = mysqli_query($link, "WITH ranked_dates AS (
    SELECT `id`, `thali`, `stop_date`, ROW_NUMBER() OVER (PARTITION BY `thali` ORDER BY `stop_date`) AS row_num FROM `stop_thali` where `stop_date` > '" . date('Y-m-d') . "' AND `Thali` = '" . $values['Thali'] . "'
),
grouped_dates AS (
    SELECT `id`, `thali`, `stop_date`, DATE_SUB(`stop_date`, INTERVAL row_num DAY) AS group_key FROM ranked_dates
)
SELECT `id`, `thali`, MIN(`stop_date`) AS start_date, MAX(`stop_date`) AS end_date FROM grouped_dates GROUP BY `thali`, group_key ORDER BY start_date;") or die(mysqli_error($link));
if (isset($stop_dates) && $stop_dates->num_rows > 0) {
  while ($values = mysqli_fetch_assoc($stop_dates)) { ?>
    <div class="modal fade" id="startthali-<?php echo $values['id']; ?>">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="startthali-<?php echo $values['id']; ?>" class="form-horizontal" method="post" action="stopthali.php"
            autocomplete="off">
            <input type="hidden" name="action" value="admin_start_thali" />
            <input type="hidden" name="thali" value="<?php echo $values['thali']; ?>" />
            <input type="hidden" name="start_date" value="<?php echo $values['start_date']; ?>" />
            <input type="hidden" name="end_date" value="<?php echo $values['end_date']; ?>" />
            <div class="modal-header">
              <h4 class="modal-title fs-5">Delete Stop Thali Dates</h4>
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
}
mysqli_free_result($stop_dates); ?>

<?php include('footer.php'); ?>
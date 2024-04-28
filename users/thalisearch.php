<?php
include('connection.php');
include('_authCheck.php');

if ($_POST) {
  if ($_POST['comment']) {
    $clean_comment = htmlentities(strip_tags($_POST['comment']), ENT_QUOTES, 'UTF-8');
    $comment_insert_query = "INSERT INTO `comments`(`author_id`,`user_id`,`comment`) values('" . $_SESSION['thaliid'] . "','" . $_POST['user_id'] . "','$clean_comment')";

    mysqli_query($link, $comment_insert_query);
  }

  if ($_POST['musaid']) {
    $clean_musaid = htmlentities(strip_tags($_POST['musaid']), ENT_QUOTES, 'UTF-8');
    $change_musaid_query = "UPDATE `thalilist` SET `musaid` = '$clean_musaid' WHERE Thali = '" . $_GET['thalino'] . "'";
    mysqli_query($link, $change_musaid_query);
  }
  header("Location: thalisearch.php?thalino=" . $_GET['thalino'] . "&general=" . $_GET['general'] . "&year=" . $_GET['year']);
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

  $query = "SELECT id, Thali, NAME, CONTACT,musaid, thalisize, Active, Transporter, thalicount, Full_Address, Thali_start_date, Thali_stop_date, Paid, (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending FROM thalilist";
  if (!empty($_GET['thalino'])) {
    $query .= " WHERE Thali LIKE '%" . addslashes($_GET['thalino']) . "%'";
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
<!DOCTYPE html>
<!-- saved from url=(0029)http://bootswatch.com/flatly/ -->
<html lang="en">

<head><?php include('_head.php'); ?></head>

<body>
  <?php include('_nav.php'); ?>
  <div class="container">
    
    <!-- Forms
      ================================================== -->
    <div class="row">
      <div class="col-lg-12">
        <div class="page-header">
          <h2 id="forms">Thali Search</h2>
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12">
          <div class="well bs-component">
            <form class="form-horizontal">
              <fieldset>
                <div class="form-group">
                  <label for="inputThalino" class="col-lg-2 control-label">Thali No</label>
                  <div class="col-lg-10">
                    <input type="text" class="form-control" id="inputThalino" placeholder="Thali No" name="thalino">
                  </div>
                </div>
                <div class="form-group">
                  <label for="inputGeneral" class="col-lg-2 control-label">Other</label>
                  <div class="col-lg-10">
                    <input type="text" class="form-control" id="inputGeneral" placeholder="Contact/ ITS no / Email / Name" name="general">
                  </div>
                </div>
                <div class="form-group">
                  <label for="year" class="col-lg-2 control-label">Year</label>
                  <div class="col-lg-10">
                    <select class="form-control" id="year" name="year">
                      <?php for ($i = 1438; $i <= 1450; $i++) { ?>
                        <option value="<?php echo $i; ?>" <?php if ($current_year['value'] == $i) echo "selected"; ?>><?php echo $i; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <div class="col-lg-10 col-lg-offset-2">
                    <button type="submit" class="btn btn-primary">Submit</button>
                  </div>
                </div>
              </fieldset>
            </form>
          </div>
        </div>

        <?php if (isset($_GET['year'])) : ?>
          <div class="col-lg-12">
            <div class="col-lg-12">
              <div class="page-header">
                <h2 id="tables">Thali Info</h2>
              </div>
              <div class="bs-component">
                <div id="receiptForm">
                  <div class="form-group row">
                    <div class="col-xs-5 col-md-6">
                      <input class="form-control" type="number" name="receipt_amount" placeholder="Receipt Amount" />
                    </div>
                    <div class="col-xs-3">
                      <select class="form-control" name="payment_type" id="payment_type">
                        <option></option>
                        <option value="Cash">Cash</option>
                        <option value="Online">Online</option>
                        <option value="Cheque">Cheque</option>
                      </select>
                    </div>
                    <div class="col-xs-4 col-md-3">
                      <input type="text" style="display:none" name="transaction_id" id="transaction_id" placeholder="Transaction ID" />
                      <input type="hidden" name="receipt_thali" />
                      <input class="btn btn-default" type="button" name="cancel" value="Cancel" />
                      <input class="btn btn-primary" type="button" name="save" value="Save" />
                    </div>
                  </div>
                </div>
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
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <div class="modal" id="changeMusaid">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title">Change Musaid</h4>
          </div>
          <div class="modal-body">
            <select name="musaid" required='required' class="form-control">
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
            <button type="submit" class="btn btn-primary">Submit</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal" id="changeThalisize">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="changethalisize.php">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title">Change Musaid</h4>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" value="<?php echo $values['id']; ?>">
            <input type="hidden" name="Thali" value="<?php echo $values['Thali']; ?>">
            <input type="hidden" name="Active" value="<?php echo $values['Active']; ?>">
            <select name="thalisize" required='required' class="form-control">
              <option value=''>Select</option>
              <option value='Mini'>Mini</option>
              <option value='Small'>Small</option>
              <option value='Medium'>Medium</option>
              <option value='Large'>Large</option>
            </select>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary">Submit</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php $adminmenu = mysqli_query($link, "SELECT * FROM menu_list WHERE `menu_date` >= '" . date('Y-m-d') . "' AND `menu_type` = 'thaali' order by `menu_date` DESC") or die(mysqli_error($link));
  while ($amenu_values = mysqli_fetch_assoc($adminmenu)) {
    if( !empty($values['Thali'])) {
      $adminumenu = mysqli_query($link, "SELECT * FROM user_menu WHERE `menu_date` = '".$amenu_values['menu_date']."' AND `thali` = '".$values['Thali']."'") or die(mysqli_error($link));
    }
    if(isset($adminumenu) && $adminumenu->num_rows > 0) {
      $rowaumenu = $adminumenu->fetch_assoc();
      echo $menu_id = $rowaumenu['id'];
      $menu_date = $rowaumenu['menu_date'];
      $menu_item = unserialize($rowaumenu['menu_item']);
      if( !empty($menu_item['roti']['qty'])) {
        $roti_qty = $menu_item['roti']['qty'];
      }
      $target = 'adminusermenu-'.$menu_id; 
    } else {
      $menu_id = $amenu_values['id'];
      $menu_date = $amenu_values['menu_date'];
      $menu_item = unserialize($amenu_values['menu_item']);
      if( !empty($values['thalisize'])) {
        if($values['thalisize'] == 'Mini') {
          $roti_qty = $menu_item['roti']['tqty'];
        }elseif($values['thalisize'] == 'Small') {
          $roti_qty = $menu_item['roti']['sqty'];
        } elseif($values['thalisize'] == 'Medium') {
          $roti_qty = $menu_item['roti']['mqty'];
        } elseif($values['thalisize'] == 'Large') {
          $roti_qty = $menu_item['roti']['lqty'];
        }
      }
      $target = 'adminmenu-'.$menu_id;
    } ?>
    <div class="modal" id="<?php echo $target; ?>">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="changemenu-<?php echo $menu_id; ?>" class="form-horizontal" method="post" action="changemenu.php">
            <input type="hidden" name="action" value="admin_change_menu" />
            <input type="hidden" name="menu_id" value="<?php echo $menu_id; ?>" />
            <input type="hidden" name="thali" value="<?php echo $values['id']; ?>" />
            <input type="hidden" name="thalino" value="<?php echo $_GET['thalino']; ?>" />
            <input type="hidden" name="general" value="<?php echo $_GET['general']; ?>" />
            <input type="hidden" name="year" value="<?php echo $_GET['year']; ?>" />
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title">Edit Menu of <?php echo $amenu_values['menu_date']; ?> for thaali <?php echo $values['Thali']; ?></h4>
            </div>
            <div class="modal-body">
              <?php if (!empty($menu_item['sabji']['item'])) { ?>
                <div class="form-group row">
                  <label for="sabji" class="col-xs-6 control-label"><?php echo $menu_item['sabji']['item']; ?></label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[sabji][item]" id="sabji" value="<?php echo (!empty($menu_item['sabji']['item']) ? $menu_item['sabji']['item'] : ''); ?>">
                    <div class="input-group">
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-minus" type="button">-</button>
                      </span>
                      <input type="number" class="form-control" name="menu_item[sabji][qty]" id="sabjiqty" value="<?php echo (!empty($menu_item['sabji']['qty']) ? $menu_item['sabji']['qty'] : '1'); ?>" min="0" readonly>
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-plus" type="button">+</button>
                      </span>
                    </div>
                  </div>
                </div>
              <?php } ?>
              <?php if (!empty($menu_item['tarkari']['item'])) { ?>
                <div class="form-group row">
                  <label for="tarkari" class="col-xs-6 control-label"><?php echo $menu_item['tarkari']['item']; ?></label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[tarkari][item]" id="tarkari" value="<?php echo (!empty($menu_item['tarkari']['item']) ? $menu_item['tarkari']['item'] : ''); ?>">
                    <div class="input-group">
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-minus" type="button">-</button>
                      </span>
                      <input type="number" class="form-control" name="menu_item[tarkari][qty]" id="tarkariqty" value="<?php echo (!empty($menu_item['tarkari']['qty']) ? $menu_item['tarkari']['qty'] : '1'); ?>" min="0" readonly>
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-plus" type="button">+</button>
                      </span>
                    </div>
                  </div>
                </div>
              <?php } ?>
              <?php if (!empty($menu_item['rice']['item'])) { ?>
                <div class="form-group row">
                  <label for="rice" class="col-xs-6 control-label"><?php echo $menu_item['rice']['item']; ?></label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[rice][item]" id="rice" value="<?php echo (!empty($menu_item['rice']['item']) ? $menu_item['rice']['item'] : ''); ?>">
                    <div class="input-group">
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-minus" type="button">-</button>
                      </span>
                      <input type="number" class="form-control" name="menu_item[rice][qty]" id="riceqty" value="<?php echo (!empty($menu_item['rice']['qty']) ? $menu_item['rice']['qty'] : '1'); ?>" min="0" readonly>
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-plus" type="button">+</button>
                      </span>
                    </div>
                  </div>
                </div>
              <?php } ?>
              <?php if (!empty($menu_item['roti']['item'])) { ?>
                <div class="form-group row">
                  <label for="roti" class="col-xs-6 control-label"><?php echo $menu_item['roti']['item']; ?></label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[roti][item]" id="roti" value="<?php echo (!empty($menu_item['roti']['item']) ? $menu_item['roti']['item'] : ''); ?>">
                    <div class="input-group">
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-minus" type="button">-</button>
                      </span>
                      <input type="number" class="form-control" name="menu_item[roti][qty]" id="rotiqty" value="<?php echo (!empty($roti_qty) ? $roti_qty : '1'); ?>" min="0" readonly>
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-plus" type="button">+</button>
                      </span>
                    </div>
                  </div>
                </div>
              <?php } ?>
              <?php if (!empty($menu_item['extra']['item'])) { ?>
                <div class="form-group row">
                  <label for="roti" class="col-xs-6 control-label"><?php echo $menu_item['extra']['item']; ?></label>
                  <div class="col-xs-6">
                    <input type="hidden" class="form-control" name="menu_item[extra][item]" id="extra" value="<?php echo (!empty($menu_item['extra']['item']) ? $menu_item['extra']['item'] : ''); ?>">
                    <div class="input-group">
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-minus" type="button">-</button>
                      </span>
                      <input type="number" class="form-control" name="menu_item[extra][qty]" id="riceqty" value="<?php echo (!empty($menu_item['extra']['qty']) ? $menu_item['extra']['qty'] : '1'); ?>" min="0" readonly>
                      <span class="input-group-btn">
                        <button class="btn btn-primary btn-plus" type="button">+</button>
                      </span>
                    </div>
                  </div>
                </div>
              <?php } ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php }
  mysqli_free_result($adminmenu); ?>

  <?php include('_bottomJS.php'); ?>
  <script>
    $(function() {
      var receiptForm = $('#receiptForm');
      receiptForm.hide();
      $('[data-key="payhoob"]').click(function() {
        $('[name="receipt_thali"]', receiptForm).val($(this).attr('data-thali'));
        receiptForm.show();
      });
      $('[name="save"]').click(function() {
        var data = '';
        $('input[type!="button"]', receiptForm).each(function() {
          data = data + $(this).attr('name') + '=' + $(this).val() + '&';
        });
        data = data + "payment_type=" + $('#payment_type').val();
        $.ajax({
          method: 'post',
          url: '_payhoob.php',
          async: 'false',
          data: data,
          success: function(data) {
            if (data.includes("Success")) {
              alert('Hoob sucessfully updated.');
              receiptForm.hide();
              location.reload();
              // } else if(data == 'DuplicateReceiptNo') {
              //   alert('Receipt number already exists in database');
            } else {
              alert(data);
            }
          },
          error: function() {
            alert('Try again');
          }
        });
      });
      $('[name="cancel"]').click(function() {
        receiptForm.hide();
      });
      $('[data-key="stopthaali"]').click(function() {
        stopThali_admin($(this).attr('data-thali'), $(this).attr('data-active'), false, false, function(data) {
          // if (data === 'success') {
          location.reload();
          // }
        });
      });
      $('[data-key="stoppermanant"]').click(function() {
        var c = confirm("Are you sure you want to permanently stop this thali?");
        if (c == false) {
          return;
        }
        var clearHub;
        var r = confirm("Press OK to clear pending hub or CANCEL to go ahead with stop permanent without clearing!");
        if (r == true) {
          clearHub = "true";
        } else {
          clearHub = "false";
        }
        $.post("stop_permanant.php", {
            Thaliid: $(this).data("thali"),
            clear: clearHub
          },
          function(data, status) {
            alert("Thali Stopped Successfully and Number released to be re-used");
            location.reload();
          });
      });

      $('#payment_type').on('change', function() {
        if ($(this).val() === "Cash") {
          $("#transaction_id").hide()
        } else {
          $("#transaction_id").show()
        }
      });

      <?php if ($_GET) : ?>
        window.location = '#tables';
      <?php endif; ?>
    });
  </script>
</body>

</html>
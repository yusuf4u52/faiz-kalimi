<?php
include('connection.php');
include('_authCheck.php');

if($_POST){
  if($_POST['comment']){
    $clean_comment = htmlentities(strip_tags($_POST['comment']), ENT_QUOTES, 'UTF-8');
    $comment_insert_query = "INSERT INTO `comments`(`author_id`,`user_id`,`comment`) values('".$_SESSION['thaliid']."','".$_POST['user_id']."','$clean_comment')";

    mysqli_query($link,$comment_insert_query);
  }

  if($_POST['musaid']){
    $clean_musaid = htmlentities(strip_tags($_POST['musaid']), ENT_QUOTES, 'UTF-8');
    $change_musaid_query = "UPDATE `thalilist` SET `musaid` = '$clean_musaid' WHERE Thali = ".$_GET['thalino'];

    mysqli_query($link,$change_musaid_query);
  }
  header("Location: thalisearch.php?thalino=".$_GET['thalino']."&general=".$_GET['general']."&year=".$_GET['year']);
}

$current_year = mysqli_fetch_assoc(mysqli_query($link,"SELECT value FROM settings where `key`='current_year'"));
$musaid_list = mysqli_fetch_all(mysqli_query($link,"SELECT username, email FROM users"), MYSQLI_ASSOC);


if ($_GET) {
  if ($current_year['value'] == $_GET['year']) {
    $thalilist_tablename = "thalilist";
    $receipts_tablename = "receipts";
  } else {
    $thalilist_tablename = "thalilist_".$_GET['year'];
    $receipts_tablename = "receipts_".$_GET['year'];
  }

  $query="SELECT id, Thali, NAME, CONTACT, Active, Transporter, thalicount, Full_Address, Thali_start_date, Thali_stop_date, Paid, (Previous_Due + yearly_hub + Zabihat - Paid) AS Total_Pending FROM thalilist";
  if (!empty($_GET['thalino'])) {
    $query .= " WHERE Thali = '" . addslashes($_GET['thalino']) . "'";
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
                      <?php for ($i=1438;$i<=1450;$i++) { ?>
                        <option value="<?php echo $i; ?>" <?php if($current_year['value'] == $i) echo "selected";?>><?php echo $i; ?></option>
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
        <?php
        if ($_GET) :
        ?>
          <div class="col-lg-12">
            <div class="col-lg-12">
              <div class="page-header">
                <h2 id="tables">Thali Info</h2>
              </div>
              <div class="bs-component">
                <div id="receiptForm">
                  <input type="number" name="receipt_amount" placeholder="Receipt Amount" />
                  <input type="hidden" name="receipt_thali" />
                  <input type="button" name="cancel" value="cancel" />
                  <input type="button" name="save" value="save" />
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
                  foreach($musaid_list as $musaid) {
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
              alert('Update failed. Please do not add receipt again unless you check system values properly');
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
          if (data === 'success') {
            location.reload();
          }
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
      <?php if ($_GET) : ?>
        window.location = '#tables';
      <?php endif; ?>
    });
  </script>
</body>
</html>
<?php
include '_authCheck.php';
include 'getHijriDate.php';

$today = getTodayDateHijri();
if ($_POST) {
  $_POST['address'] = str_replace("'", "", $_POST['address']);
  mysqli_query($link, "UPDATE thalilist set
                                      CONTACT='" . $_POST["contact"] . "',
                                      niyazdate=NULLIF('" . $_POST["niyazdate"] . "', ''),
                                      Full_Address='" . $_POST["address"] . "',
                                      ITS_No='" . $_POST["its"] . "',
                                      wingflat='" . $_POST["wingflat"] . "',
                                      society='" . $_POST["society"] . "',
                                      WhatsApp='" . $_POST["whatsapp"] . "'
                                      WHERE Email_id = '" . $_SESSION['email'] . "'") or die(mysqli_error($link));

  if ($_POST['address'] != $_SESSION['old_address']) {
    mysqli_query($link, "UPDATE thalilist set Transporter='Transporter' where id ='" . $_SESSION['thaliid'] . "'");
    mysqli_query($link, "update change_table set processed = 1 where userid = '" . $_SESSION['thaliid'] . "' and `Operation` in ('Update Address') and processed = 0") or die(mysqli_error($link));
    mysqli_query($link, "INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`) VALUES ('" . $_SESSION['thali'] . "','" . $_SESSION['thaliid'] . "', 'Update Address','" . $today . "')") or die(mysqli_error($link));
  }

  unset($_SESSION['old_address']);
  header('Location: index.php');
} else {
  $query = "SELECT * FROM thalilist where Email_id = '" . $_SESSION['email'] . "'";

  $data = mysqli_fetch_assoc(mysqli_query($link, $query));

  // print_r($data); exit;

  extract($data);
  $_SESSION['old_address'] = $Full_Address;
}

?>
<!DOCTYPE html>

<!-- saved from url=(0029)http://bootswatch.com/flatly/ -->

<html lang="en">

<head>
  <?php include '_head.php'; ?>
  <?php include '_bottomJS.php'; ?>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
  <style>
    .selected {
      background-color: red !important;
      color: white !important;
    }
  </style>
</head>

<body>

  <?php include '_nav.php'; ?>
  <div class="container">

    <!-- Forms

      ================================================== -->

    <div class="row">

      <div class="col-lg-12">

        <div class="page-header">

          <h2 id="forms">Update info</h2>
          <h4 id="forms">Make Sure you fill out all the required field to get to the Home Page</h4>

        </div>

      </div>

      <div class="row">

        <div class="col-lg-6">

          <div class="well bs-component">

            <form class="form-horizontal" method="post">

              <fieldset>

                <div class="form-group">

                  <label for="inputName" class="col-lg-2 control-label">Name</label>

                  <div class="col-lg-10">

                    <input type="text" class="form-control" id="inputName" placeholder="Name" required='required' name="name" value='<?php echo $NAME; ?>' disabled>

                  </div>

                </div>

                <div class="form-group">

                  <label for="inputIts" class="col-lg-2 control-label">ITS Id</label>

                  <div class="col-lg-10">

                    <input type="text" pattern="[0-9]{8}" class="form-control" id="inputIts" placeholder="its" required='required' name="its" value='<?php echo $ITS_No; ?>' title="Enter correct ITS ID">

                  </div>

                </div>

                <div class="form-group">
                  <label for="inputContact" class="col-lg-2 control-label">Mobile No.</label>
                  <div class="col-lg-10">
                    <input type="text" pattern="[0-9]{10}" class="form-control" id="inputContact" placeholder="Contact" required='required' name="contact" value='<?php echo $CONTACT; ?>' title="Enter 10 digits">
                  </div>
                </div>

                <div class="form-group">

                  <label for="inputwhatsapp" class="col-lg-2 control-label">WhatsApp No.</label>

                  <div class="col-lg-10">

                    <input type="text" class="form-control" id="inputwhatsapp" placeholder="WhatsApp" required='required' name="whatsapp" value='<?php echo $WhatsApp; ?>'>

                  </div>

                </div>

                <?php if (!empty($yearly_hub)) { ?>
                  <div class="form-group">
                    <label for="niyazdate" class="col-lg-2 control-label">Niyaz Date</label>
                    <div class="col-lg-10">
                      <input type="text" class="form-control" id="niyazdate" required='required' name="niyazdate" value='<?php echo $niyazdate; ?>' <?php echo !empty($niyazdate) ? "disabled" : ""; ?>>
                    </div>
                  </div>
                <?php } ?>

                <div class="form-group">
                  <label class="col-lg-2 control-label">Wing-Flat</label>
                  <div class="col-lg-10">
                    <input type="text" class="form-control" placeholder="B1-1002" required='required' name="wingflat" value='<?php echo $wingflat; ?>'>
                  </div>
                </div>

                <div class="form-group">
                  <label for="inputContact" class="col-lg-2 control-label">Society</label>
                  <div class="col-lg-10">
                    <select class="form-control" name="society" required='required'>
                      <option value=''>Select</option>
                      <?php
                      $society_list = mysqli_query($link, "SELECT distinct(society) FROM thalilist where Active=1 and society is not null order by society");
                      while ($society_option = mysqli_fetch_assoc($society_list)) {
                      ?>
                        <option value='<?php echo $society_option['society']; ?>' <?php echo ($society_option['society'] == $society) ? "selected" : ""; ?>><?php echo $society_option['society']; ?></option>
                      <?php
                      }
                      ?>
                    </select>
                  </div>
                </div>

                <div class="form-group">

                  <label for="inputAddress" class="col-lg-2 control-label">Address</label>

                  <div class="col-lg-10">
                    <textarea class="form-control" id="inputAddress" name="address"><?php echo $Full_Address; ?></textarea>

                  </div>

                </div>

                <div class="form-group">

                  <div class="col-lg-10 col-lg-offset-2">

                    <button type="submit" class="btn btn-primary" name='submit'>Submit</button>

                  </div>

                </div>

              </fieldset>

            </form>

          </div>

        </div>

      </div>

    </div>

  </div>

  <!-- Modal -->
  <div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">

      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Update info</h4>
        </div>
        <div class="modal-body">
          <p>Make Sure you fill out all the required field to get to the Home Page</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>

    </div>
  </div>
  <!-- Message model ends-->
  <?php if (isset($_GET['update_pending_info'])) { ?>
    <script type="text/javascript">
      $('#myModal').modal('show');
    </script>
  <?php } ?>

  <script>
    $(function() {
      var dates = "";
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          dates = JSON.parse(this.responseText);
        }
      };
      xhttp.open("GET", "_fetch_niyaz_dates.php", true);
      xhttp.send();

      $('#niyazdate').daterangepicker({
        singleDatePicker: true,
        showDropdowns: true,
        autoApply: true,
        locale: {
          format: 'YYYY-MM-DD'
        },
        autoUpdateInput: false,
        minDate: moment(),
        maxDate: "2022-04-01",
        isInvalidDate: function(date) {
          if (date.day() === 0 || dates.reserved.includes(date.format("YYYY-MM-DD")) || dates.nonthali.includes(date.format("YYYY-MM-DD"))) {
            return true;
          }
        },
        isCustomDate: function(date) {
          if (dates.reserved.includes(date.format("YYYY-MM-DD"))) {
            return "selected";
          }
        }
      });

      $('#niyazdate').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD'));
      });

      $('#niyazdate').on('hideCalendar.daterangepicker', function(ev, picker) {
        $(this).val('');
      });

    });
  </script>
</body>

</html>
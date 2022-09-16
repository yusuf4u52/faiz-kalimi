<?php
include('_authCheck.php');
include('_common.php');

$query = "SELECT * FROM thalilist LEFT JOIN transporters on thalilist.Transporter = transporters.Name where Email_id = '" . $_SESSION['email'] . "'";
$values = mysqli_fetch_assoc(mysqli_query($link, $query));

$musaid_details = mysqli_fetch_assoc(mysqli_query($link, "SELECT NAME, CONTACT FROM thalilist where Email_id = '" . $values['musaid'] . "'"));

$_SESSION['thaliid'] = $values['id'];
$_SESSION['thali'] = $values['Thali'];

// Check if users gmail id is registered with us and has got a thali number against it 
if (is_null($values['Active']) || $values['Active'] == 2) {
  $some_email = $_SESSION['email'];
  session_unset();
  session_destroy();

  $status = "Sorry! Either $some_email is not registered with us OR your thali is not active. Send and email to kalimimohallapoona@gmail.com";
  header("Location: login.php?status=$status");
  exit;
}

// Check if takhmeen is done for the year
// if (!empty($values['Thali']) && empty($values['yearly_hub'])) {
//   // header("Location: selectyearlyhub.php");
//   $some_email = $_SESSION['email'];
//   $status = "Sorry! Either $some_email is not registered with us OR your thali is not active. Send and email to kalimimohallapoona@gmail.com";
//   header("Location: login.php?status=$status");
//   exit;
// }

// Redirect users to update details page if any details are missing
if (!empty($values['Thali']) && (empty($values['ITS_No']) || empty($values['CONTACT']) || empty($values['WhatsApp']) || empty($values['wingflat']) || empty($values['society']) || empty($values['Full_Address']))) {
  header("Location: update_details.php?update_pending_info");
  exit;
}

// Redirect users to update details page if any details are missing only for thali takers
// if ($values['yearly_hub'] >= 72000 && empty($values['niyazdate'])) {
//   header("Location: update_details.php?update_pending_info");
//   exit;
// }

// Check if there is any enabled event that needs users response
$enabled_events_query = mysqli_query($link, "SELECT * FROM events where showonpage='1'");
$enabled_events_values = mysqli_fetch_assoc($enabled_events_query);

// if (empty($values['Transporter']) && !empty($enabled_events_values) && !isResponseReceived($enabled_events_values['id'])) {
if (!empty($enabled_events_values) && !isResponseReceived($enabled_events_values['id'])) {
  header("Location: events.php");
  exit;
}

// show the index page with hub miqaat breakdown
if (!empty($values['yearly_hub'])) {
  // fetch miqaats from db
  $sql = mysqli_query($link, "select miqat_date,miqat_description from sms_date");
  $miqaatslist = mysqli_fetch_all($sql);

  $miqaat_count = sizeof($miqaatslist);
  // calculate installment based on yearly hub and number of miqaats
  $installment = (int)($values['yearly_hub']) / $miqaat_count;
  $installment = floor($installment);

  // add installment to the miqaat array by individually adding installment
  // to each row and than pushing that row into new array.
  $miqaatslistwithinstallement = array();
  foreach ($miqaatslist as $miqaat) {
    array_push($miqaat, $installment);
    array_push($miqaatslistwithinstallement, $miqaat);
  }

  // add any previous year pending to first installment
  $miqaatslistwithinstallement[0][2] += $values['Previous_Due'];

  // adjust installments if hub is paid
  if (!empty($values['Paid'])) {
    $paid = $values['Paid'];
    for ($i = 0; $i < $miqaat_count; $i++) {
      if ($miqaatslistwithinstallement[$i][2] - $paid  == 0) {
        $miqaatslistwithinstallement[$i][2] = 0;
        break;
      } else if ($miqaatslistwithinstallement[$i][2] - $paid > 0) {
        $miqaatslistwithinstallement[$i][2] = $miqaatslistwithinstallement[$i][2] - $paid;
        break;
      } else if ($miqaatslistwithinstallement[$i][2] - $paid < 0) {
        $paid = $paid - $miqaatslistwithinstallement[$i][2];
        $miqaatslistwithinstallement[$i][2] = 0;
      }
    }
  }

  // check if miqaat has passed, if so than move that passed miqaat amount to next
  $todays_date = date("Y-m-d");
  $previousInstall = 0;
  for ($i = 0; $i < $miqaat_count; $i++) {
    if ($miqaatslistwithinstallement[$i][0] < $todays_date) {
      $previousInstall += $miqaatslistwithinstallement[$i][2];
      $miqaatslistwithinstallement[$i + 1][2] += $miqaatslistwithinstallement[$i][2];
      $miqaatslistwithinstallement[$i][2] = "Miqaat Done";
    } else {
      $next_install = $miqaatslistwithinstallement[$i][2];
      mysqli_query($link, "UPDATE thalilist set next_install ='$next_install' WHERE Email_id = '" . $_SESSION['email'] . "'") or die(mysqli_error($link));
      break;
    }
  }
  mysqli_query($link, "UPDATE thalilist set prev_install_pending ='$previousInstall' WHERE Email_id = '" . $_SESSION['email'] . "'") or die(mysqli_error($link));
}
?>
<!DOCTYPE html>

<!-- saved from url=(0029)http://bootswatch.com/flatly/ -->

<html lang="en">

<head>
  <?php include('_head.php'); ?>
</head>

<body>

  <?php include('_nav.php'); ?>

  <div class="container">
    <br />
    <br />

    <div class="row">
      <div class="col-xs-6 col-sm-3 col-md-2">

        <?php
        if ($values['Transporter'] != 0) {
          if ($values['Active'] == 0) {
            if ($values['hardstop'] == 1) {
        ?>
              <input type="button" onclick="alert('<?php echo "You are not allowed to start your thali: " . $values['hardstop_comment'] ?>')" name="start_thali" value="Start Thaali" class="btn btn-success" />
            <?php

            } else {
            ?>

              <form method="POST" action="start_thali.php" onsubmit='return confirm("Are you sure?");' data-key="LazyLoad" class="hidden">
                <input type="submit" name="start_thali" value="Start Thaali" class="btn btn-success" />
              </form>

            <?php
            }
          } else {
            ?>

            <form method="POST" action="stop_thali.php" onsubmit='return confirm("Are you sure?");' data-key="LazyLoad" class="hidden">
              <input type="submit" name="stop_thali" value="Stop Thaali" class="btn btn-danger" />
            </form>

          <?php } ?>

      </div>

      <div class="col-xs-6 col-sm-3 col-md-2">
      <?php
        } else {
      ?>
        <form>
          <h5>You have not done hub takhmeen and so will not see START THALI button</h5>
        </form>
      <?php }
      ?>
      </div>
    </div>

    <br />

    <div class="row">
      <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        <div class="panel panel-default">
          <div class="panel-heading" role="tab" id="headingOne">
            <h4 class="panel-title">
              <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                Thaali Details <span class="text-muted" style="font-size: 12px; float: right;">(Click to Expand/Collapse)</span>
              </a>
            </h4>
          </div>
          <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
            <div class="panel-body">
              <ul class="list-group col">
                <li class="list-group-item">
                  <h6 class="list-group-item-head ing text-muted">Thaali Number</h6>
                  <p class="list-group-item-text"><strong><?php echo $values['Thali']; ?></strong></p>
                </li>
                <li class="list-group-item">
                  <h6 class="list-group-item-head ing text-muted">Tiffin Number</h6>
                  <p class="list-group-item-text"><strong><?php echo $values['tiffinno']; ?></strong></p>
                </li>
                <li class="list-group-item">
                  <h6 class="list-group-item-heading text-muted">Name</h6>
                  <p class="list-group-item-text"><strong><?php echo $values['NAME']; ?></strong></p>
                </li>
                <li class="list-group-item">
                  <h6 class="list-group-item-heading text-muted">Mobile Number</h6>
                  <p class="list-group-item-text"><strong><?php echo $values['CONTACT']; ?></strong></p>
                </li>
                <li class="list-group-item">
                  <h6 class="list-group-item-heading text-muted">Thali Type</h6>
                  <p class="list-group-item-text"><strong><?php echo $values['thalisize']; ?></strong></p>
                </li>
                <?php if ($musaid_details) { ?>
                  <li class="list-group-item">
                    <h6 class="list-group-item-heading text-muted">Musaid</h6>
                    <p class="list-group-item-text"><strong><?php echo $musaid_details['NAME']; ?> | <a href="tel:<?php echo $musaid_details['CONTACT']; ?>"><?php echo $musaid_details['CONTACT']; ?></a></strong></p>
                  </li>
                <?php } ?>
                <li class="list-group-item">
                  <h5 class="list-group-item-heading text-muted">Pending Hoob</h5>
                  <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Previous Year Pending
                      <span class="badge bg-primary rounded-pill"><?php echo $values['Previous_Due']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Current Year Takhmeen
                      <span class="badge bg-primary rounded-pill">+ <?php echo $values['yearly_hub']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Paid
                      <span class="badge bg-primary rounded-pill"><a href="hoobHistory.php">- <?php echo $values['Paid']; ?></a></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      Total Pending
                      <span class="badge bg-primary rounded-pill"><?php echo $values['Total_Pending']; ?></span>
                    </li>
                  </ul>
                </li>
                <li class="list-group-item">
                  <h6 class="list-group-item-heading text-muted">Is Active?</h6>
                  <p class="list-group-item-text"><strong><?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></strong></p>
                </li>
                <li class="list-group-item">
                  <h6 class="list-group-item-heading text-muted">Transporter</h6>
                  <p class="list-group-item-text">
                    <?php
                    echo "" . $values['Transporter'] . " | " . $values['Mobile'] . "";
                    ?>
                  </p>
                </li>
                <li class="list-group-item">
                  <h6 class="list-group-item-heading text-muted">Address</h6>
                  <p class="list-group-item-text"><?php echo $values['Full_Address']; ?></p>
                </li>

                <?php
                if ($values['Active'] == 1) {
                ?>
                  <li class="list-group-item">
                    <h6 class="list-group-item-heading text-muted">Start Date</h6>
                    <p class="list-group-item-text hijridate"><?php echo $values['Thali_Start_Date']; ?></p>
                  </li>

                <?php
                } else {
                ?>
                  <li class="list-group-item">
                    <h6 class="list-group-item-heading text-muted">Stop Date</h6>
                    <p class="list-group-item-text hijridate"><?php echo $values['Thali_Stop_Date']; ?></p>
                  </li>
                <?php } ?>
              </ul>
            </div>
          </div>
        </div>

        <!-- Break down -->
        <div class="panel panel-default" style="margin-top: 20px;">
          <div class="panel-heading" role="tab" id="headingTwo">
            <h4 class="panel-title">
              <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                Hoob Breakdown <span class="text-muted" style="font-size: 12px; float: right;">(Click to Expand/Collapse)</span>
              </a>
            </h4>
          </div>
          <div id="collapseTwo" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingTwo">
            <div class="panel-body">
              <h5 class="col-xs-12">The niyaaz amount will be payable throughout the year on the following miqaats. If possible do contribute the whole amount in Lailat ul Qadr</h5>
              <table class='table table-striped'>
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Pending Amount</th>
                    <th>Payment Link</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($miqaatslistwithinstallement as $miqaat) {
                  ?>
                    <tr>
                      <td><?php echo $miqaat['1']; ?></td>
                      <td><?php echo $miqaat['2']; ?></td>
                      <?php if ($miqaat['2'] != 0 || $miqaat['2'] != "Miqaat Done") { ?>
                        <td><a href="upi://pay?pa=50200068209839@HDFC0000029.ifsc.npci&pn=D B J TRUST K M POONA - FMB&cu=INR&am=<?php echo $miqaat['2']; ?>">Pay</a></td>
                      <?php } else { ?>
                        <td></td>
                      <?php } ?>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- Break down -->
      </div>
    </div>
  </div>

  <?php
  if (isset($_GET['status'])) {
  ?>
    <script type="text/javascript">
      <?php
      if ($_GET['status'] == 'Start Thali Successful') {
        $message = $_GET['status'] . '. ' . 'Your pending hoob : "' . $values['Total_Pending'] . '"';
      } else {
        $message = $_GET['status'];
      }
      ?>

      alert('<?php echo $message; ?>');
      window.location.href = window.location.href.split('?')[0];
    </script>
  <?php } ?>

  <?php include('_bottomJS.php'); ?>

  <div align="center">
    <a href="mailto:kalimimohallapoona@gmail.com">kalimimohallapoona@gmail.com</a><br><br>
  </div>
</body>

</html>
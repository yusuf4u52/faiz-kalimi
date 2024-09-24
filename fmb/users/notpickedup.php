<?php
include('connection.php');
include('_authCheck.php');
include('../sms/_credentials.php');
include('getHijriDate.php');

$today = getTodayDateHijri();

if ($_POST) {
  $_POST['thalino'] = rtrim($_POST['thalino'], ',');
  $singlethali = explode(',', $_POST['thalino']);

  foreach ($singlethali as $thali) {
    mysqli_query($link, "UPDATE thalilist set Reg_Fee = Reg_Fee + 200 WHERE Thali = '$thali'") or die(mysqli_error($link)) or die(mysqli_error($link));
    mysqli_query($link, "INSERT INTO not_picked_up (`Thali_no`, `Date`, `Reason`, `Fine` ) VALUES ( '$thali', '" . $today . "' , 'Not Picked Up' , 200)") or die(mysqli_error($link));

    $sql = mysqli_query($link, "SELECT CONTACT from thalilist where Thali='$thali'");
    $row = mysqli_fetch_row($sql);
    $sms_to = $row[0];
    $sms_body = "Thali $thali, You did not pickup your thali today.You have been fined Rs 200 for not treating maulas neamat with respect it deserves.";
    $sms_body = urlencode($sms_body);
    $result = file_get_contents("https://www.fast2sms.com/dev/bulkV2?authorization=$smsauthkey&route=v3&sender_id=TXTIND&message=$sms_body&language=english&flash=0&numbers=$sms_to");
  }
  echo ("<SCRIPT LANGUAGE='JavaScript'>
    window.alert('Fine of 200 added successfully');
    </SCRIPT>");
}

?>
<!DOCTYPE html>
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

          <h2 id="forms">Fine thalis that didn't Pickup</h2>

        </div>

      </div>



      <div class="row">

        <div class="col-lg-6">

          <div class="well bs-component">

            <form method="post" class="form-horizontal">

              <fieldset>


                <div class="form-group">

                  <label for="inputThalino" class="col-lg-2 control-label">Thali No</label>

                  <div class="col-lg-10">

                    <input type="text" class="form-control" id="inputThalino" placeholder="e.g. 508,37" name="thalino">

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

        <?php include('_bottomJS.php'); ?>
</body>

</html>
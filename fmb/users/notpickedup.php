<?php
include('header.php');
include('navbar.php');
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

<div class="card">
  <div class="card-body">
	<h2 class="mb-3">Fine thalis that didn't Pickup</h2>
	<form method="post" class="form-horizontal">
	  <div class="mb-3 row">
		<label for="inputThalino" class="col-2 control-label">Thali No</label>
		<div class="col-10">
		  <input type="text" class="form-control" id="inputThalino" placeholder="e.g. 508,37" name="thalino">
		</div>
	  </div>
	  <div class="mb-3 row">
		<div class="col-10 offset-2">
		  <button type="submit" class="btn btn-light">Submit</button>
		</div>
	  </div>
	</form>
  </div>
</div>

<?php include('footer.php'); ?>
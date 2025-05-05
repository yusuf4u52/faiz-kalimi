<?php
include('../users/connection.php');
include('../users/header.php');
require '../sms/_credentials.php';
include('call_api.php');
require '../users/_sendMail.php';

session_start();
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
if ($_POST) {
  $raw_data = $_POST;
  $_SESSION['mail'] = $_POST['email'];
  function sanitize($v)
  {
    return addslashes($v);
  }
  $data = array_map("sanitize", $raw_data);
  extract($data);
  $combined_name = $firstname . " " . $fathername . " " . $lastname;
  $sql = "INSERT INTO thalilist (
                                        `NAME`,
                                        `CONTACT`,
                                        `ITS_No`,
                                        `Full_Address`,
                                        `Email_ID`,
                                        `WhatsApp`
                                        )
                            VALUES (
                                    '$combined_name',
                                    '$mobile',
                                    '$its',
                                    '$address',
                                    '$email',
                                    '$whatsapp'
                                    )";
  if (!mysqli_query($link, $sql)) {
    if (substr_count(mysqli_error($link), "Duplicate") > 0) {
      echo ("<SCRIPT LANGUAGE='JavaScript'>
    window.alert('Your data already exist in the system and so you can login directly. You will now be redirected to the login page.')
    window.location.href='../users/login.php';
    </SCRIPT>");
    } else {
      echo mysqli_error($link);
    }
    exit;
  }
  mysqli_close($link);
  $msgvar = "Salaam " . $firstname . "bhai,<br><br>New Registration form for Faiz ul Mawaid il Burhaniyah thali has been successfully submitted.<br>
  <b>Please contact Kalimi Mohalla Jamaat Office to start your thali.</b><br><br>
  For any concerns mail kalimimohallapoona@gmail.com";
  sendEmail($email, 'New Registration Successful, Visit Faiz to start the thali', $msgvar, null);
  echo ("<SCRIPT LANGUAGE='JavaScript'>
    window.alert('Please contact Kalimi Mohalla Jamaat Office to start your thali. Address: Near Burhani Park, Kalimi Masjid, Yewlewadi, Office Time - 10AM to 12AM.')
    window.location.href='index.php';
    </SCRIPT>");
}
?>
<div class="fmb-content">
	<div class="container">
		<div class="row">
			<div class="col-12 offset-sm-2 col-sm-8 offset-lg-3 col-lg-6">
	<a href="/fmb/index.php"><img class="img-fluid mx-auto d-block my-3" src="../users/assets/img/logo.png"
		alt="Faiz ul Mawaid il Burhaniyah (Kalimi Mohalla - Poona)" width="390" height="157" /></a>
	  <h2 class="mb-3 text-center">Thaali Registration</h2>
				<div class="card">
					<div class="card-body">
		  <form class="form-horizontal" method="post" autocomplete="off">
			<div class="mb-3 row">
			  <label for="its" class="col-3 control-label">ITS No</label>
			  <div class="col-9">
				<input type="number" class="form-control" id="its" name="its" pattern="[0-9]{8}" required='required'>
			  </div>
			</div>
			<div class="mb-3 row">
			  <label for="firstname" class="col-3 control-label">First Name</label>
			  <div class="col-9">
				<input type="text" class="form-control" id="firstname" name="firstname" required='required'>
			  </div>
			</div>
			<div class="mb-3 row">
			  <label for="fathername" class="col-3 control-label">Father's Name</label>
			  <div class="col-9">
				<input type="text" class="form-control" id="fathername" name="fathername" required='required'>
			  </div>
			</div>
			<div class="mb-3 row">
			  <label for="lastname" class="col-3 control-label">Last Name</label>
			  <div class="col-9">
				<input type="text" class="form-control" id="lastname" name="lastname" required='required'>
			  </div>
			</div>
			<div class="mb-3 row">
			  <label for="address" class="col-3 control-label">Current Address</label>
			  <div class="col-9">
				<textarea class="form-control" rows="3" id="address" name="address" required></textarea>
				<p class="help-block "><small>(Please enter in this order- Flat No, Floor No, Bldg No, Society Name, Road, Nearest Landmark)</small></p>
			  </div>
			</div>
			<div class="mb-3 row">
			  <label for="mobile" class="col-3 control-label">Mobile Number</label>
			  <div class="col-9">
				<input type="number" class="form-control" id="mobile" name="mobile" pattern="[0-9]{10}" required='required'>
			  </div>
			</div>
			<div class="mb-3 row">
			  <label for="whatsapp" class="col-3 control-label">WhatsApp Number</label>
			  <div class="col-9">
				<input type="number" class="form-control" id="whatsapp" name="whatsapp" pattern="[0-9]{10}" required='required'>
			  </div>
			</div>
			<div class="mb-3 row">
			  <label for="email" class="col-3 control-label">Email Address</label>
			  <div class="col-9">
				<input type="email" class="form-control" id="email" name="email" pattern="^[_a-z0-9-]+(\.[_a-z0-9-]+)*@gmail.com$" required='required'>
				<p class="help-block "><small>(Only Gmail)</small></p>
			  </div>
			</div>
			<div class="mb-3 row">
			  <div class="col-9 offset-3">
				<button type="submit" class="btn btn-light" name='submit'>Submit</button>
			  </div>
			</div>
		  </form>
		</div> <!-- /container -->
				</div>
			</div>
		</div>
	</div>
</div>
<?php include('../users/footer.php'); ?>

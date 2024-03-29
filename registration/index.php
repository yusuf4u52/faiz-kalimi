<?php
include('../users/connection.php');
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
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="">
  <link rel="icon" href="/users/images/icon.png">

  <title>Thali registration form</title>

  <!-- Bootstrap core CSS -->
  <link href="assets/bootstrap.min.css" rel="stylesheet">

  <!-- Custom styles for this template -->
  <link href="assets/jumbotron-narrow.css" rel="stylesheet">

  <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
  <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
  <style type="text/css">
    body {
      /*background-image: url('assets/background.jpg');*/
      background-attachment: fixed;
    }

    .container {
      background-image: url(../users/images/icon-gray.png);
      background-size: 30px 30px;
      background-clip: border-box;
      padding-top: 1em;
      padding-bottom: 1em;
      border: 1px dotted #ddd;
      border-radius: 10px;
      -webkit-box-shadow: 0px 0px 30px 0px rgba(0, 0, 0, 0.78);
      -moz-box-shadow: 0px 0px 30px 0px rgba(0, 0, 0, 0.78);
      box-shadow: 0px 0px 30px 0px rgba(0, 0, 0, 0.78);
    }

    .required {
      color: red;
    }

    .font-size-13-px {
      font-size: 13px;
    }

    .color-brown {
      color: brown;
    }
  </style>
</head>

<body>

  <div class="container drop-shadow">
    <div class="header" style="text-align: center; vertical-align: middle; font-weight:20px">
      <h4 class="color-brown"><strong>Faiz ul Mawaid il Burhaniyah</strong></h4>
      <h5 class="color-brown">(Kalimi Mohalla)</h5>
      <h4 style="margin-top: 20px;"><strong>Thaali Registration</strong></h4>
    </div>

    <form method="post">
      <div class='col-xs-12'>

        <div class="row">
          <div class="form-group col-xs-6 col-md-3">
            <label for="its">ITS ID <a class="required">*</a></label>
            <input type="text" class="form-control" id="its" name="its" pattern="[0-9]{8}" required>
          </div>
        </div>

        <div class="row">
          <div class="form-group col-md-4">
            <label for="firstname">First Name <a class="required">*</a></label>
            <input type="text" class="form-control" id="firstname" name="firstname" required>
          </div>
          <div class="form-group col-md-4">
            <label for="fathername">Father's Name <a class="required">*</a></label>
            <input type="text" class="form-control" id="fathername" name="fathername" required>
          </div>
          <div class="form-group col-md-4">
            <label for="lastname">Last Name <a class="required">*</a></label>
            <input type="text" class="form-control" id="lastname" name="lastname" required>
          </div>
        </div>

        <div class="form-group">
          <label for="address">Current Address <a class="required">*</a></label>
          <!-- <input type="text" class="form-control" id="address1" name="address1" required>
              <input type="text" class="form-control" id="address2" name="address2" required style="margin-top : 5px">
              <input type="text" class="form-control" id="address3" name="address3" required style="margin-top : 5px"> -->

          <textarea class="form-control" rows="3" id="address" name="address" required></textarea>
          <p class="help-block "><em>(Please enter in this order-FLAT No, Floor No, Bldg No, SOCIETY Name, ROAD, Nearest LANDMARK)</em></p>
        </div>

        <div class="row">
          <div class="form-group col-md-6">
            <label for="mobile">Mobile Number <a class="required">*</a></label>
            <input type="tel" maxlength="10" class="form-control" id="mobile" name="mobile" pattern="[0-9]{10}" required>
          </div>
          <div class="form-group col-md-6">
            <label for="whatsapp">WhatsApp Number <a class="required">*</a></label>
            <input type="number" class="form-control" id="whatsapp" name="whatsapp" pattern="[0-9]{10}" required>
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email Address <a class="required">*</a></label>(only Gmail)
          <input type="email" class="form-control" id="Email" name="email" pattern="^[_a-z0-9-]+(\.[_a-z0-9-]+)*@gmail.com$" required>
        </div>

        <div class="form-group" style="text-align: center; vertical-align: middle; font-weight:20px;margin-top: 25px;">
          <button type="submit" class="btn btn-success">Next</button>
        </div>
      </div>
    </form>
  </div> <!-- /container -->
  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
</body>

</html>
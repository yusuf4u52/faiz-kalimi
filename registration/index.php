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
  $transport = ($transport == 'Yes') ? 'Transporter' : 'Pick Up';
  $occupation = ($occupation == 'Student') ? 'Student' : 'Working';
  // $datafromits = CallAPI($its,$header,$its_url);
  // if (!empty($datafromits)) {
  //   $datafromits_name = $datafromits['name'];
  //   $datafromits_jamaat = $datafromits['jamaat'];
  // } else {
  $datafromits_name = $firstname . " " . $fathername . " " . $lastname;
  $datafromits_jamaat = $watan;
  // }

  $sql = "INSERT INTO thalilist (
                                        `NAME`,
                                        `CONTACT`,
                                        `ITS_No`,
                                        `Full_Address`,
                                        `Email_ID`,
                                        `WATAN`,
                                        `Transporter`,
                                        `Occupation`
                                       ,`Gender`
                                       ,`Area`
                                       ,`WhatsApp`
                                       ,`College`
                                       ,`Field`
                                       ,`Permanent_Residence`
                                        )
                            VALUES (
                                    '$datafromits_name',
                                    '$mobile',
                                    '$its',
                                    '$address',
                                    '$email',
                                    '$datafromits_jamaat',
                                    '$transport',
                                    '$occupation'
                                    ,'$gender'
                                    ,'$area'
                                    ,'$whatsapp'
                                    ,'$college'
                                    ,'$field'
                                    ,'$permanent_residence'
                                    )";
  mysqli_query($link, $sql) or die(mysqli_error($link));
  mysqli_close($link);
  $msgvar = "Salaam " . $firstname . "bhai,<br><br>New Registration form for Faiz ul Mawaid il Burhaniyah thali has been successfully submitted.<br>
  <b>Please contact Saifuddinbhai on +919975305980 to activate your thali.</b><br><br>
  For any concerns mail kalimifaiz@gmail.com";
  sendEmail($email, 'New Registration Successful, Visit Faiz to activate the thali', $msgvar, null);
  echo ("<SCRIPT LANGUAGE='JavaScript'>
    window.alert('Form has been successfully submitted.You need to visit faiz office with xerox of ITS card and 8000 Hub to get the thali started. Address: Shop Near Gold Gym,Lane adjacent to Satyanand Hospital, Office Time - 9 to 11 AM.')
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
          <label>Gender <a class="required">*</a> : </label>
          <label class="radio-inline">
            <input type="radio" name="gender" value="Male" required />Male
          </label>
          <label class="radio-inline">
            <input type="radio" name="gender" value="Female" required />Female
          </label>
        </div>

        <hr />

        <div class="form-group">
          <label for="address">Current Address <a class="required">*</a></label>
          <!-- <input type="text" class="form-control" id="address1" name="address1" required>
              <input type="text" class="form-control" id="address2" name="address2" required style="margin-top : 5px">
              <input type="text" class="form-control" id="address3" name="address3" required style="margin-top : 5px"> -->

          <textarea class="form-control" rows="3" id="address" name="address" required></textarea>
          <p class="help-block "><em>(Please enter in this order-FLAT No, Floor No, Bldg No, SOCIETY Name, ROAD, Nearest LANDMARK)</em></p>
        </div>

        <div class="form-group">
          <label for="area">Area <a class="required">*</a></label>
          <select class="form-control" id="area" name="area" required>
            <option disabled selected value> -- select an option -- </option>
            <option value="Bhagyoday Nagar">Bhagyoday Nagar</option>
            <option value="Bhawani Peth">Bhawani Peth</option>
            <option value="Camp">Camp</option>
            <option value="City">City</option>
            <option value="Fakhri Hills">Fakhri Hills</option>
            <option value="Fatima Nagar">Fatima Nagar</option>
            <option value="Ghorpadi">Ghorpadi</option>
            <option value="Kondhwa">Kondhwa</option>
            <option value="Kondhwa Budruk">Kondhwa Budruk</option>
            <option value="Kothrud">Kothrud</option>
            <option value="Market Yard">Market Yard</option>
            <option value="Mitha Nagar">Mitha Nagar</option>
            <option value="NIBM">NIBM</option>
            <option value="Salunke Vihar">Salunke Vihar</option>
            <option value="Tilak Nagar">Tilak Nagar</option>
            <option value="Undri">Undri</option>
            <option value="Wanawadi">Wanawadi</option>
            <option value="(not present in the list)">(not present in the list)</option>
          </select>
        </div>

        <div class="form-group">
          <label>Transport Required <a class="required">*</a> : </label>
          <label class="radio-inline">
            <input type="radio" name="transport" value="Yes" required>Yes
          </label>
          <label class="radio-inline">
            <input type="radio" name="transport" value="No" required>No
          </label>
        </div>

        <div class="row">
          <div class="form-group col-md-6">
            <label for="mobile">Mobile Number <a class="required">*</a></label>
            <input type="tel" maxlength="10" class="form-control" id="mobile" name="mobile" pattern="[0-9]{10}" required>
          </div>
          <div class="form-group col-md-6">
            <label for="whatsapp">WhatsApp Number <a class="required">*</a></label>
            <input type="number" class="form-control" id="whatsapp" name="whatsapp" pattern="0{2}[0-9]{8,20}" required placeholder="00[CountryCode][MobileNumber]">
          </div>
        </div>

        <div class="form-group">
          <label for="email">Email Address <a class="required">*</a></label>(only Gmail)
          <input type="email" class="form-control" id="Email" name="email" pattern="^[_a-z0-9-]+(\.[_a-z0-9-]+)*@gmail.com$" required>
        </div>

        <hr />

        <div class="form-group">
          <label for="watan">Watan <a class="required">*</a></label>
          <input type="text" class="form-control" id="watan" name="watan" required>
        </div>
        <div class="form-group">
          <label for="permanent_residence">Permanent Residence Address</label>
          <input type="text" class="form-control" id="permanent_residence" name="permanent_residence">
          <p class="help-block">If different from Watan e.g. Kuwait, Dubai, Mumbai, Surat etc</p>
        </div>

        <hr />

        <div class="form-group font-size-13-px">
          <label>Occupation <a class="required">*</a> : </label>
          <label class="radio-inline">
            <input type="radio" name="occupation" value="Student" required>Student
          </label>
          <label class="radio-inline">
            <input type="radio" name="occupation" value="Working Professional" required>Working Professional
          </label>
        </div>

        <div class="row">
          <div class="form-group col-md-6">
            <label for="college">Full College/Company Name <a class="required">*</a></label>
            <input type="text" class="form-control" id="college" name="college" required>
          </div>
          <div class="form-group col-md-6">
            <label for="field">Field/Stream of Study <a class="required">*</a></label>
            <input type="text" class="form-control" id="field" name="field" required>
          </div>
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
<?php
include('header.php');
include('navbar.php');
include 'getHijriDate.php';

$today = getTodayDateHijri();
if ($_POST) {
  $_POST['address'] = str_replace("'", "", $_POST['address']);
  mysqli_query($link, "UPDATE thalilist set
                      CONTACT='" . $_POST["contact"] . "',
                      Full_Address='" . mysqli_real_escape_string($link, $_POST["address"]) . "',
                      ITS_No='" . $_POST["its"] . "',
                      wingflat='" . $_POST["wingflat"] . "',
                      society='" . $_POST["society"] . "',
                      WhatsApp='" . $_POST["whatsapp"] . "'
                      WHERE Thali = '" . $_SESSION['thali'] . "'") or die(mysqli_error($link));

  if ($_POST['society'] != $_SESSION['old_society']) {
    mysqli_query($link, "UPDATE thalilist set Transporter=NULL, sector = NULL, subsector = NULL where id ='" . $_SESSION['thaliid'] . "'");
    mysqli_query($link, "update change_table set processed = 1 where userid = '" . $_SESSION['thaliid'] . "' and `Operation` in ('Update Address') and processed = 0") or die(mysqli_error($link));
    mysqli_query($link, "INSERT INTO change_table (`Thali`,`userid`, `Operation`, `Date`) VALUES ('" . $_SESSION['thali'] . "','" . $_SESSION['thaliid'] . "', 'Update Address','" . $today . "')") or die(mysqli_error($link));
  }

  if(!empty($_POST['second_email'])) {
    if ($_POST['second_email'] != $_SESSION['old_semail']) {
      $checkemail = mysqli_query($link , "SELECT * FROM thalilist where Email_ID = '" . $_POST['second_email'] . "' OR `SEmail_ID` = '" . $_POST['second_email'] . "'") or die(mysqli_error($link));
      if ($checkemail->num_rows > 0) {
        $errormsg = 'registered';
      } else {
        mysqli_query($link, "UPDATE thalilist set SEmail_ID='" . $_POST["second_email"] . "' where Thali = '" . $_SESSION['thali'] . "'") or die(mysqli_error($link));
        if($_SESSION['email'] != $_POST['second_email']) {
          $first_email = $_SESSION['email'];
          $second_email = $_POST['second_email'];
          session_unset();
          session_destroy();
          $status = "Great! $second_email is registered successfully with us and $first_email is unregistered. Please login again.";
          echo '<script type="text/javascript">
            window.location = "http://localhost/fmb/index.php?status=' . $status . '";
          </script>';
          exit; 
        }
      }
    }
  }

  $msg = 'updated';

  unset($_SESSION['old_society']);
  unset($_SESSION['active']);
  unset($_SESSION['old_semail']);
}

$query = "SELECT * FROM thalilist where Thali = '" . $_SESSION['thali'] . "'";
$data = mysqli_fetch_assoc(mysqli_query($link, $query));

extract($data);
$_SESSION['old_society'] = $society;
$_SESSION['old_semail'] = $SEmail_ID;
$_SESSION['active'] = $Active;

?>
<div class="content mt-5">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-body">
            <h2 class="mb-3">Update info</h2>
            <h6 class="mb-3">Make sure you fill out all the required fields.</h6>
            <?php if (isset($errormsg) && $errormsg == 'registered') { ?>
              <div class="alert alert-danger" role="alert">
                <?php echo '<strong>'.$_POST['second_email'] . '</strong> already registered. Try another Gmail ID.'; ?>
              </div>
            <?php } elseif (isset($msg) && $msg == 'updated') { ?>
              <div class="alert alert-success" role="alert">
                Your details successfully updated.
              </div>
            <?php } ?>
            <form class="form-horizontal" method="post" autocomplete="off">
              <input type="hidden" name="Thali" value='<?php echo $_SESSION['thali']; ?>'>
              <input type="hidden" name="Email_ID" value='<?php echo $Email_ID; ?>'>
              <div class="mb-3 row">
                <label for="inputName" class="col-3 control-label">Primary Email</label>
                <div class="col-9">
                  <input type="email" class="form-control" id="inputEmail" placeholder="Email" required='required'
                    name="email" value='<?php echo $Email_ID; ?>' disabled>
                </div>
              </div>
              <div class="mb-3 row">
                <label for="inputName" class="col-3 control-label">Secondary Email</label>
                <div class="col-9">
                  <input type="email" class="form-control" id="inputEmail" placeholder="Email" required='required'
                    name="second_email" value='<?php echo (!empty($SEmail_ID) ? $SEmail_ID : ''); ?>' pattern="[a-z0-9._%+\-]+@gmail.com$">
                    <p class="help-block mb-0 text-danger text-end"><small>(Only Gmail)</small></p>
                </div>
              </div>
              <div class="mb-3 row">
                <label for="inputName" class="col-3 control-label">HOF Name</label>
                <div class="col-9">
                  <input type="text" class="form-control" id="inputName" placeholder="HOF Name" required='required'
                    name="name" value='<?php echo $NAME; ?>'>
                </div>
              </div>
              <div class="mb-3 row">
                <label for="inputIts" class="col-3 control-label">HOF ITS</label>
                <div class="col-9">
                  <input type="text" pattern="[0-9]{8}" class="form-control" id="inputIts" placeholder="HOF ITS"
                    required='required' name="its" value='<?php echo $ITS_No; ?>' title="Enter correct ITS ID">
                </div>
              </div>
              <div class="mb-3 row">
                <label for="inputContact" class="col-3 control-label">Mobile No.</label>
                <div class="col-9">
                  <input type="text" pattern="[0-9]{10}" class="form-control" id="inputContact" placeholder="Contact"
                    required='required' name="contact" value='<?php echo $CONTACT; ?>' title="Enter 10 digits">
                </div>
              </div>
              <div class="mb-3 row">
                <label for="inputwhatsapp" class="col-3 control-label">Whatsapp No.</label>
                <div class="col-9">
                  <input type="text" pattern="[0-9]{10}" class="form-control" id="inputwhatsapp" placeholder="WhatsApp"
                    required='required' name="whatsapp" value='<?php echo $WhatsApp; ?>'>
                </div>
              </div>
              <?php if ($yearly_hub >= 72000) { ?>
                <!-- <div class="mb-3 row">
                    <label class="col-3 control-label">Aata Required</label>
                    <div class="col-9">
                      <select class="form-control" name="aata" required='required'>
                        <option value='' <?php echo (is_null($aata)) ? "selected" : ""; ?>></option>
                        <option value='0' <?php echo ($aata == '0') ? "selected" : ""; ?>>0 kg</option>
                        <option value='5' <?php echo ($aata == '5') ? "selected" : ""; ?>>5 kg</option>
                        <?php if ($thalisize == 'Medium' || $thalisize == 'Large') { ?>
                          <option value='10' <?php echo ($aata == '10') ? "selected" : ""; ?>>10 kg</option>
                        <?php }
                        if ($thalisize == 'Large') { ?>
                          <option value='15' <?php echo ($aata == '15') ? "selected" : ""; ?>>15 kg</option>
                        <?php } ?>
                      </select>
                    </div>
                  </div> -->
                <!-- <div class="mb-3 row">
                    <label for="niyazdate" class="col-3 control-label">Niyaz Date</label>
                    <div class="col-9">
                      <input type="text" class="form-control" id="niyazdate" name="niyazdate" value='<?php echo $niyazdate; ?>' <?php echo !empty($niyazdate) ? "disabled" : ""; ?>>
                    </div>
                  </div>-->
              <?php } ?>
              <div class="mb-3 row">
                <label class="col-3 control-label">Wing/Flat</label>
                <div class="col-9">
                  <input type="text" class="form-control" placeholder="B1-1002" required='required' name="wingflat"
                    value='<?php echo $wingflat; ?>'>
                </div>
              </div>
              <div class="mb-3 row">
                <label for="inputContact" class="col-3 control-label">Society</label>
                <div class="col-9">
                  <select class="form-select" name="society" required='required'>
                    <option value=''>Select</option>
                    <?php
                    $society_list = mysqli_query($link, "SELECT distinct(society) FROM thalilist where society is not null order by society");
                    while ($society_option = mysqli_fetch_assoc($society_list)) {
                      ?>
                      <option value='<?php echo $society_option['society']; ?>' <?php echo ($society_option['society'] == $society) ? "selected" : ""; ?>>
                        <?php echo $society_option['society']; ?>
                      </option>
                      <?php
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="mb-3 row">
                <label for="inputAddress" class="col-3 control-label">Full Address</label>
                <div class="col-9">
                  <textarea class="form-control" id="inputAddress"
                    name="address"><?php echo $Full_Address; ?></textarea>
                </div>
              </div>
              <div class="mb-3 row">
                <div class="col-9 offset-3">
                  <button type="submit" class="btn btn-light" name='submit'>Submit</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include('footer.php'); ?>
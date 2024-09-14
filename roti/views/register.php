<?php
if_not_post_redirect('/data_entry');

$action = $_POST['action'];
$sabeel = $_POST['sabeel'];
$miqaat_id = $_POST['miqaat_id'];
if ($action === 'register') {
  $itsid = $_POST['itsid'];
  $full_name = $_POST['full_name'];
  $roti_count = $_POST['roti_count'];
  $contact = $_POST['contact'];

  $output = register_roti_maker($sabeel, $itsid, $full_name, $contact, $roti_count, $miqaat_id);
  // $msg = $output === 'negative' ? 'Ops! Registration failed. Try again.' :
  //   ($output === 'fail' ? 'Ops! Registered, but failed to take Roti count. Try again please.' : 'Thanks! your input is registered.');

  if ($output === 'negative') {
    setSessionData(TRANSIT_DATA, 'Sorry, failed to register, try again.');
  } else if ($output === 'fail') {
    auto_post_redirect('fetch_data', ['sabeel' => $sabeel, 'miqaat_id' => $miqaat_id]);
  } else {
    auto_post_redirect('acknowledge', ['sabeel' => $sabeel, 'miqaat_id' => $miqaat_id]);
  }
  //do_redirect_with_message('/data_entry', $msg);
}

if (isset($sabeel) && isset($miqaat_id)) {
  setAppData('sabeel', $sabeel);
  setAppData('miqaat_id', $miqaat_id);
} else {
  do_redirect('/data_entry');
}

function content_display()
{
  $sabeel = getAppData('sabeel');
  $miqaat_id = getAppData('miqaat_id');
  ?>
  <h2>Enter Roti Maker's Details</h2>
  <form method="post">
    <input type="hidden" value="register" name="action" id="action" />
    <input type="hidden" value="<?= $sabeel ?>" name="sabeel" id="sabeel">
    <input type="hidden" value="<?= $miqaat_id ?>" name="miqaat_id" id="miqaat_id">
    <div class='col-xs-12'>
      <div class="form-group">
        <label for="email">ITS ID</label>
        <input type="text" class="form-control" id="itsid" placeholder="Enter ITS ID" name="itsid" pattern="^[0-9]{8}$"
          required>
      </div>
      <div class="form-group">
        <label for="email">Full name</label>
        <input type="text" class="form-control" id="full_name" placeholder="Enter name" name="full_name" required>
      </div>
      <div class="form-group">
        <label for="email">Contact</label>
        <input type="text" class="form-control" id="contact" placeholder="Enter Mobile" name="contact" required>
      </div>
      <div class="form-group">
        <label for="email">Roti Packet Count (1 Packet = 4 Roti)</label>
        <select class="form-control" name="roti_count" id="roti_count">
          <?php for ($i = 1; $i < 26; $i++) {
            $value = $i == 1 ? "1 Packet" : "$i Packets";            
            echo "<option value='$i'>$value</option>";
          } ?>
        </select>
      </div>
      <div class="form-group" style="text-align: center; vertical-align: middle; font-weight:20px;margin-top: 25px;">
        <button type="submit" class="btn btn-success">Save</button>
      </div>
    </div>
  </form>
<?php } ?>
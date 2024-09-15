<?php

if (is_post()) {

  $miqaat_id = $_POST['miqaat_id'];
  $sabeel = $_POST['sabeel'];

  $rotimaker_result = get_rotimaker_by_sabeel($sabeel);
  if (!is_record_found($rotimaker_result)) {
    auto_post_redirect('register', ['sabeel' => $sabeel, 'miqaat_id' => $miqaat_id, 'action' => 'input']);
  } else {
    auto_post_redirect('fetch_data', ['sabeel' => $sabeel, 'miqaat_id' => $miqaat_id]);
  }

}

$result = get_current_miqaat();
if (!is_record_found($result)) {
  do_redirect_with_message('/error', 'No active miqaat for "Mohabbat Ni Roti". Please visit later.');
}
$miqaat_id = $result->data[0]['id'];
setAppData('miqaat_id' , $miqaat_id);

function content_display()
{
  $miqaat_id = getAppData('miqaat_id');
  ?>
  <h4>Sabeel Search</h4>
  <form action="data_entry" method="post">
  <input type="hidden" value="<?= $miqaat_id ?>" name="miqaat_id" id="miqaat_id">

    <div class='col-xs-12'>
      <div class="form-group">
        <label for="email">Sabeel Number (numbers only)</label>
        <input type="text" class="form-control" id="sabeel" placeholder="Enter sabeel number" name="sabeel"
          pattern="^[0-9]{3,5}$" required>
      </div>
      <div class="form-group" style="text-align: center; vertical-align: middle; font-weight:20px;margin-top: 25px;">
        <button type="submit" class="btn btn-success">Next</button>
      </div>
    </div>
  </form>
<?php } ?>

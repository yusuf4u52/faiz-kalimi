<?php

$result = get_current_miqaat();
if (!is_record_found($result)) {
  do_redirect_with_message('/error', 'No active miqaat. Please visit later.');
}
$miqaat_id = $result->data[0]['id'];
setAppData('miqaat_id', $miqaat_id);

function content_display()
{
  $miqaat_id = getAppData('miqaat_id');
  ?>
  <h6>Search Page</h6>
  <form action="search_sabeel" method="post">
    <input type="hidden" value="<?= $miqaat_id ?>" name="miqaat_id" id="miqaat_id">
    <div class="input-group mb-3">
      <input type="text" required class="form-control" name="hof_id" id="hof_id" placeholder="HOF ID"
        pattern="^[0-9]{8}$" aria-label="HOF ID" aria-describedby="button-addon2">
      <button class="btn btn-outline-secondary" type="submit" id="button-addon2">Search</button>
    </div>
  </form>
<?php } ?>

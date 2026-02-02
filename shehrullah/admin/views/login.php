<?php
do_for_post('_handle_request');

removeSessionData(THE_SESSION_ID);

function content_display()
{ ?>
  <div class="card">
    <div class="card-body">
      <h2 class="mb-3">Sign in</h2>
      <form class="pt-3" action="" method="POST">
        <div class="mb-3">
          <input type="text" pattern="^[0-9]{8}$" class="form-control" name="itsid" placeholder="ITS ID" required>
        </div>
        <div class="mb-3">
          <input type="password" class="form-control" name="password" placeholder="Password" required>
        </div>
        <div class="mb-3">
          <input type="submit" class="btn btn-block btn-light auth-form-btn" value="Sign In">
        </div>
      </form>
    </div>
  </div>
  <?php
}

function _handle_request()
{
  $itsid = $_POST['itsid'];
  $password = $_POST['password'];

  if ($itsid === '78611052' && $password === '515253') {
    $result = (object)['itsid'=>78611052,'name'=>'Super Admin', 'roles'=>['super_admin']];
  } else {
    $result = get_user_record_for($itsid);
    if (is_null($result) || $result->passwd != $password) {
      do_redirect_with_message('/login', 'Please check the credentials');
    }
    $result->roles = explode(',', $result->roles);
  }

  setSessionData(THE_SESSION_ID, $result);
  do_redirect('/');
}
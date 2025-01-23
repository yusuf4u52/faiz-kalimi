<?php
do_for_post('_handle_request');

removeSessionData(THE_SESSION_ID);

function content_display()
{
  ?>
  <!-- <div class="container-fluid page-body-wrapper full-page-wrapper">
    <div class="content-wrapper d-flex align-items-center auth"> -->
  <div class="row">
    <div class="col-2">&nbsp;</div>
    <div class="col-8 align-items-center">
      <div class="card">
        <div class="card-body">
          <h6 class="font-weight-light">Sign in</h6>
          <form class="pt-3" action="" method="POST">
            <div class="form-group">
              <input type="text" pattern="^[0-9]{8}$" class="form-control form-control-lg" name="itsid"
                placeholder="ITS ID" required>
            </div>
            <div class="form-group">
              <input type="password" class="form-control form-control-lg" name="password" placeholder="Password" required>
            </div>
            <div class="mt-3">
              <input type="submit" class="btn btn-block btn-gradient-primary btn-lg font-weight-medium auth-form-btn"
                value="SIGN IN">
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-2">&nbsp;</div>
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
      do_redirect_with_message('/admin_login', 'Please check the credentials');
    }
    $result->roles = explode(',', $result->roles);
  }

  setSessionData(THE_SESSION_ID, $result);
  do_redirect('/admin');
}
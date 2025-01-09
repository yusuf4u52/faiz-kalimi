<?php
$admin_users = ['78611052'=>'5253','30359589'=>'1234', '30362851'=>'Ammar2851'];

if( is_post() ) {
    $its_id = $_POST['its_id'];
    $password = $_POST['password'];
    foreach($admin_users as $k=>$v) {
        if( $k == $its_id && $password == $v ) {
            setSessionData(THE_SESSION_ID, ['ITSID'=>$k]);
            do_redirect('');
        }
    }

    setSessionData(TRANSIT_DATA, 'Oops! Check your credentials.');
}

function content_display()
{
?>
<form method="post">
    <div class='col-xs-12'>
        <div class="mb-3 row">
            <label for="its_id" class="col-sm-3 col-form-label">ITS ID</label>
            <div class="col-sm-9">
                <input type="text" pattern="^[0-9]{8}$" required class="form-control" id="its_id" name="its_id">
            </div>
        </div>
        <div class="mb-3 row">
            <label for="full_name" class="col-sm-3 col-form-label">Password</label>
            <div class="col-sm-9">
                <input type="text" required class="form-control" id="password" name="password">
            </div>
        </div>

        <div class="form-group" style="font-weight:20px;margin-top: 25px;">
            <button type="submit" class="btn btn-success">Save</button>
        </div>
    </div>
</form>
<?php } ?>
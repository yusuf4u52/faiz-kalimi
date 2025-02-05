<?php
if( !is_user_a(SUPER_ADMIN) ) {
    do_redirect_with_message('/home' , 'Redirected as tried to access unauthorized area.');
}


$itsid = getAppData('arg1');
$action = is_null($itsid) ? 'list' : ($itsid > 0 ? 'edit' : 'new');
setAppData('action', $action);

$user_data = (object) [];
if ($itsid > 0) {
    $result = get_user_record_for($itsid);
    if (is_null($result)) {
        do_redirect_with_message('/users', "ITS ID ($itsid) not found.");
    }
    $user_data = $result;
}

setAppData('user_data', $user_data);

do_for_post('_handle_add_user');

function content_display()
{
    $itsid = getAppData('arg1');
    $action = getAppData('action');
    switch ($action) {
        case 'list':
            show_user_list();
            break;
        case 'edit':
        case 'new':
            show_input_user();
            break;
    }
}

function show_input_user()
{
    $itsid = getAppData('arg1');
    $action = getAppData('action');

    $user_data = getAppData('user_data');
    ?>
    <div class="card">
        <div class="card-body">
            <form action="" method="post">
                <input type='hidden' name='req_itsid' value="<?= $itsid ?>" />
                <div class="form-group row">
                    <label for="itsid" class="col-sm-2 col-form-label">ITS ID</label>
                    <div class="col-sm-10">
                        <?php if ($itsid > 0) { ?>
                            <input type='hidden' name='itsid' value="<?= $user_data->itsid ?>" />
                            <?= $user_data->itsid ?>
                        <?php } else { ?>
                            <input type='text' pattern="^[0-9]{8}$" class='form-control' name='itsid'
                                value="<?= $user_data->itsid ?? '' ?>" required />
                        <?php } ?>

                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Name</label>
                    <div class="col-sm-10">
                        <input type='text' class='form-control' name='name' value="<?= $user_data->name ?? '' ?>"
                            required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="full_niyaz" class="col-sm-2 col-form-label">Password</label>
                    <div class="col-sm-10">
                        <?php if ($itsid > 0) { ?>
                            <input type='hidden' name='passwd' value="<?= $user_data->passwd ?>" />
                            <input type='text' class='form-control' name='passwd_new' />
                        <?php } else { ?>
                            <input type='text' class='form-control' name='passwd' required />
                        <?php } ?>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="roles" class="col-sm-12 col-form-label">Suggested Roles: <?= implode(' , ' , ROLE) ?></label>                    
                </div>
                <div class="form-group row">
                    <label for="roles" class="col-sm-2 col-form-label">Roles (Comma separated)</label>
                    <div class="col-sm-10">
                        <input type='text' class='form-control' name='roles' value="<?= $user_data->roles ??'' ?>" required />
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-10">
                        <button type="submit" class="btn btn-gradient-success btn-rounded btn-fw">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function show_user_list()
{
    $user_data = get_user_records();
    $url = getAppData('BASE_URI');
    ?>
    <div class="card">
        <div class="card-header row">
            <div class="col-6">User Data</div>
            <div class="col-6"><a href="<?= $url ?>/users/0" class="btn btn-gradient-success btn-rounded btn-fw">Add New</a></div>
            </di>
            <div class="card-body">
                <?= __display_user_list([$user_data]) ?>
            </div>
        </div>
    </div>
    <?php
}

function __display_user_list($data)
{
    $records = $data[0];
    util_show_data_table($records, [
        '__show_row_sequence' => 'S/no',
        'itsid' => 'ITS ID',
        'name' => 'Name',
        'roles' => 'Roles',
        '__show_link2markaz_list' => 'Action'
    ]);
}

function __show_link2markaz_list($row, $index)
{
    $itsid = $row->itsid;
    $uri = getAppData('BASE_URI');
    return "<a href='$uri/users/$itsid' class='btn btn-gradient-success btn-rounded btn-fw'>Edit</a>";
}


function _handle_add_user()
{
    $req_itsid = $_POST['req_itsid'];

    if ($req_itsid > 0) {
        $itsid = $_POST['itsid'];
        $name = $_POST['name'];
        $passwd = $_POST['passwd'];
        $roles = $_POST['roles'];
        $passwd_new = $_POST['passwd_new'];
        if (strlen(trim($passwd_new)) > 0) {
            $passwd = $passwd_new;
        }
    } else {
        $itsid = $_POST['itsid'];
        $name = $_POST['name'];
        $passwd = $_POST['passwd'];
        $roles = $_POST['roles'];
    }

    $msg = 'User added successfully!';
    $result = add_user_record($itsid, $name, $passwd, $roles);
    if (!is_null($result)) {
        $msg = 'Failed with error: ' . $result;
    }
    do_redirect_with_message('/users', $msg);
}
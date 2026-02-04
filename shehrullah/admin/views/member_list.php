<?php
if_not_post_redirect('/home');

if (!is_user_a(SUPER_ADMIN, RECEPTION, DATA_ENTRY)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}


do_for_post('_handle_post');

function _handle_post()
{
    $sabeel = $_POST['sabeel'];
    setAppData('sabeel', $sabeel);

    $action = $_POST['action'] ?? '';
    if ('register' == $action) {
        _handle_register_member();
    }

    $sabeel_data = get_thaalilist_data($sabeel);
    if (is_null($sabeel_data)) {
        do_redirect_with_message('\home', "Sabeel number ($sabeel) not found. Get the sabeel added to thaali data.");
    }

    setAppData('sabeel_data', $sabeel_data);

    $hof_id = $sabeel_data->ITS_No;
    setAppData('hof_id', $hof_id);

    $attendees_data = get_members_for($hof_id);
    setAppData('attendees_data', $attendees_data);

    
    if ('enable_member' == $action) {
        _handle_enable_member();
    }

}

function _handle_register_member()
{
    $hof_id = $_POST['hof_id'];
    $sabeel = $_POST['sabeel'];

    $its_id = $_POST['its_id'];
    $full_name = $_POST['full_name'];
    //$gender = $_POST['gender'];
    $age = $_POST['age'];

    $gender_post = $_POST['gender'];
    $misaq_post = $_POST['misaq'];

    $gender = 'Male';
    if( strtolower($gender_post)  === 'f' ) {
        $gender = 'Female';
    }
    $misaq = 'Done';
    if( strtolower($misaq_post)  === 'n' ) {
        $misaq = 'Not Done';
    }

    $add_member_result = add_family_member($hof_id, $sabeel, $its_id, 
    $full_name, $gender, $age, $misaq);
    if (is_record_found($add_member_result)) {
        setSessionData(TRANSIT_DATA, 'Mehman added successfully!');
    } else {
        setSessionData(TRANSIT_DATA, "Oops! failed to add. Seems ITS ($its_id) is already used.");
    }
}

function show_add_member_form()
{
    $hof_id = $_POST['hof_id'];
    $sabeel = $_POST['sabeel'];
    ?>
    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-6">
                    <h2 class="mb-3">Add Mehman for sabeel #:<?= $sabeel ?></h2>
                </div>
                <!--<div class="col-6 text-end">
                    <form method="post" action="search_sabeel">
                        <input type="hidden" value="<?= $hof_id ?>" name="hof_id" id="hof_id">
                        <input type="hidden" name="sabeel" value="<?= $sabeel ?>">
                        <button type="submit" class="btn btn-light mb-3">Back</button>
                    </form>
                </div>-->
            </div>
            <form method="post">
                <input type="hidden" value="register" name="action" id="action" />
                <input type="hidden" value="<?= $hof_id ?>" name="hof_id" id="hof_id">
                <input type="hidden" name="sabeel" value="<?= $sabeel ?>">
                <div class="mb-3 row">
                    <label for="staticEmail" class="col-4 form-label">HOF ID</label>
                    <div class="col-8">
                        <input type="text" readonly class="form-control" id="staticEmail" value="<?= $hof_id ?>">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="its_id" class="col-4 form-label">ITS ID</label>
                    <div class="col-8">
                        <input type="text" pattern="^[0-9]{8}$" required class="form-control" id="its_id" name="its_id">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="full_name" class="col-4 form-label">Full name</label>
                    <div class="col-8">
                        <input type="text" required class="form-control" id="full_name" name="full_name">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="gender" class="col-4 form-label">Gender (M or F)</label>
                    <div class="col-8">
                        <input type="text" pattern="^(?:m|M|f|F)$" required 
                        class="form-control" id="gender" name="gender" value="<?= $_POST['gender'] ?? '' ?>">                    
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="age" class="col-4 form-label">Age</label>
                    <div class="col-8">
                        <input type="text" pattern="^[0-9]{1,2}$" required class="form-control" id="age" name="age">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="misaq" class="col-4 form-label">Misaq (Y or N)</label>
                    <div class="col-8">
                    <input type="text" pattern="^(?:y|Y|n|N)$" required 
                    class="form-control" id="misaq" name="misaq" value="<?= $_POST['misaq'] ?? '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-light">Save</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}


function _handle_enable_member()
{
    $attendees_data = getAppData('attendees_data');
    $member_count = 0;
    foreach ($attendees_data as $attendee) {
        $its_id = $attendee->its_id;
        $attendance_type = $_POST["attendance_type_for_$its_id"] ?? 'N';
        if ($attendance_type === "Y") {
            allow_members_for_shehrullah($its_id);
            $member_count++;
        }
    }
    if ($member_count > 0) {
        do_redirect_with_message('\home', "$member_count members enabled for shehrullah. Ask the mumin to fill the form again.");
    }
}

function content_display() {
    $action = $_POST['action'] ?? '';
    if ('entry' == $action) {
        show_add_member_form();
    } else {
        show_member_list();
    }
}

function show_member_list()
{
    $attendees_data = getAppData('attendees_data');
    $sabeel = getAppData('sabeel');
    $url = getAppData('BASE_URI');
    $hof_id = getAppData('hof_id');
    ?>
    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-6">
                    <h2 class="mb-3">Add Member for sabeel #: <?= $sabeel ?></h2>
                </div>
                <div class="col-6 text-end">
                    <form class="forms-sample mb-3" action="" method="POST">
                        <input type="hidden" name="action" value="entry">
                        <input type="hidden" name="hof_id" value="<?= $hof_id ?>">
                        <input type="hidden" name="sabeel" value="<?= $sabeel ?>">
                        <button type="submit" class="btn btn-light">New</button>
                    </form>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <p class="mb-3"> Select the member and submit </p>
                    <form class="forms-sample" action="" method="POST">
                        <input type="hidden" name="action" value="enable_member">
                        <input type="hidden" name="sabeel" value="<?= $sabeel ?>">
                        <div class="form-group row">
                            <?php __display_family_list([$attendees_data]) ?>
                        </div>
                        <button type="submit" class="btn btn-light">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function __display_family_list($data)
{
    //case when sa.masalla is null then '' else sa.masalla end as masalla, 
// case when sa.attendance_type is null then 'Yes' else sa.attendance_type end as attendance_type,
// case when sa.chair_preference is null then 'No' else sa.chair_preference end as chair_preference,
// m.its_id,m.full_name,m.age,m.gender
    $records = $data[0];
    util_show_data_table($records, [
        '__show_attends_checkbox' => 'Select',
        'its_id' => 'ITS ID',
        'hof_id' => 'HOF ID',
        'full_name' => 'Name',
    ]);
}

function __show_attends_checkbox($row, $index)
{
    $itsid = $row->its_id;
    $attendance_type = '';

    return "
        <div class='form-check form-check-flat form-check-primary'>                            
                <label class='form-check-label'><input class='form-check-input' type='checkbox' name='attendance_type_for_$itsid' value='Y'></label>
        </div>
    ";
}
<?php
if_not_post_redirect('/data_entry');

$action = $_POST['action'];
$sabeel = $_POST['sabeel'];
$miqaat_id = $_POST['miqaat_id'];
$hof_id = $_POST['hof_id'];

if ($action === 'register') {
    $its_id = $_POST['its_id'];
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];


    $add_member_result = add_family_member($hof_id, $sabeel, $its_id, $full_name, $gender, $age);

    if (is_record_found($add_member_result)) {
        add_attendance_for($its_id, $hof_id, $miqaat_id);
        setSessionData(TRANSIT_DATA, 'Member added successfully!');
        auto_post_redirect('search_sabeel', ['hof_id' => $hof_id, 'miqaat_id' => $miqaat_id]);
    } else {
        setSessionData(TRANSIT_DATA, 'Oops! failed to add the member.');
    }

    //   $output = register_roti_maker($sabeel, $itsid, $full_name, $contact, $roti_count, $miqaat_id);
//   // $msg = $output === 'negative' ? 'Ops! Registration failed. Try again.' :
//   //   ($output === 'fail' ? 'Ops! Registered, but failed to take Roti count. Try again please.' : 'Thanks! your input is registered.');

    //   if ($output === 'negative') {
//     setSessionData(TRANSIT_DATA, 'Sorry, failed to register, try again.');
//   } else if ($output === 'fail') {
//     auto_post_redirect('fetch_data', ['sabeel' => $sabeel, 'miqaat_id' => $miqaat_id]);
//   } else {
//     auto_post_redirect('acknowledge', ['sabeel' => $sabeel, 'miqaat_id' => $miqaat_id]);
//   }
//   //do_redirect_with_message('/data_entry', $msg);
}

// if (isset($sabeel) && isset($miqaat_id)) {
//   setAppData('sabeel', $sabeel);
//   setAppData('miqaat_id', $miqaat_id);
// } else {
//   do_redirect('/data_entry');
// }

function content_display()
{
    $sabeel = $_POST['sabeel'];
    $miqaat_id = $_POST['miqaat_id'];
    $hof_id = $_POST['hof_id'];
    ?>
    <h6>Add Family Member</h6>
    <form method="post">
        <input type="hidden" value="register" name="action" id="action" />
        <input type="hidden" value="<?= $sabeel ?>" name="sabeel" id="sabeel">
        <input type="hidden" value="<?= $miqaat_id ?>" name="miqaat_id" id="miqaat_id">
        <input type="hidden" value="<?= $hof_id ?>" name="hof_id" id="hof_id">

        <div class='col-xs-12'>
            <div class="mb-3 row">
                <label for="staticEmail" class="col-sm-4 col-form-label">HOF ID</label>
                <div class="col-sm-8">
                <input type="text" readonly class="form-control-plaintext" id="staticEmail" value="<?= $hof_id ?>">                
                </div>
            </div>
            <div class="mb-3 row">
                <label for="its_id" class="col-sm-4 col-form-label">ITS ID</label>
                <div class="col-sm-8">
                    <input type="text" pattern="^[0-9]{8}$" required class="form-control" id="its_id" name="its_id">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="full_name" class="col-sm-4 col-form-label">Full name</label>
                <div class="col-sm-8">
                    <input type="text" required class="form-control" id="full_name" name="full_name">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="gender" class="col-sm-4 col-form-label">Gender</label>
                <div class="col-sm-8">
                <select class="form-select" name="gender" id="gender">
                    <option>Male</option>
                    <option>Female</option>
                </select>
                </div>
            </div>
            <div class="mb-3 row">
                <label for="age" class="col-sm-4 col-form-label">Age</label>
                <div class="col-sm-8">
                    <input type="text"  pattern="^[0-9]{1,2}$" required class="form-control" id="age" name="age">
                </div>
            </div>
            
            <div class="form-group" style="font-weight:20px;margin-top: 25px;">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </div>
    </form>
<?php } ?>

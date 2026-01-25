<?php
$enc_hof_id = getAppData('arg1');
$hof_id = do_decrypt($enc_hof_id);
//
if (is_null($hof_id) || !is_numeric($hof_id)) {
    do_redirect_with_message('/input-sabeel', 'Invalid request. Try again.');
}
setAppData('hof_id', $hof_id);

if ($hof_id == 0) {
    $mumineen_data = [];
} else {
    $mumineen_data = get_attendees_data_for_nonsab($hof_id, $hijri_year, false);
}

if (is_null($mumineen_data)) {
    do_redirect("/get2post?url=/nonsab_register_2/$enc_hof_id&its_id=$hof_id&action=hof");
}

setAppData('mumineen_data', $mumineen_data);

do_for_post('_handle_form_submission');

function content_display()
{
    $mumineen_data = getAppData('mumineen_data');
    $hof_id = getAppData('hof_id');
    $uri = getAppData('BASE_URI');

    $hijri_year = get_current_hijri_year();

    $whatsapp = '';
    $pirsa = 'N';
    $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
    if( ! is_null($takhmeen_data) ) {
        $whatsapp = $takhmeen_data->whatsapp;
        $pirsa = $takhmeen_data->pirsa_count > 0 ? 'Y' : 'N';
    }

    $enc_hof_id = getAppData('arg1');

    ?>
    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-8">
                    <h2 class="mb-1">Create Family</h4>
                    <p><small>Add all your family member using 'Add Member' then submit this page</small></p>
                </div>
                <div class="col-4 text-end">
                    <a href="<?= $uri ?>/nonsab_register_2/<?= $enc_hof_id ?>" class="btn btn-light">Add Member</a>
                </div>
            </div>
            <h5 class="mb-3"> Select the member and submit to proceed</h5>
            <form class="forms-sample" action="" method="POST">
                <div class="row mb-3">
                    <?php __display_family_list([$mumineen_data]) ?>
                </div>
                <?php if ($hof_id > 0) { ?>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="pirsa" value='Y' <?= ($pirsa == 'Y' ? 'checked' : '') ?>> 
                        <label class="form-check-label"></label>Select if pirsa required</label>
                    </div>
                    <div class="row mb-3">
                        <label for="whatsapp" class="col-3 form-label">Whatsapp Number</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" pattern="^[0-9]{10,13}$" id="whatsapp" name="whatsapp" placeholder="WhatsApp Number" required value="<?=$whatsapp?>">
                        </div>
                    </div>
                    <input type="hidden" name="action" value="register_for_shehrullah">
                    <input type="hidden" name="hof_id" value="<?= $hof_id ?>">
                    <button type="submit" class="btn btn-light">Submit</button>
                <?php } ?>
            </form>
        </div>
    </div>
    <?php
}

function __display_family_list($data)
{
    $records = $data[0];
    util_show_data_table($records, [
        '__show_attends_checkbox' => 'Attends?',
        '__show_chair_checkbox' => 'Chair?',
        'its_id' => 'ITS ID',
        'full_name' => 'Name',
        //'__show_delete' => 'Delete'
    ]);
}

function __show_attends_checkbox($row, $index)
{
    $itsid = $row->its_id;
    $attendance_type = $row->attendance_type ?? '';

    return "<div class='form-check'>                            
        <input class='form-check-input' type='checkbox' name='attendance_type_for_$itsid' value='Y' " . ($attendance_type == 'Y' ? 'checked' : '') . ">
    </div>";
}

function __show_chair_checkbox($row, $index)
{
    $itsid = $row->its_id;
    $chair_preference = $row->chair_preference ?? '';

    return "<div class='form-check'>                            
        <input class='form-check-input' type='checkbox' name='chair_preference_for_$itsid' value='Y' " . ($chair_preference == 'Y' ? 'checked' : '') . ">
    </div>";
}

function _handle_form_submission()
{
    $hijri_year = get_current_hijri_year();
    $mumineen_data = getAppData('mumineen_data');

    // $sabeel_data = getAppData('sabeel_data');
    // $sabeel = $sabeel_data->Thali;
    // $hof_id = $sabeel_data->ITS_No;

    $action = $_POST['action'] ?? '';
    $venue = 'kalimi_masjid';
    $family_hub = 0;
    $pirsa_count = 0;
    $chair_count = 0;
    $parking_count = 0;
    $niyaz_type = 'family';

    $markaz_data = get_shehrullah_data_for($hijri_year);
    if (is_null($markaz_data)) {
        $markaz_data = (object) ['per_kid_niyaz' => 0, 'zero_hub_age' => 0, 'half_hub_age' => 0, 'family_niyaz' => 0]; //Empty object
    }

    $hof_id = $_POST['hof_id'];

    foreach ($mumineen_data as $attendees_data_record) {
        $itsid = $attendees_data_record->its_id;

        $attendance_type = $_POST["attendance_type_for_$itsid"] ?? 'N';
        $chair_preference = $_POST["chair_preference_for_$itsid"] ?? 'N';

        if ($attendance_type === 'Y') {
            $family_hub += get_hub_for_age($attendees_data_record->age, $markaz_data);
        }

        if ($chair_preference === 'Y') {
            $chair_count++;
        }

        $success = add_shehrullah_attendees(
            $hijri_year,
            $itsid,
            $hof_id,
            $attendance_type,
            $chair_preference
        );
        if (!$success) {
            setSessionData(TRANSIT_DATA, 'Failed to record data for ' . $itsid);
        }
    }

    $whatsapp = $_POST['whatsapp'];
    $pirsa = $_POST['pirsa'] ?? 'N';
    if ($pirsa === 'Y') {
        $pirsa_count = 1;
    }

    $update_input_change_result = add_shehrullah_takhmeen(
        $hijri_year,
        $hof_id,
        $family_hub,
        $pirsa_count,
        $chair_count,
        $parking_count,
        $venue,
        $whatsapp,
        0
    );

    if (!$update_input_change_result) {
        setSessionData(TRANSIT_DATA, 'Ops! something went wrong');
    }

    $enc_hof_id = getAppData('arg1');
    do_redirect("/nonsab_register_3/$enc_hof_id");
}


<?php
initial_processing();

do_for_post('_handle_form_submit');

function initial_processing()
{
    //Extract URI param and find sabeel
    $en_sabeel = getAppData('arg1');
    $sabeel = do_decrypt($en_sabeel);

    $sabeel_data = get_thaalilist_data($sabeel);
    if (is_null($sabeel_data)) {
        do_redirect_with_message('/input-sabeel', 'No records found for input ' . $sabeel . '. Enter correct sabeel number or HOF ITS.');
    }

    if( $sabeel_data->sector == 7 || $sabeel_data->sector == 13 ) {
        do_redirect_with_message('/input-sabeel', 'Error: Belongs to Hatimi Hills Markaz.');
    }
        
    setAppData('sabeel_data', $sabeel_data);

    $hof_id = $sabeel_data->ITS_No;    
    setAppData('hof_id', $hof_id);

    $hijri_year = get_current_hijri_year();
    setAppData('hijri_year', $hijri_year);

    // $data = getClearanceData($hof_id);
    // if( is_null($data) ) {
    //     do_redirect_with_message('/input-sabeel', "Your clearance process is pensing. Please visit jamaat office.");
    // }

    $attendees_data = get_attendees_data_for($hof_id, $hijri_year, false);
    if (is_null($attendees_data)) {
        do_redirect_with_message('/input-sabeel', 'Error: Seems your ITS (' . $hof_id . ') belong to other mohalla. Please contact jamaat office.');
    }

    setAppData('attendees_data', $attendees_data);
}

function _handle_form_submit()
{
    $hijri_year = getAppData('hijri_year');
    $attendees_data = getAppData('attendees_data');

    $sabeel_data = getAppData('sabeel_data');
    $sabeel = $sabeel_data->Thali;
    $hof_id = $sabeel_data->ITS_No;

    $action = $_POST['action'] ?? '';
    $venue = 'kalimi_masjid';
    $family_hub = 0;
    $pirsa_count = 0;
    $chair_count = 0;
    $parking_count = 0;
    $niyaz_type = 'family';

    if ('save_now' == $action) {
        $markaz_data = get_shehrullah_data_for($hijri_year);
        if (is_null($markaz_data)) {
            $markaz_data = (object) ['per_kid_niyaz' => 0, 'zero_hub_age' => 0, 'half_hub_age' => 0, 'family_niyaz' => 0]; //Empty object
        }

        foreach ($attendees_data as $attendees_data_record) {
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

        // $year,
        // $hof_id,
        // $family_hub,
        // $pirsa_count,
        // $chair_count,
        // $parking_count,
        // $venue

        $update_input_change_result = add_shehrullah_takhmeen(
            $hijri_year,
            $hof_id,
            $family_hub,
            $pirsa_count,
            $chair_count,
            $parking_count,
            $venue,
            $whatsapp,
            $sabeel
        );

        if (!$update_input_change_result) {
            setSessionData(TRANSIT_DATA, 'Ops! something went wrong');
        }

        $arg1 = getAppData('arg1');
        do_redirect("/print-form/$arg1");
    }

}


function content_display()
{
    //$hof_id = getAppData('hof_id');
    $attendees_data = getAppData('attendees_data');
    $hijri_year = get_current_hijri_year();

    $sabeel_data = getAppData('sabeel_data');
    $sabeel = $sabeel_data->Thali;
    $hof_id = $sabeel_data->ITS_No;

    $whatsapp = $sabeel_data->WhatsApp ?? '';
    $pirsa = 'N';

    $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
    if( ! is_null($takhmeen_data) ) {
        $whatsapp = $takhmeen_data->whatsapp;
        $pirsa = $takhmeen_data->pirsa_count > 0 ? 'Y' : 'N';
    }
    ?>
    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Shehrullah Registration</h4>
                    <p class="card-description"> Select the attendess and enter the required details. </p>
                    <form class="forms-sample" method="POST">
                        <input type="hidden" name="sabeel" value="<?= $sabeel ?>">
                        <input type="hidden" name="hof_id" value="<?= $hof_id ?>">
                        <input type="hidden" name="action" value="save_now">

                        <div class="form-group row">
                            <?php __display_family_list([$attendees_data]) ?>
                        </div>
                        <div class="form-check form-check-flat form-check-primary">
                            <label class="form-check-label">
                                <input type="checkbox" class="form-check-input" name="pirsa" value='Y' <?= $pirsa == 'Y' ? 'checked' : '' ?>> Select for pirsa </label>
                        </div>
                        <div class="form-group row">
                            <label for="whatsapp" class="col-sm-3 col-form-label">Whatsapp Number</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" pattern="^[0-9]{10,13}$" id="whatsapp"
                                    name="whatsapp" placeholder="WhatsApp Number" value='<?= $whatsapp ?>' required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gradient-primary me-2">Submit</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Chair Information -->
    <div class="modal fade" id="chairInfoModal" tabindex="-1" role="dialog" aria-labelledby="chairInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="chairInfoModalLabel">Chair Arrangement Information</h5>
                    <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" onclick="closeChairModal()">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Chairs will not be allowed in Masjid, Rahat block for gents is in SEHEN and for ladies in MAWAID</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeChairModal()">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showChairModal() {
            const modal = document.getElementById('chairInfoModal');
            // Bootstrap 5
            if (typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modal).show();
            }
            // Bootstrap 4 / jQuery
            else if (typeof $ !== 'undefined' && $.fn.modal) {
                $(modal).modal('show');
            }
            // Fallback
            else {
                alert('Chairs will not be allowed in Masjid, Rahat block for gents is in SEHEN and for ladies in MAWAID');
            }
        }

        function closeChairModal() {
            const modal = document.getElementById('chairInfoModal');
            // Bootstrap 5
            if (typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getInstance(modal)?.hide();
            }
            // Bootstrap 4 / jQuery
            else if (typeof $ !== 'undefined' && $.fn.modal) {
                $(modal).modal('hide');
            }
            // Fallback cleanup
            else {
                modal.classList.remove('show');
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[name^="chair_preference_for_"]').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        showChairModal();
                    }
                });
            });
        });
    </script>
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
        '__show_attends_checkbox' => 'Attends?',
        '__show_chair_checkbox' => 'Chair?',
        'full_name' => 'Name',
    ]);
}

function __show_attends_checkbox($row, $index)
{
    $itsid = $row->its_id;
    $attendance_type = $row->attendance_type ?? '';

    return "
        <div class='form-check form-check-flat form-check-primary'>                            
                <label class='form-check-label'><input class='form-check-input' type='checkbox' name='attendance_type_for_$itsid' value='Y' " . ($attendance_type == 'Y' ? 'checked' : '') . "></label>
        </div>
    ";

    //return "<input class='form-check-input' type='checkbox' name='attendance_type_for_$itsid' value='Y' " . ($attendance_type == 'Y' ? 'checked' : '') . ">";
}

function __show_chair_checkbox($row, $index)
{
    // $itsid = $row->ITS_ID;
    // $chair_preference = $row->chair_preference ?? '';
    $itsid = $row->its_id;
    $chair_preference = $row->chair_preference ?? '';

    return "<div class='form-check form-check-flat form-check-primary'>                            
    <label class='form-check-label'><input class='form-check-input' type='checkbox' name='chair_preference_for_$itsid' value='Y' " . ($chair_preference == 'Y' ? 'checked' : '') . "></label>
</div>";

    //return "<input class='form-check-input' type='checkbox' name='chair_preference_for_$itsid' value='Y' " . ($chair_preference == 'Y' ? 'checked' : '') . ">";
}

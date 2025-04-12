<?php

do_for_post('_handle_form_submission');

function content_display()
{
    ?>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Vajebaat Slot Registration</h4>
            <p class="card-description">Please enter HOF ID to proceed for vajebaat slots booking</p>
        </div>
        <div class="card-body">
            <form method="post" action="" class="forms-sample">
                <input type="hidden" name="action" value="nonsab_register"/>
                <div class="form-group">
                    <label class="col-sm-3 col-form-label">HOF ID (Numbers only)</label>
                    <div class="input-group col-xs-12">
                        <input type="text" class="form-control" placeholder="HOF ID" pattern="^[0-9]{8}$"
                            id="hof_id" name="hof_id" aria-label="Sabeel number" aria-describedby="button-addon2" required>
                        <button class="btn btn-outline-primary" type="submit" id="button-addon2">Search</button>
                    </div>
                </div>
            </form>            
        </div>
    </div>
    <?php
}

function _handle_form_submission()
{
    $action = $_POST['action'] ?? '';
    if( function_exists($action) ) {
        $action();
    } else {
        do_redirect('/input-sabeel');
    }
    
}

function sabeel_search() {
    $sabeel = $_POST['sabeel'];
    $sabeel_data = get_thaalilist_data($sabeel);
    if (is_null($sabeel_data)) {
        do_redirect_with_message('/input-sabeel', 'No records found for input ' . $sabeel . '. Enter correct sabeel number or HOF ITS.');
    }

    $hof_id = $sabeel_data->ITS_No;
    setAppData('hof_id', $hof_id);

    $hof_data = get_hof_data($hof_id);
    if (is_null($hof_data)) {
        do_redirect_with_message('/input-sabeel', "This is not a HOF ID");
    }

    if (intval($hof_data->sector) === 7) {
        if( $hof_id = 30359589 ) {
            do_redirect('/vjb.slot_booking/' . do_encrypt($sabeel));
        }
        do_redirect_with_message('/input-sabeel', 'Please contact Hatimi Hills Markaz for registration.');
    }

    $hijri_year = get_current_hijri_year();
    setAppData('hijri_year', $hijri_year);

    $attendees_data = get_attendees_data_for($hof_id, $hijri_year, false);
    if (is_null($attendees_data)) {
        do_redirect_with_message('/input-sabeel', 'Error: Seems your ITS (' . $hof_id . ') belong to other mohallah. Please contact jamaat office.');
    }

    $enc_sabeel = do_encrypt($sabeel);
    do_redirect('/input-attendees/' . $enc_sabeel);
}

function nonsab_register() {
    $hof_id = $_POST['hof_id'];


    
    // $itsids = [30359589, 30376437, 60458264, 20382133];

    // if( !in_array($hof_id, $itsids) ) {
    //     do_redirect_with_message('/input-sabeel', 'Please visit us later. We are working on it.');
    // } 

    $hof_data = get_its_record_for($hof_id);

    // $hof_data = get_hof_data($hof_id);
    if (is_null($hof_data) ) {
        do_redirect_with_message('/vajebaat', 'Error: Invalid ITS ID or Not a HOF ID or Your ITS does not belong to Kalimi.');
    }

    // if( $hof_data->mohallah === 'Kalimi' ) {
    //     do_redirect_with_message('/input-sabeel', 'This ITS ID is from Kalimi Mohalla.');
    // }

    // if (intval($hof_data->sector) === 7) {
    //     do_redirect_with_message('/input-sabeel', 'Please contact "Hatimi Hills Markaz" for registration.');
    // }

    $encrypted_hof_id = do_encrypt($hof_id);

    do_redirect('/vjb.slot_booking/'.$encrypted_hof_id);
}
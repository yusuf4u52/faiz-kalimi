<?php

do_for_post('_handle_form_submission');

function content_display()
{
    ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-2">Vajebaat Slot Registration</h2>
            <p class="mb-3"><small>Please enter HOF ID to proceed for vajebaat slots booking</small></p>
            <form method="post" action="" class="forms-sample">
                <input type="hidden" name="action" value="nonsab_register"/>
                <div class="row mb-3">
                    <label class="col-4 form-label">HOF ID (Numbers only)</label>
                    <div class="col-8">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="HOF ID" pattern="^[0-9]{8}$"
                                id="hof_id" name="hof_id" aria-label="Sabeel number" aria-describedby="button-addon2" required>
                            <button class="btn btn-light" type="submit" id="button-addon2">Search</button>
                        </div>
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
        do_redirect('/vajebaat');
    }
    
}

function sabeel_search() {
    $sabeel = $_POST['sabeel'];
    $sabeel_data = get_thaalilist_data($sabeel);
    if (is_null($sabeel_data)) {
        do_redirect_with_message('/vajebaat', 'No records found for input ' . $sabeel . '. Enter correct sabeel number or HOF ITS.');
    }

    $hof_id = $sabeel_data->ITS_No;
    setAppData('hof_id', $hof_id);

    $hof_data = get_hof_data($hof_id);
    if (is_null($hof_data)) {
        do_redirect_with_message('/vajebaat', "This is not a HOF ID");
    }

    $hijri_year = get_current_hijri_year();
    setAppData('hijri_year', $hijri_year);

    $attendees_data = get_attendees_data_for($hof_id, $hijri_year, false);
    if (is_null($attendees_data)) {
        do_redirect_with_message('/vajebaat', 'Error: Seems your ITS (' . $hof_id . ') belong to other mohallah. Please contact jamaat office.');
    }

    $enc_sabeel = do_encrypt($sabeel);
    do_redirect('/vjb.slot_booking/' . $enc_sabeel);
}


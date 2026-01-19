<?php

do_for_post('_handle_form_submission');

function content_display()
{
    $url = getAppData('BASE_URI');
    ?>
    <!-- <div class="card">
        <div class="card-header">
            <h4 class="card-title">FMB - Lailatul Qadr Niyat</h4>
            <p class="card-description">Enter HOF ID and Search</p>
        </div>
        <div class="card-body">
            <form method="post" action="fmb_lq_niyat" class="forms-sample">
                <input type="hidden" name="action" value="hof_for_fmb"/>
                <div class="form-group">
                    <label class="col-sm-3 col-form-label">HOF ID</label>
                    <div class="input-group col-xs-12">
                        <input type="text" class="form-control" placeholder="HOF ID" pattern="^[0-9]{8}$"
                            id="hof_id" name="hof_id" aria-label="HOF ID" aria-describedby="button-addon2" required>
                        <button class="btn btn-outline-primary" type="submit" id="button-addon2">Search</button>
                    </div>
                </div>
            </form>            
        </div>
    </div> -->
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Sabeel Search</h4>
            <p class="card-description"> Enter sabeel number and enter </p>
        </div>
        <div class="card-body">
            <form method="post" action="" class="forms-sample">
                <input type="hidden" name="action" value="sabeel_search"/>
                <div class="form-group">
                    <label class="col-sm-3 col-form-label">Sabeel ID / HOF ID (Numbers only)</label>
                    <div class="input-group col-xs-12">
                        <input type="text" class="form-control" placeholder="Sabeel Number" pattern="^[0-9]{1,8}$"
                            id="sabeel" name="sabeel" aria-label="Sabeel number" aria-describedby="button-addon2" required>
                        <button class="btn btn-outline-primary" type="submit" id="button-addon2">Search</button>
                    </div>
                </div>
            </form>            
        </div>
    </div>

    <!-- <br/>
    <hr/>
    <br/>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Vajebaat Slot Registration</h4>
            <a href="<?=$url?>/vajebaat" class="btn btn-outline-primary" type="submit" id="button-addon2">Vajebaat Area</a>
        </div>
    </div> -->
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
    $thaali_data = get_thaalilist_data($sabeel);
    if (is_null($thaali_data)) {
        do_redirect_with_message('/input-sabeel', 'No records found for input ' . $sabeel . '. Enter correct sabeel number or HOF ITS.');
    }
    $hof_id = $thaali_data->ITS_No;
    setAppData('hof_id', $hof_id);

    $sabeel_id = $thaali_data->Thali;
    $sabeel_data = get_sabeel_data($sabeel_id);
    if (is_null($sabeel_data)) {
        do_redirect_with_message('/input-sabeel', 'Please contact Kalimi Jamaat Office, your sabeel number not found for input ' . $sabeel);
    }

    $hof_data = get_hof_data($hof_id);
    if (is_null($hof_data)) {
        do_redirect_with_message('/input-sabeel', "This is not a HOF ID");
    }

    $hijri_year = get_current_hijri_year();
    setAppData('hijri_year', $hijri_year);

    $sector = intval($hof_data->sector);
    
    if ($sector == 7 || $sector == 13) {
        if( $hof_id == 30359589 ) {
            do_redirect('/vjb.slot_booking/' . do_encrypt($sabeel));
        }
        do_redirect_with_message('/input-sabeel', 'Please contact <b>Hatimi Hills Markaz</b> for registration.');
    }

    $attendees_data = get_attendees_data_for($hof_id, $hijri_year, false);
    if (is_null($attendees_data)) {
        do_redirect_with_message('/input-sabeel', 'Error: Seems your ITS (' . $hof_id . ') belong to other mohallah. Please contact jamaat office.');
    }

    $enc_sabeel = do_encrypt($sabeel);
    do_redirect('/input-attendees/' . $enc_sabeel);
}

function hof_for_fmb() {
    $hof_id = $_POST['hof_id'];
    $hof_data = get_its_record_for($hof_id);
    if (is_null($hof_data) ) {
        do_redirect_with_message('/input-sabeel', 'Error: This ITS ['.$hof_id.'] is not a HOF or not belong to Kalimi. Please contact Moiz Bhai Mulla (9096778753)');
    }

    //$encrypted_hof_id = do_encrypt($hof_id);
    setSessionData('HOF_FOR_FMB', $hof_id);

    do_redirect('/fmb_lq_niyat');
}


// function nonsab_register() {
//     $hof_id = $_POST['hof_id'];

//     $itsids = [30359589, 30376437, 60458264, 20382133];

//     if( !in_array($hof_id, $itsids) ) {
//         do_redirect_with_message('/input-sabeel', 'Please visit us later. We are working on it.');
//     } 

//     $hof_data = get_its_record_for($hof_id);

//     // $hof_data = get_hof_data($hof_id);
//     if (is_null($hof_data) ) {
//         do_redirect_with_message('/input-sabeel', 'Error: Invalid ITS ID or Not a HOF ID or Your ITS does not belong to Kalimi.');
//     }

//     // if( $hof_data->mohallah === 'Kalimi' ) {
//     //     do_redirect_with_message('/input-sabeel', 'This ITS ID is from Kalimi Mohalla.');
//     // }

//     // if (intval($hof_data->sector) === 7) {
//     //     do_redirect_with_message('/input-sabeel', 'Please contact "Hatimi Hills Markaz" for registration.');
//     // }

//     $encrypted_hof_id = do_encrypt($hof_id);

//     do_redirect('/vjb.slot_booking/'.$encrypted_hof_id);
// }
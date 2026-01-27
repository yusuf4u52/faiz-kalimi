<?php

/*
The logic
Extra the HOF ID from URI
Find out if the HOF has already done the booking?
If yes, redirect to vajebaat configuration page
If no, show them the available slots.

Once he submits the slot
See if slot has capacity
If yes, book a slot for him and go to vajebaat input page
If no, show him the message that slot is full and go back to slot selection page
*/



//Extract URI param and find sabeel
$hof_id_encrypted = getAppData('arg1');
$hof_id = do_decrypt($hof_id_encrypted);
// $hof_id = 30359589;


setAppData('hof_id', $hof_id);

$hijri_year = get_current_hijri_year();
setAppData('hijri_year', $hijri_year);

do_for_post('_handle_form_submit');

function _handle_form_submit()
{   
    $slot_id = $_POST['slot_id'];
    $hof_id = $_POST['hof_id'];
    $action = $_POST['action'];

    $encrypted_hof_id = do_encrypt($hof_id);


    $result = add_booking($hof_id, $slot_id);
    if( is_null($result) ) {
        //do_redirect_with_message('/vjb.vajebaat_input/' . do_encrypt($hof_id), 'Your slot is booked successfully');
        //setSessionData(TRANSIT_DATA, 'Your slot is booked sucessfully.');
        do_redirect_with_message("/vjb.slot_booking/$encrypted_hof_id", 'Your slot is booked sucessfully.');
    } else {
        //setSessionData(TRANSIT_DATA, $result);
        do_redirect_with_message("/vjb.slot_booking/$encrypted_hof_id" , $result);
    }
}

function content_display()
{
    $url = getAppData('BASE_URI');
    $hof_id = getAppData('hof_id');
    $enc_hof_id = do_encrypt($hof_id);

    $booking_details = get_booking_details($hof_id);
    // if (!is_null($booking_details)) {
    //     //do_redirect('/vjb.vajebaat_input/' . $hof_id_encrypted);
    //     setAppData('booking_details' , $booking_details);
    // }

    // $booking_details = getAppData('booking_details');
    if( is_null($booking_details) ) {
    $slots = get_slot_for_registration();
    ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-2">Vajebaat Slots</h2>
            <p class="mb-3"><small>Select the vajebaat slot and proceed</small></p>
            <form method="post" action="" class="forms-sample">
                <input type="hidden" name="hof_id" value="<?=$hof_id?>" />
                <input type="hidden" name="action" value="register" />
                <div class="row mb-3">
                    <label for="whatsapp" class="col-3 form-label">Slot</label>
                    <div class="col-9">
                        <select class="form-select" name="slot_id" required>
                            <option value="">Select Slot....</option>
                            <?php foreach ($slots as $slot) { ?>
                                <option value="<?= $slot->id ?>"><?= $slot->title ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-light">Submit</button>
            </form>            
        </div>        
    </div>
    <?php
    } else { ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-2">Vajebaat Slots</h2>
            <p class="mb-3"><small>You vajebaat slot booking is</small></p>
            <form method="post" action="" class="forms-sample">
                <input type="hidden" name="hof_id" value="<?=$hof_id?>" />
                <input type="hidden" name="action" value="delete_slot" />
                <div class="row mb-3">
                    <label for="whatsapp" class="col-3 form-label">Vajebaat Slot</label>
                    <div class="col-9">
                        <?=$booking_details->title?>
                    </div>
                </div>
                <!-- <p>Delete my slot</p>
                <button type="submit" class="btn btn-light">Delete</button> -->
            </form>
        </div>
        <!-- <div class="card-footer">
            <a href="<?=$url?>/vjb.vajebaat_input/<?=$enc_hof_id?>" class="btn btn-light">Go to Vajebaat form</a>
        </div> -->
    </div>
    <?php
    }

}
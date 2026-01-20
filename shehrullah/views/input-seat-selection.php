<?php

do_for_post('_handle_form_submission');

function content_display()
{
    // Check if seat selection is open
    if (!is_seat_selection_open()) {
        ?>
        <div class="alert alert-warning" role="alert">
            <strong>Seat Selection is Currently CLOSED</strong><br>
            Please wait for the announcement when seat selection opens.
        </div>
        <a href="<?= getAppData('BASE_URI') ?>" class="btn btn-primary">Go Back to Home</a>
        <?php
        return;
    }
    ?>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Seat Selection</h4>
            <p class="card-description">Enter your Sabeel number or HOF ID to select seats</p>
        </div>
        <div class="card-body">
            <div class="alert alert-info" role="alert">
                <strong>Note:</strong> Seat selection is available only after full payment of takhmeen.
                <br>Seat selection is on <strong>first come first serve</strong> basis.
            </div>
            <form method="post" action="" class="forms-sample">
                <input type="hidden" name="action" value="seat_selection_search"/>
                <div class="form-group">
                    <label class="col-sm-3 col-form-label">Sabeel ID / HOF ID (Numbers only)</label>
                    <div class="input-group col-xs-12">
                        <input type="text" class="form-control" placeholder="Sabeel Number or HOF ID" pattern="^[0-9]{1,8}$"
                            id="sabeel" name="sabeel" aria-label="Sabeel number" aria-describedby="button-addon2" required>
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
    if (function_exists($action)) {
        $action();
    } else {
        do_redirect('/input-seat-selection');
    }
}

function seat_selection_search() {
    $sabeel = $_POST['sabeel'];
    
    // Try to find by sabeel or HOF ID
    $thaali_data = get_thaalilist_data($sabeel);
    if (is_null($thaali_data)) {
        do_redirect_with_message('/input-seat-selection', 'No records found for input ' . $sabeel . '. Enter correct sabeel number or HOF ITS.');
    }
    
    $hof_id = $thaali_data->ITS_No;
    $hijri_year = get_current_hijri_year();
    
    // Check if takhmeen is done
    $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
    if (is_null($takhmeen_data) || $takhmeen_data->takhmeen <= 0) {
        do_redirect_with_message('/input-seat-selection', 'Takhmeen is not done yet. Please complete registration and takhmeen first.');
    }
    
    // Check payment status
    $is_paid = $takhmeen_data->paid_amount >= $takhmeen_data->takhmeen;
    $has_exception = has_seat_exception($hof_id, $hijri_year);
    
    if (!$is_paid && !$has_exception) {
        $pending = $takhmeen_data->takhmeen - $takhmeen_data->paid_amount;
        do_redirect_with_message('/input-seat-selection', 'Payment pending. Please complete payment of Rs. ' . number_format($pending) . ' to select seats.');
    }
    
    // Check if there are any attendees eligible for seat selection
    $attendees = get_attendees_for_seat_selection($hof_id);
    if (empty($attendees)) {
        do_redirect_with_message('/input-seat-selection', 'No eligible family members found for seat selection. Only Misaq Done members who are attending can select seats.');
    }
    
    $enc_sabeel = do_encrypt($sabeel);
    do_redirect('/seat-selection/' . $enc_sabeel);
}

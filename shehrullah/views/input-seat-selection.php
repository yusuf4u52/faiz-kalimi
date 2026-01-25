<?php

do_for_post('_handle_form_submission');

function content_display()
{
    // Check if seat selection is open
    if (!is_seat_selection_open()) {
        ?>
        <div class="card">
            <div class="card-body">
                <div class="alert alert-warning" role="alert">
                    <strong>Seat Selection is Currently CLOSED</strong><br>
                    Please wait for the announcement when seat selection opens.
                </div>
                <a href="<?= getAppData('BASE_URI') ?>" class="btn btn-light">Go Back to Home</a>
            </div>
        </div>
        <?php
        return;
    }
    ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-2">Seat Selection</h2>
            <p class="mb-3"><small>Enter your HOF ID to select seats</small></p>
            <div class="alert alert-info" role="alert">
                <strong>Note:</strong> Seat selection is on <strong>first come first serve</strong> basis.
            </div>
            <form method="post" action="" class="forms-sample">
                <input type="hidden" name="action" value="seat_selection_search"/>
                <div class="row mb-3">
                    <label class="col-4 form-label">HOF ID (Numbers only)</label>
                    <div class="col-8">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="HOF ID" pattern="^[0-9]{1,8}$"
                                id="sabeel" name="sabeel" aria-label="HOF ID" aria-describedby="button-addon2" required>
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
    if (function_exists($action)) {
        $action();
    } else {
        do_redirect('/input-seat-selection');
    }
}

function seat_selection_search() {
    $hof_id = $_POST['sabeel'];
    
    // Search only by HOF ID (ITS_No)
    $query = 'SELECT Thali, NAME, CONTACT, sabeelType, ITS_No, 
    Email_ID,Full_Address,WhatsApp, sector
    FROM thalilist WHERE ITS_No=?;';
    $result = run_statement($query, $hof_id);
    $thaali_data = ($result->success && $result->count > 0) ? $result->data[0] : null;
    
    if (is_null($thaali_data)) {
        do_redirect_with_message('/input-seat-selection', 'No records found for HOF ID ' . $hof_id . '. Enter correct HOF ID.');
    }
    
    // Verify eligibility
    if (!can_select_seats($hof_id)) {
        do_redirect_with_message('/input-seat-selection', 'You are not eligible for seat selection. Please complete payment first.');
    }
    
    // Check if there are any attendees eligible for seat selection
    $attendees = get_attendees_for_seat_selection($hof_id);
    if (empty($attendees)) {
        do_redirect_with_message('/input-seat-selection', 'No eligible family members found for seat selection. Only Misaq Done members who are attending can select seats.');
    }
    
    $enc_hof_id = do_encrypt($hof_id);
    do_redirect('/seat-selection/' . $enc_hof_id);
}

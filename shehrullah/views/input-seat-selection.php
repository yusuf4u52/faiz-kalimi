<?php

do_for_post('_handle_form_submission');

function content_display()
{
    // Check if seat selection is open
    if (!is_seat_selection_open()) {
        ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="alert alert-warning mb-4" role="alert">
                    <i class="fas fa-lock me-2"></i>
                    <strong>Seat Selection is Currently CLOSED</strong><br>
                    <small class="d-block mt-2">Please wait for the announcement when seat selection opens.</small>
                </div>
                <a href="<?= getAppData('BASE_URI') ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-home me-2"></i>Go Back to Home
                </a>
            </div>
        </div>
        <style>
            @media (max-width: 767.98px) {
                .content-wrapper {
                    padding-left: 0.75rem !important;
                    padding-right: 0.75rem !important;
                    padding-top: 1rem !important;
                    padding-bottom: 1rem !important;
                }
                .card {
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                }
                .card-body {
                    padding: 2rem 1rem !important;
                }
                .btn-lg {
                    min-height: 48px;
                    width: 100%;
                }
            }
        </style>
        <?php
        return;
    }
    ?>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-1">Seat Selection</h4>
            <p class="card-description mb-0 text-muted">Enter your HOF ID to select seats</p>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> Seat selection is on <strong>first come first serve</strong> basis.
            </div>
            <form method="post" action="" class="forms-sample">
                <input type="hidden" name="action" value="seat_selection_search"/>
                <div class="form-group mb-3">
                    <label class="form-label fw-semibold mb-2">HOF ID (Numbers only)</label>
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control" placeholder="Enter your 8-digit HOF ID" pattern="^[0-9]{1,8}$"
                            id="sabeel" name="sabeel" aria-label="HOF ID" aria-describedby="button-addon2" required>
                        <button class="btn btn-primary" type="submit" id="button-addon2">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                    </div>
                    <small class="form-text text-muted mt-1">Enter your 8-digit HOF ID to proceed with seat selection</small>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        /* Mobile-first optimizations */
        @media (max-width: 767.98px) {
            .content-wrapper {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }
            
            .card {
                margin-left: 0 !important;
                margin-right: 0 !important;
                border-radius: 0.5rem;
            }
            
            .card-body {
                padding: 1.25rem 1rem !important;
            }
            
            .card-header {
                padding: 1rem !important;
            }
            
            .alert {
                padding: 0.875rem 1rem;
                font-size: 0.9375rem;
            }
            
            .input-group-lg .form-control {
                font-size: 1rem;
                min-height: 48px;
            }
            
            .input-group-lg .btn {
                min-height: 48px;
                padding: 0.625rem 1rem;
            }
        }
        
        @media (min-width: 768px) {
            .card-body {
                padding: 1.5rem !important;
            }
            
            .input-group-lg .form-control {
                font-size: 1.125rem;
            }
        }
        
        .form-label {
            font-size: 0.9375rem;
        }
        
        .input-group-lg .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        .alert-info {
            border-left: 4px solid #0dcaf0;
        }
    </style>
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
    
    $enc_hof_id = do_encrypt($hof_id);
    do_redirect('/seat-selection/' . $enc_hof_id);
}

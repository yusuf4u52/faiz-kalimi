<?php

initial_processing();
do_for_post('_handle_form_submit');

function initial_processing()
{
    $en_sabeel = getAppData('arg1');
    $sabeel = do_decrypt($en_sabeel);

    $sabeel_data = get_thaalilist_data($sabeel);
    if (is_null($sabeel_data)) {
        do_redirect_with_message('/input-sabeel', 'No records found. Please enter correct sabeel number or HOF ITS.');
    }

    $hof_id = $sabeel_data->ITS_No;
    setAppData('sabeel_data', $sabeel_data);
    setAppData('hof_id', $hof_id);

    $hijri_year = get_current_hijri_year();
    setAppData('hijri_year', $hijri_year);

    // Verify eligibility
    if (!can_select_seats($hof_id)) {
        do_redirect_with_message('/input-sabeel', 'You are not eligible for seat selection. Please complete payment first.');
    }

    $attendees = get_attendees_for_seat_selection($hof_id);
    if (empty($attendees)) {
        do_redirect_with_message('/input-sabeel', 'No eligible family members found for seat selection.');
    }
    setAppData('attendees', $attendees);
}

function _handle_form_submit()
{
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_seat') {
        $its_id = $_POST['its_id'] ?? '';
        $area_code = $_POST['area_code'] ?? '';
        $hof_id = getAppData('hof_id');
        
        if (empty($its_id) || empty($area_code)) {
            setSessionData(TRANSIT_DATA, 'Invalid selection.');
            return;
        }
        
        // Try to allocate seat - returns seat number/true on success, false on failure
        $result = allocate_seat($its_id, $hof_id, $area_code);
        
        // Success if seat is allocated, failure if not
        if ($result) {
            $message = is_string($result) ? "Seat allocated successfully! Seat #{$result}" : 'Seat allocated successfully!';
            setSessionData(TRANSIT_DATA, $message);
        } else {
            setSessionData(TRANSIT_DATA, 'Seat allocation failed. Area may be full or unavailable.');
        }
        
        // Refresh attendees data
        $attendees = get_attendees_for_seat_selection($hof_id);
        setAppData('attendees', $attendees);
    }
}

function content_display()
{
    // Suppress template message display - we'll handle it with modal
    setAppData('SUPPRESS_TRANSIT_MESSAGE', true);
    
    $sabeel_data = getAppData('sabeel_data');
    $hof_id = getAppData('hof_id');
    $hijri_year = getAppData('hijri_year');
    $attendees = getAppData('attendees');
    $url = getAppData('BASE_URI');
    
    $name = $sabeel_data->NAME ?? '';
    $sabeel = $sabeel_data->Thali ?? '';
    
    // Get transit message if any
    $transit_message = getSessionData(TRANSIT_DATA);
    $is_error = !empty($transit_message) && (strpos($transit_message, 'failed') !== false || strpos($transit_message, 'Invalid') !== false);
    $is_success = !empty($transit_message) && strpos($transit_message, 'success') !== false;
    
    if (!empty($transit_message)) {
        clearSessionData(TRANSIT_DATA);
    }
    
    ui_card("Seat Selection - Shehrullah {$hijri_year}H");
    ui_alert('<strong>Important:</strong> Seat selection is on <strong>first come first serve</strong> basis. Please complete your selection promptly.', 'warning');
    ?>
    <table class="table table-sm table-bordered mb-4" style="max-width:500px">
        <tr><th style="width:100px">HOF</th><td><?= ui_code($hof_id) ?> <?= h($name) ?></td></tr>
        <tr><th>Sabeel</th><td><?= h($sabeel) ?></td></tr>
    </table>
    
    <h6 class="mb-3">Select Seating Area for Family Members</h6>
    <?php
    ui_table(['#', 'Name', 'G/Age', 'Chair', 'Select Area', 'Seat #', 'Action']);
    
    $index = 0;
    foreach ($attendees as $att) {
        $index++;
        $its_id = $att->its_id;
        $chair = $att->chair_preference == 'Y' ? 'Yes' : 'No';
        $allocated_area = $att->allocated_area ?? '';
        $seat_number = $att->seat_number ?? '';
        $is_admin = !empty($att->allocated_by);
        
        // Get eligible areas
        $eligible_areas = get_eligible_areas_for_attendee($its_id, $hof_id);
        
        // Area cell
        if ($is_admin) {
            $area_cell = ui_badge($att->allocated_area_name ?? '', 'info') . "<br>" . ui_muted('Assigned by Admin');
        } else {
            $opts = [];
            if (empty($allocated_area)) $opts[''] = '-- Select --';
            
            foreach ($eligible_areas as $a) {
                $opts[$a->area_code] = $a->area_name;
            }
            
            if (empty($eligible_areas)) {
                $opts[''] = 'Limit reached';
            }
            
            $area_cell = "<form method=\"post\" class=\"d-inline\" id=\"form_{$its_id}\">"
                . "<input type=\"hidden\" name=\"action\" value=\"save_seat\">"
                . "<input type=\"hidden\" name=\"its_id\" value=\"{$its_id}\">"
                . ui_select('area_code', $opts, $allocated_area)
                . "</form>";
        }
        
        // Seat cell
        if (!empty($seat_number)) {
            $seat_cell = ui_badge($seat_number, 'success');
        } else {
            $seat_cell = '--';
        }
        
        // Action cell
        if ($is_admin) {
            $action_cell = ui_muted('--');
        } else {
            $action_cell = "<button type=\"button\" class=\"btn btn-primary btn-sm\" onclick=\"document.getElementById('form_{$its_id}').submit();\">Save</button>";
        }
        
        ui_tr([
            $index,
            h($att->full_name),
            ui_ga($att->gender, $att->age),
            $chair,
            $area_cell,
            $seat_cell,
            $action_cell
        ]);
    }
    
    ui_table_end();
    ?>
    <div class="mt-4">
        <?= ui_link('Back', "$url/input-seat-selection", 'secondary') ?>
    </div>
    <?php 
    ui_card_end();
    
    // Bootstrap Modal for Error Messages
    if ($is_error && !empty($transit_message)) {
        ?>
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-danger" style="border-width: 3px;">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="errorModalLabel">
                            <i class="fas fa-exclamation-triangle"></i> Seat Allocation Error
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center" style="font-size: 1.1rem;">
                        <p class="mb-0"><strong><?= h($transit_message) ?></strong></p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">I Understand</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show Bootstrap modal for errors
            var errorModal = document.getElementById('errorModal');
            if (errorModal) {
                var modal = new bootstrap.Modal(errorModal);
                modal.show();
            }
        });
    </script>
    <?php
}

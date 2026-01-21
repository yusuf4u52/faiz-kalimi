<?php

initial_processing();
do_for_post('_handle_form_submit');

function initial_processing()
{
    // Suppress template message display - we'll handle it with modal
    setAppData('SUPPRESS_TRANSIT_MESSAGE', true);
    
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

    $attendees = get_all_attendees_for_display($hof_id);
    if (empty($attendees)) {
        do_redirect_with_message('/input-sabeel', 'No family members found for seat selection.');
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
        $attendees = get_all_attendees_for_display($hof_id);
        setAppData('attendees', $attendees);
    }
}

function content_display()
{
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
        removeSessionData(TRANSIT_DATA);
    }
    
    // Check if selection is complete from already-fetched attendees data
    $selection_complete = true;
    $has_eligible = false;
    foreach ($attendees as $att) {
        $misaq_done = ($att->misaq ?? '') === 'Done';
        if ($misaq_done) {
            $has_eligible = true;
            $allocated_area = $att->allocated_area ?? '';
            $seat_number = $att->seat_number ?? '';
            if (empty($allocated_area) || empty($seat_number)) {
                $selection_complete = false;
                break;
            }
        }
    }
    // If no eligible attendees, selection cannot be complete
    if (!$has_eligible) {
        $selection_complete = false;
    }
    
    ui_card("Seat Selection - Shehrullah {$hijri_year}H");
    
    if ($selection_complete) {
        ui_alert('<strong>Selection Complete!</strong> Your seat selection has been finalized. Click Print to view your tickets.', 'success');
    } else {
        ui_alert('<strong>Important:</strong> Seat selection is on <strong>first come first serve</strong> basis. Please complete your selection promptly.', 'warning');
    }
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
        $misaq_done = ($att->misaq ?? '') === 'Done';
        
        // Get eligible areas (only if Misaq is Done)
        $eligible_areas = $misaq_done ? get_eligible_areas_for_attendee($its_id, $hof_id) : [];
        
        // Area cell
        if ($is_admin) {
            $area_cell = ui_badge($att->allocated_area_name ?? '', 'info') . "<br>" . ui_muted('Assigned by Admin');
        } else if (!$misaq_done) {
            $area_cell = ui_muted('Misaq not Done');
        } else {
            // If selection is complete, show badge instead of dropdown
            if ($selection_complete && !empty($allocated_area)) {
                $area_cell = ui_badge($att->allocated_area_name ?? $allocated_area, 'primary');
            } else {
                $opts = [];
                if (empty($allocated_area)) $opts[''] = '-- Select --';
                
                foreach ($eligible_areas as $a) {
                    $opts[$a->area_code] = $a->area_name;
                }
                
                if (empty($eligible_areas)) {
                    $opts[''] = 'Limit reached';
                }
                
                // If selection is complete but this attendee doesn't have area yet, disable dropdown
                $disabled_attr = $selection_complete ? 'disabled' : '';
                $select_html = ui_select('area_code', $opts, $allocated_area);
                $area_cell = "<form method=\"post\" class=\"d-inline\" id=\"form_{$its_id}\">"
                    . "<input type=\"hidden\" name=\"action\" value=\"save_seat\">"
                    . "<input type=\"hidden\" name=\"its_id\" value=\"{$its_id}\">"
                    . str_replace('<select', '<select ' . $disabled_attr, $select_html)
                    . "</form>";
            }
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
        } else if (!$misaq_done) {
            $action_cell = ui_muted('--');
        } else {
            if ($selection_complete) {
                // Show Print button instead of Save when selection is complete - pass ITS ID to show only this person's ticket
                $action_cell = "<button type=\"button\" class=\"btn btn-success btn-sm\" onclick=\"showPrintModal('{$its_id}');\">Print</button>";
            } else {
                $action_cell = "<button type=\"button\" class=\"btn btn-primary btn-sm\" onclick=\"document.getElementById('form_{$its_id}').submit();\">Save</button>";
            }
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
    
    // Print Modal with Ticket Structure
    if ($selection_complete) {
        ?>
        <div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="printModalLabel">
                            <i class="fas fa-ticket-alt"></i> Seat Tickets - Shehrullah <?= $hijri_year ?>H
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="printableTickets">
                        <style>
                            @media print {
                                body * { visibility: hidden; }
                                #printModal, #printModal * { visibility: visible; }
                                #printModal { position: absolute; left: 0; top: 0; width: 100%; }
                                .modal-header, .modal-footer, .no-print { display: none !important; }
                                .modal-dialog { max-width: 100%; margin: 0; }
                                .modal-content { border: none; box-shadow: none; }
                            }
                        </style>
                        <?php
                        foreach ($attendees as $att) {
                            $misaq_done = ($att->misaq ?? '') === 'Done';
                            $allocated_area = $att->allocated_area ?? '';
                            $seat_number = $att->seat_number ?? '';
                            
                            // Only show tickets for attendees with allocated seats
                            if ($misaq_done && !empty($allocated_area) && !empty($seat_number)) {
                                $area_name = $att->allocated_area_name ?? $allocated_area;
                                $ticket_its_id = $att->its_id;
                                ?>
                                <div class="ticket-card" data-its-id="<?= h($ticket_its_id) ?>" style="display: none;">
                                    <div class="card border-success mb-3">
                                        <div class="card-header bg-success text-white text-center">
                                            <h5 class="mb-0">Shehrullah <?= $hijri_year ?>H</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <div class="border-start border-success border-3 ps-3">
                                                        <small class="text-muted text-uppercase fw-bold">Name</small>
                                                        <div class="fw-bold"><?= h($att->full_name) ?></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="border-start border-success border-3 ps-3">
                                                        <small class="text-muted text-uppercase fw-bold">Area</small>
                                                        <div class="fw-bold"><?= h($area_name) ?></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="border-start border-success border-3 ps-3">
                                                        <small class="text-muted text-uppercase fw-bold">Seat</small>
                                                        <div class="fw-bold"><?= h($seat_number) ?></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="border-start border-success border-3 ps-3">
                                                        <small class="text-muted text-uppercase fw-bold">ITS ID</small>
                                                        <div class="fw-bold"><?= h($att->its_id) ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-center text-muted mt-3 pt-3 border-top">
                                                <small>Please carry this ticket for your convenience</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <div class="modal-footer no-print">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-success" onclick="printTickets();">
                            <i class="fas fa-print"></i> Print
                        </button>
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
        
        function showPrintModal(itsId) {
            // Hide all tickets first
            var allTickets = document.querySelectorAll('.ticket-card');
            allTickets.forEach(function(ticket) {
                ticket.style.display = 'none';
            });
            
            // Show only the ticket for the selected attendee
            if (itsId) {
                var selectedTicket = document.querySelector('.ticket-card[data-its-id="' + itsId + '"]');
                if (selectedTicket) {
                    selectedTicket.style.display = 'block';
                }
            }
            
            var printModal = document.getElementById('printModal');
            if (printModal) {
                var modal = new bootstrap.Modal(printModal);
                modal.show();
            }
        }
        
        function printTickets() {
            window.print();
        }
    </script>
    <?php
}

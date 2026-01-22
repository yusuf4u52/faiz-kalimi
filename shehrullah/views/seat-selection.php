<?php

initial_processing();
do_for_post('_handle_form_submit');

function initial_processing()
{
    // Suppress template message display - we'll handle it with modal
    setAppData('SUPPRESS_TRANSIT_MESSAGE', true);
    
    $en_sabeel = getAppData('arg1');
    $hof_id = do_decrypt($en_sabeel);

    // Search only by HOF ID (ITS_No)
    $query = 'SELECT Thali, NAME, CONTACT, sabeelType, ITS_No, 
    Email_ID,Full_Address,WhatsApp, sector
    FROM thalilist WHERE ITS_No=?;';
    $result = run_statement($query, $hof_id);
    $sabeel_data = ($result->success && $result->count > 0) ? $result->data[0] : null;
    
    if (is_null($sabeel_data)) {
        do_redirect_with_message('/input-seat-selection', 'No records found for HOF ID ' . $hof_id . '. Enter correct HOF ID.');
    }

    $hof_id = $sabeel_data->ITS_No;
    setAppData('sabeel_data', $sabeel_data);
    setAppData('hof_id', $hof_id);

    $hijri_year = get_current_hijri_year();
    setAppData('hijri_year', $hijri_year);

    // Verify eligibility
    if (!can_select_seats($hof_id)) {
        do_redirect_with_message('/input-seat-selection', 'You are not eligible for seat selection. Please complete payment first.');
    }

    $attendees = get_all_attendees_for_display($hof_id);
    if (empty($attendees)) {
        do_redirect_with_message('/input-seat-selection', 'No family members found for seat selection.');
    }
    setAppData('attendees', $attendees);
}

function _handle_form_submit()
{
    $action = $_POST['action'] ?? '';
    
    // Handle AJAX request for getting eligible areas
    if ($action === 'get_eligible_areas_ajax') {
        header('Content-Type: application/json');
        $its_id = $_POST['its_id'] ?? '';
        // Try to get hof_id from POST first, then from app data
        $hof_id = $_POST['hof_id'] ?? getAppData('hof_id');
        
        if (empty($its_id) || empty($hof_id)) {
            echo json_encode(['success' => false, 'error' => 'Missing parameters']);
            exit;
        }
        
        $eligible_areas = get_eligible_areas_for_attendee($its_id, $hof_id);
        $options = [];
        foreach ($eligible_areas as $area) {
            $options[] = [
                'area_code' => $area->area_code,
                'area_name' => $area->area_name
            ];
        }
        
        echo json_encode(['success' => true, 'areas' => $options]);
        exit;
    }
    
    // Get encrypted sabeel for redirect URL (POST-Redirect-GET pattern)
    $en_sabeel = getAppData('arg1');
    $redirect_url = '/seat-selection/' . $en_sabeel;
    
    if ($action === 'save_seat') {
        $its_id = $_POST['its_id'] ?? '';
        $area_code = $_POST['area_code'] ?? '';
        $hof_id = getAppData('hof_id');
        
        if (empty($its_id) || empty($area_code)) {
            do_redirect_with_message($redirect_url, 'Invalid selection.');
            return;
        }
        
        // Try to allocate seat - returns seat number/true on success, false on failure
        $result = allocate_seat_atomic($its_id, $hof_id, $area_code);
        
        // Success if seat is allocated, failure if not
        if ($result) {
            $message = is_string($result) ? "Seat allocated successfully! Seat #{$result}" : 'Seat allocated successfully!';
            do_redirect_with_message($redirect_url, $message);
        } else {
            do_redirect_with_message($redirect_url, 'Seat allocation failed. Area may be full or unavailable.');
        }
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
        ui_alert('<strong>Selection Complete!</strong> Your seat selection has been finalized. Click Print to see your seat card.', 'success');
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
            // Show badge if this attendee has selected an area, otherwise show dropdown
            if (!empty($allocated_area)) {
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
                // Add data attributes for AJAX refresh functionality
                $select_html = str_replace('<select', '<select data-its-id="' . h($its_id) . '" data-hof-id="' . h($hof_id) . '" id="area_select_' . h($its_id) . '" ' . $disabled_attr, $select_html);
                $area_cell = "<form method=\"post\" class=\"d-inline\" id=\"form_{$its_id}\">"
                    . "<input type=\"hidden\" name=\"action\" value=\"save_seat\">"
                    . "<input type=\"hidden\" name=\"its_id\" value=\"{$its_id}\">"
                    . $select_html
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
            $has_allocation = !empty($allocated_area) && !empty($seat_number);
            $action_buttons = [];
            
            if ($has_allocation) {
                // Show Print button when selection is complete - pass ITS ID to show only this person's seat
                if ($selection_complete) {
                    $action_buttons[] = "<button type=\"button\" class=\"btn btn-success btn-sm\" onclick=\"showPrintModal('{$its_id}');\">Print</button>";
                }
            } else {
                // Show Save button when no allocation exists
                $action_buttons[] = "<button type=\"button\" class=\"btn btn-primary btn-sm\" onclick=\"document.getElementById('form_{$its_id}').submit();\">Save</button>";
            }
            
            $action_cell = implode(' ', $action_buttons);
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
    
    // Print Modal with Seat Structure
    if ($selection_complete) {
        ?>
        <div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="printModalLabel">
                            Shehrullah <?= $hijri_year ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <?php
                        foreach ($attendees as $att) {
                            $misaq_done = ($att->misaq ?? '') === 'Done';
                            $allocated_area = $att->allocated_area ?? '';
                            $seat_number = $att->seat_number ?? '';
                            
                            // Only show seats for attendees with allocated seats
                            if ($misaq_done && !empty($allocated_area) && !empty($seat_number)) {
                                $area_name = $att->allocated_area_name ?? $allocated_area;
                                $seat_its_id = $att->its_id;
                                ?>
                                <div class="seat-card" data-its-id="<?= h($seat_its_id) ?>" style="display: none;">
                                    <div class="card border-success">
                                        <div class="card-header bg-success text-white text-center py-2" style="display: none;">
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="mb-2">
                                                <small class="text-muted d-block">Name</small>
                                                <strong><?= h($att->full_name) ?></strong>
                                            </div>
                                            <div class="mb-2">
                                                <small class="text-muted d-block">Area</small>
                                                <strong><?= h($area_name) ?></strong>
                                            </div>
                                            <div class="mb-2">
                                                <small class="text-muted d-block">Seat</small>
                                                <strong><?= h($seat_number) ?></strong>
                                            </div>
                                            <div class="mb-2">
                                                <small class="text-muted d-block">ITS ID</small>
                                                <strong><?= h($att->its_id) ?></strong>
                                            </div>
                                            <div class="text-center text-muted mt-3 pt-2 border-top">
                                                <small>Please carry this card for your convenience</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            
            // Refresh area dropdowns periodically to handle rush times
            var refreshInterval = setInterval(refreshAllAreaDropdowns, 5000); // Refresh every 5 seconds
            
            // Also refresh before form submission
            var forms = document.querySelectorAll('form[id^="form_"]');
            forms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    var itsIdInput = form.querySelector('input[name="its_id"]');
                    if (itsIdInput) {
                        var itsId = itsIdInput.value;
                        refreshAreaDropdown(itsId, function() {
                            // Continue with form submission after refresh
                            // Don't prevent default - let form submit normally
                        });
                    }
                });
            });
            
            // Clean up interval when page unloads
            window.addEventListener('beforeunload', function() {
                clearInterval(refreshInterval);
            });
        });
        
        function refreshAllAreaDropdowns() {
            var dropdowns = document.querySelectorAll('select[data-its-id]:not([disabled])');
            dropdowns.forEach(function(dropdown) {
                // Skip if dropdown is disabled or form is being submitted
                if (dropdown.disabled) {
                    return;
                }
                
                var itsId = dropdown.getAttribute('data-its-id');
                var currentValue = dropdown.value;
                refreshAreaDropdown(itsId, function(updated) {
                    if (updated && currentValue && !dropdown.querySelector('option[value="' + currentValue + '"]')) {
                        // If current selection is no longer available, clear it
                        dropdown.value = '';
                        // Show a subtle notification
                        showAreaUnavailableNotification(itsId);
                    }
                });
            });
        }
        
        function refreshAreaDropdown(itsId, callback) {
            var dropdown = document.getElementById('area_select_' + itsId);
            if (!dropdown) {
                if (callback) callback(false);
                return;
            }
            
            var hofId = dropdown.getAttribute('data-hof-id');
            var currentValue = dropdown.value;
            
            // Create form data
            var formData = new FormData();
            formData.append('action', 'get_eligible_areas_ajax');
            formData.append('its_id', itsId);
            formData.append('hof_id', hofId);
            
            // Fetch updated areas
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success && data.areas) {
                    // Store current selection
                    var selectedValue = dropdown.value;
                    
                    // Clear existing options (except the first empty option if exists)
                    var firstOption = dropdown.options[0];
                    var isEmptyOption = firstOption && firstOption.value === '';
                    dropdown.innerHTML = '';
                    
                    // Add empty option if it existed before
                    if (isEmptyOption) {
                        var emptyOpt = document.createElement('option');
                        emptyOpt.value = '';
                        emptyOpt.textContent = '-- Select --';
                        dropdown.appendChild(emptyOpt);
                    }
                    
                    // Add updated options
                    var hasCurrentSelection = false;
                    data.areas.forEach(function(area) {
                        var option = document.createElement('option');
                        option.value = area.area_code;
                        option.textContent = area.area_name;
                        if (selectedValue === area.area_code) {
                            option.selected = true;
                            hasCurrentSelection = true;
                        }
                        dropdown.appendChild(option);
                    });
                    
                    // If no areas available, add "Limit reached" option
                    if (data.areas.length === 0) {
                        var limitOpt = document.createElement('option');
                        limitOpt.value = '';
                        limitOpt.textContent = 'Limit reached';
                        dropdown.appendChild(limitOpt);
                    }
                    
                    // Restore selection if it still exists
                    if (hasCurrentSelection) {
                        dropdown.value = selectedValue;
                    }
                    
                    if (callback) callback(true);
                } else {
                    if (callback) callback(false);
                }
            })
            .catch(function(error) {
                console.error('Error refreshing area dropdown:', error);
                if (callback) callback(false);
            });
        }
        
        function showAreaUnavailableNotification(itsId) {
            // Create a subtle notification that the selected area is no longer available
            var dropdown = document.getElementById('area_select_' + itsId);
            if (dropdown) {
                var form = dropdown.closest('form');
                if (form) {
                    // Add a small visual indicator
                    dropdown.style.borderColor = '#dc3545';
                    setTimeout(function() {
                        dropdown.style.borderColor = '';
                    }, 2000);
                }
            }
        }
        
        function showPrintModal(itsId) {
            // Hide all seats first
            var allSeats = document.querySelectorAll('.seat-card');
            allSeats.forEach(function(seat) {
                seat.style.display = 'none';
            });
            
            // Show only the seat for the selected attendee
            if (itsId) {
                var selectedSeat = document.querySelector('.seat-card[data-its-id="' + itsId + '"]');
                if (selectedSeat) {
                    selectedSeat.style.display = 'block';
                }
            }
            
            var printModal = document.getElementById('printModal');
            if (printModal) {
                var modal = new bootstrap.Modal(printModal);
                modal.show();
            }
        }
    </script>
    <?php
}

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
    Email_ID,wingflat,society,Full_Address,WhatsApp, sector
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
        $seat_number = $_POST['seat_number'] ?? null;
        $hof_id = getAppData('hof_id');
        
        if (empty($its_id) || empty($area_code)) {
            do_redirect_with_message($redirect_url, 'Invalid selection.');
            return;
        }
        
        // Seat number is required - no automatic allocation
        if (empty($seat_number) || $seat_number === null || $seat_number === '') {
            do_redirect_with_message($redirect_url, 'Please provide a seat number.');
            return;
        }
        
        $seat_number = intval($seat_number);
        if ($seat_number <= 0) {
            do_redirect_with_message($redirect_url, 'Invalid seat number.');
            return;
        }
        
        // Try to allocate seat - returns seat number on success, false on failure
        $result = allocate_seat_atomic($its_id, $hof_id, $area_code, $seat_number);
        
        // Success if seat is allocated, failure if not
        if ($result) {
            $message = is_numeric($result) ? "Seat allocated successfully! Seat #{$result}" : 'Seat allocated successfully!';
            do_redirect_with_message($redirect_url, $message);
        } else {
            do_redirect_with_message($redirect_url, 'Seat allocation failed. Seat may be already taken or unavailable.');
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
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered">
            <tr><th class="w-25">HOF</th><td><?= ui_code($hof_id) ?> <?= h($name) ?></td></tr>
            <tr><th class="w-25">Sabeel</th><td><?= h($sabeel) ?></td></tr>
        </table>
    </div>
    
    <h4 class="mb-3">Select Seating Area for Family Members</h4>
    
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
        
        $disabled_attr = $selection_complete ? 'disabled' : '';
        
        // Area cell
        if ($is_admin) {
            $area_cell = ui_badge($att->allocated_area_name ?? '', 'info') . "<br>" . ui_muted('Assigned by Admin');
        } else if (!$misaq_done) {
            $area_cell = ui_muted('Misaq not Done');
        } else {
            // Show badge if this attendee has selected an area, otherwise show dropdown
            if (!empty($allocated_area)) {
                $area_cell = ui_badge($att->allocated_area_name ?? $allocated_area, 'info');
            } else {
                $opts = [];
                if (empty($allocated_area)) $opts[''] = '-- Select --';
                
                foreach ($eligible_areas as $a) {
                    $opts[$a->area_code] = $a->area_name;
                }
                
                if (empty($eligible_areas)) {
                    $opts[''] = 'Limit reached';
                }
                
                $select_html = ui_select('area_code', $opts, $allocated_area);
                // Add data attributes for AJAX refresh functionality
                $select_html = str_replace('<select', '<select data-its-id="' . h($its_id) . '" data-hof-id="' . h($hof_id) . '" id="area_select_' . h($its_id) . '" ' . $disabled_attr, $select_html);
                
                $area_cell = $select_html;
            }
        }
        
        // Seat cell - show input if no seat allocated, badge if allocated
        if ($is_admin) {
            $seat_cell = !empty($seat_number) ? ui_badge($seat_number, 'success') : '--';
        } else if (!$misaq_done) {
            $seat_cell = '--';
        } else {
            if (!empty($seat_number)) {
                $seat_cell = ui_badge($seat_number, 'success');
            } else {
                // Show seat input field in seat cell when no seat allocated
                $seat_cell = '<input type="number" id="seat_number_' . h($its_id) . '" class="form-control form-control-sm" style="width: 100px;" placeholder="Seat #" min="1" required ' . $disabled_attr . '>';
            }
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
                    $action_buttons[] = "<button type=\"button\" class=\"btn btn-light btn-sm\" onclick=\"showPrintModal('{$its_id}');\">Print</button>";
                }
            } else {
                // Show Save button when no allocation exists - use JavaScript to collect values
                if (!$selection_complete) {
                    $action_buttons[] = "<button type=\"button\" class=\"btn btn-light btn-sm\" onclick=\"submitSeatForm('{$its_id}');\">Save</button>";
                }
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
    
    <!-- Mobile card view (hidden on desktop)
    <div class="d-md-none">
        <?php
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
            ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-2 mb-2">
                        <div class="col-4"><small class="text-muted fw-bold">Name</small></div>
                        <div class="col-8"><strong><?= h($att->full_name) ?></strong></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><small class="text-muted fw-bold">G/Age</small></div>
                        <div class="col-8"><?= ui_ga($att->gender, $att->age) ?></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><small class="text-muted fw-bold">Chair</small></div>
                        <div class="col-8"><?= h($chair) ?></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><small class="text-muted fw-bold">Select Area</small></div>
                        <div class="col-8">
                            <?php
                            if ($is_admin) {
                                echo ui_badge($att->allocated_area_name ?? '', 'info') . "<br><small class=\"text-muted\">Assigned by Admin</small>";
                            } else if (!$misaq_done) {
                                echo '<span class="text-muted">Misaq not Done</span>';
                            } else {
                                if (!empty($allocated_area)) {
                                    echo ui_badge($att->allocated_area_name ?? $allocated_area, 'info');
                                } else {
                                    $opts = [];
                                    if (empty($allocated_area)) $opts[''] = '-- Select --';
                                    
                                    foreach ($eligible_areas as $a) {
                                        $opts[$a->area_code] = $a->area_name;
                                    }
                                    
                                    if (empty($eligible_areas)) {
                                        $opts[''] = 'Limit reached';
                                    }
                                    
                                    $disabled_attr = $selection_complete ? 'disabled' : '';
                                    $select_html = ui_select('area_code', $opts, $allocated_area);
                                    $select_html = str_replace('<select', '<select data-its-id="' . h($its_id) . '" data-hof-id="' . h($hof_id) . '" id="area_select_mobile_' . h($its_id) . '" class="form-select" ' . $disabled_attr, $select_html);
                                    echo "<form method=\"post\" id=\"form_mobile_{$its_id}\">"
                                        . "<input type=\"hidden\" name=\"action\" value=\"save_seat\">"
                                        . "<input type=\"hidden\" name=\"its_id\" value=\"{$its_id}\">"
                                        . $select_html
                                        . "</form>";
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><small class="text-muted fw-bold">Seat #</small></div>
                        <div class="col-8">
                            <?php
                            if (!empty($seat_number)) {
                                echo ui_badge($seat_number, 'success');
                            } else {
                                echo '<span class="text-muted">--</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                    if (!$is_admin && $misaq_done) {
                        $has_allocation = !empty($allocated_area) && !empty($seat_number);
                        ?>
                        <div class="row g-2 mt-3">
                            <div class="col-12">
                                <?php
                                if ($has_allocation && $selection_complete) {
                                    echo "<button type=\"button\" class=\"btn btn-success w-100\" onclick=\"showPrintModal('{$its_id}');\">Print</button>";
                                } else if (!$has_allocation) {
                                    echo "<button type=\"button\" class=\"btn btn-primary w-100\" onclick=\"document.getElementById('form_mobile_{$its_id}').submit();\">Save</button>";
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        ?>
    </div>-->
    <div class="mt-4">
        <?= ui_link('Back', "$url/input-seat-selection", 'light') ?>
    </div>
    <?php 
    ui_card_end();
    
    // Bootstrap Modal for Error Messages
    if ($is_error && !empty($transit_message)) {
        ?>
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Seat Allocation Error</h4>
					    <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mb-0"><?= h($transit_message) ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">I Understand</button>
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
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Shehrullah <?= $hijri_year ?></h4>
					    <button type="button" class="btn ms-auto" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
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
                                    <div class="row mb-3">
                                        <div class="col-3"><p class="mb-0">ITS ID:</p></div>
                                        <p class="col-9 mb-0"><strong><?= h($att->its_id) ?></strong>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-3"><p class="mb-0">Name:</p></div>
                                        <p class="col-9 mb-0"><strong><?= h($att->full_name) ?></strong>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-3"><p class="mb-0">Gender:</p></div>
                                        <p class="col-9 mb-0"><strong><?= h($att->gender) ?></strong>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-3"><p class="mb-0">Age:</p></div>
                                        <p class="col-9 mb-0"><strong><?= h($att->age) ?></strong>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-3"><p class="mb-0">Area:</p></div>
                                        <p class="col-9 mb-0"><strong><?= h($area_name) ?></strong>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-3"><p class="mb-0">Seat:</p></div>
                                        <p class="col-9 mb-0"><strong><?= h($seat_number) ?></strong>
                                    </div>
                                    <div class="row mb-3">
                                        <p class="col-12 mb-0"><small>Please carry this card for your convenience</small></p>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <script>
        function submitSeatForm(itsId) {
            // Get values from area select and seat input
            var areaSelect = document.getElementById('area_select_' + itsId);
            var seatInput = document.getElementById('seat_number_' + itsId);
            
            if (!areaSelect || !seatInput) {
                alert('Please select both area and seat number.');
                return;
            }
            
            var areaCode = areaSelect.value;
            var seatNumber = seatInput.value;
            
            if (!areaCode || areaCode === '') {
                alert('Please select an area.');
                return;
            }
            
            if (!seatNumber || seatNumber === '' || parseInt(seatNumber) <= 0) {
                alert('Please enter a valid seat number.');
                return;
            }
            
            // Create and submit form
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = window.location.href;
            
            var actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'save_seat';
            form.appendChild(actionInput);
            
            var itsIdInput = document.createElement('input');
            itsIdInput.type = 'hidden';
            itsIdInput.name = 'its_id';
            itsIdInput.value = itsId;
            form.appendChild(itsIdInput);
            
            var areaInput = document.createElement('input');
            areaInput.type = 'hidden';
            areaInput.name = 'area_code';
            areaInput.value = areaCode;
            form.appendChild(areaInput);
            
            var seatInputHidden = document.createElement('input');
            seatInputHidden.type = 'hidden';
            seatInputHidden.name = 'seat_number';
            seatInputHidden.value = seatNumber;
            form.appendChild(seatInputHidden);
            
            document.body.appendChild(form);
            form.submit();
        }
        
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
                        var dropdown = form.querySelector('select[data-its-id]');
                        if (dropdown) {
                            refreshAreaDropdown(itsId, dropdown.id, function() {
                                // Continue with form submission after refresh
                                // Don't prevent default - let form submit normally
                            });
                        }
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
                refreshAreaDropdown(itsId, dropdown.id, function(updated) {
                    if (updated && currentValue && !dropdown.querySelector('option[value="' + currentValue + '"]')) {
                        // If current selection is no longer available, clear it
                        dropdown.value = '';
                        // Show a subtle notification
                        showAreaUnavailableNotification(itsId);
                    }
                });
            });
        }
        
        function refreshAreaDropdown(itsId, dropdownId, callback) {
            var dropdown = document.getElementById(dropdownId);
            if (!dropdown) {
                // Try alternative ID (desktop/mobile)
                dropdown = document.getElementById('area_select_' + itsId) || document.getElementById('area_select_mobile_' + itsId);
            }
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
                    
                    // Also update the other dropdown (desktop/mobile) if it exists
                    var otherDropdownId = dropdownId === 'area_select_' + itsId ? 'area_select_mobile_' + itsId : 'area_select_' + itsId;
                    var otherDropdown = document.getElementById(otherDropdownId);
                    if (otherDropdown) {
                        var otherSelectedValue = otherDropdown.value;
                        otherDropdown.innerHTML = '';
                        if (isEmptyOption) {
                            var emptyOpt2 = document.createElement('option');
                            emptyOpt2.value = '';
                            emptyOpt2.textContent = '-- Select --';
                            otherDropdown.appendChild(emptyOpt2);
                        }
                        var hasOtherSelection = false;
                        data.areas.forEach(function(area) {
                            var option = document.createElement('option');
                            option.value = area.area_code;
                            option.textContent = area.area_name;
                            if (otherSelectedValue === area.area_code) {
                                option.selected = true;
                                hasOtherSelection = true;
                            }
                            otherDropdown.appendChild(option);
                        });
                        if (data.areas.length === 0) {
                            var limitOpt2 = document.createElement('option');
                            limitOpt2.value = '';
                            limitOpt2.textContent = 'Limit reached';
                            otherDropdown.appendChild(limitOpt2);
                        }
                        if (hasOtherSelection) {
                            otherDropdown.value = otherSelectedValue;
                        }
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
            var dropdown = document.getElementById('area_select_' + itsId) || document.getElementById('area_select_mobile_' + itsId);
            if (dropdown) {
                // Add Bootstrap danger border class temporarily
                dropdown.classList.add('border-danger');
                setTimeout(function() {
                    dropdown.classList.remove('border-danger');
                }, 2000);
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

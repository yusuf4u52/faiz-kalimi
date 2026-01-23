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
    
    <!-- HOF/Sabeel Info - Combined -->
    <div class="card border mb-3 mt-2">
        <div class="card-body p-2 py-2">
            <div class="mb-2 pb-2 border-bottom">
                <small class="text-muted d-block mb-1">HOF ID</small>
                <strong class="d-block mb-1"><?= h($hof_id) ?></strong>
                <small class="text-muted d-block mb-0"><?= h($name) ?></small>
            </div>
            <div class="mb-0">
                <small class="text-muted d-block mb-1">Sabeel</small>
                <strong class="d-block mb-0"><?= h($sabeel) ?></strong>
            </div>
        </div>
    </div>
    
    <!-- Section Divider -->
    <hr class="my-3">
    
    <!-- Mobile-First Card Layout - Progressive Enhancement for Larger Screens -->
    <div class="row g-3 g-md-4 mb-2">
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
            <!-- Card: Mobile-first, enhanced for larger screens -->
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 attendee-card">
                    <div class="card-header attendee-card-header">
                        <strong class="text-primary d-block mb-1"><?= h($att->full_name) ?></strong>
                        <small class="text-muted"><?= ui_ga($att->gender, $att->age) ?></small>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <!-- Chair Preference -->
                        <div class="mb-4">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-chair me-1"></i>Chair Preference
                            </small>
                            <div class="fw-semibold" style="padding-left: calc(1em + 0.25rem);"><?= h($chair) ?></div>
                        </div>
                        
                        <!-- Seat Number -->
                        <div class="mb-4">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-ticket-alt me-1"></i>Seat Number
                            </small>
                            <div style="padding-left: calc(1em + 0.25rem);">
                                <?php
                                if (!empty($seat_number)) {
                                    echo '<span class="badge bg-success fs-6">' . h($seat_number) . '</span>';
                                } else {
                                    echo '<span class="text-muted small">Not allocated</span>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Area Selection Section -->
                        <?php
                        // Check if action button will be shown
                        $will_show_button = !$is_admin && $misaq_done;
                        $has_allocation = !empty($allocated_area) && !empty($seat_number);
                        
                        // Only show separator when Save Selection button will be shown (not when Print button)
                        $show_save_button = $will_show_button && !$has_allocation;
                        $border_class = $show_save_button ? 'border-bottom pb-3' : '';
                        ?>
                        <div class="mb-4 <?= $border_class ?>">
                            <small class="text-muted d-block mb-2 fw-bold">
                                <i class="fas fa-map-marker-alt me-1"></i>Seating Area
                            </small>
                            <div>
                                <?php
                                if ($is_admin) {
                                    echo '<div class="d-flex align-items-center gap-2">';
                                    echo ui_badge($att->allocated_area_name ?? '', 'info');
                                    echo '<small class="text-muted">(Admin assigned)</small>';
                                    echo '</div>';
                                } else if (!$misaq_done) {
                                    echo '<span class="badge bg-secondary">Misaq not Done</span>';
                                } else {
                                    if (!empty($allocated_area)) {
                                        echo ui_badge($att->allocated_area_name ?? $allocated_area, 'primary');
                                    } else {
                                        $opts = [];
                                        if (empty($allocated_area)) $opts[''] = '-- Select Area --';
                                        
                                        foreach ($eligible_areas as $a) {
                                            $opts[$a->area_code] = $a->area_name;
                                        }
                                        
                                        if (empty($eligible_areas)) {
                                            $opts[''] = 'Limit reached';
                                        }
                                        
                                        $disabled_attr = $selection_complete ? 'disabled' : '';
                                        $select_html = ui_select('area_code', $opts, $allocated_area);
                                        // Remove form-select-sm for better mobile experience
                                        $select_html = str_replace('form-select-sm', 'form-select', $select_html);
                                        $select_html = str_replace('<select', '<select data-its-id="' . h($its_id) . '" data-hof-id="' . h($hof_id) . '" id="area_select_' . h($its_id) . '" class="form-select" ' . $disabled_attr, $select_html);
                                        echo "<form method=\"post\" id=\"form_{$its_id}\" class=\"mb-0\">"
                                            . "<input type=\"hidden\" name=\"action\" value=\"save_seat\">"
                                            . "<input type=\"hidden\" name=\"its_id\" value=\"{$its_id}\">"
                                            . $select_html
                                            . "</form>";
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Action Button -->
                        <?php
                        if ($will_show_button) {
                            ?>
                            <div class="mt-auto">
                                <?php
                                if ($has_allocation && $selection_complete) {
                                    echo "<button type=\"button\" class=\"btn btn-success w-100\" onclick=\"showPrintModal('{$its_id}');\">"
                                        . "<i class=\"fas fa-print me-2\"></i>Print Seat Card"
                                        . "</button>";
                                } else if (!$has_allocation) {
                                    echo "<button type=\"button\" class=\"btn btn-primary w-100\" onclick=\"document.getElementById('form_{$its_id}').submit();\">"
                                        . "<i class=\"fas fa-save me-2\"></i>Save Selection"
                                        . "</button>";
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    
    <!-- Section Divider -->
    <hr class="my-5">
    
    <!-- Navigation -->
    <div class="mt-3 mb-2">
        <?= ui_link('Back', "$url/input-seat-selection", 'secondary') ?>
    </div>
    <?php 
    ui_card_end();
    
    // Bootstrap Modal for Error Messages
    if ($is_error && !empty($transit_message)) {
        ?>
        <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-danger" style="border-width: 3px;">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="errorModalLabel">
                            <i class="fas fa-exclamation-triangle"></i> Seat Allocation Error
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center px-3 py-4">
                        <p class="mb-0" style="font-size: clamp(1rem, 2.5vw, 1.1rem);"><strong><?= h($transit_message) ?></strong></p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-danger btn-lg w-100 w-md-auto" data-bs-dismiss="modal">I Understand</button>
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
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="printModalLabel">
                            <i class="fas fa-ticket-alt me-2"></i>Shehrullah <?= $hijri_year ?> - Seat Card
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3 p-md-4">
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
                                    <div class="card border-success shadow-sm">
                                        <div class="card-header bg-success text-white text-center py-3">
                                            <h6 class="mb-0">Seat Card</h6>
                                        </div>
                                        <div class="card-body p-4">
                                            <div class="row g-3 mb-3">
                                                <div class="col-12 col-md-6">
                                                    <small class="text-muted d-block mb-1">Name</small>
                                                    <strong class="fs-5"><?= h($att->full_name) ?></strong>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <small class="text-muted d-block mb-1">ITS ID</small>
                                                    <strong class="fs-6"><?= ui_code($att->its_id) ?></strong>
                                                </div>
                                            </div>
                                            <div class="row g-3 mb-3">
                                                <div class="col-12 col-md-6">
                                                    <small class="text-muted d-block mb-1">Area</small>
                                                    <strong class="fs-5"><?= h($area_name) ?></strong>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <small class="text-muted d-block mb-1">Seat Number</small>
                                                    <strong class="fs-4 text-success"><?= h($seat_number) ?></strong>
                                                </div>
                                            </div>
                                            <div class="text-center text-muted mt-4 pt-3 border-top">
                                                <small><i class="fas fa-info-circle me-1"></i>Please carry this card for your convenience</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print();">
                                            <i class="fas fa-print me-1"></i>Print This Card
                                        </button>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary w-100 w-md-auto" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <style>
        /* Optimize for mobile - maximize screen width */
        @media (max-width: 767.98px) {
            /* Reduce container padding on mobile */
            .content-wrapper {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }
            
            /* Make card use full width with minimal side margins */
            .card.mb-3 {
                margin-left: 0 !important;
                margin-right: 0 !important;
                margin-bottom: 1rem !important;
                border-radius: 0.5rem;
            }
            
            /* Reduce horizontal padding inside card on mobile */
            .card > .card-body {
                padding: 1.25rem 1rem !important;
            }
            
            /* Compact padding for HOF/Sabeel combined card on mobile */
            .card.border .card-body {
                padding: 0.75rem 0.875rem !important;
            }
        }
        
        /* Reduce outer padding of main card container */
        .card.mb-3 {
            margin-bottom: 1rem !important;
        }
        
        /* Increase inner padding of main card body for larger screens */
        .card > .card-body {
            padding: 1.5rem 1.25rem !important;
        }
        
        @media (min-width: 768px) {
            .card > .card-body {
                padding: 2rem 1.5rem !important;
            }
        }
        
        /* Section Separators */
        hr {
            border: none;
            border-top: 2px solid #e9ecef;
            opacity: 1;
        }
        
        /* Mobile-First Card Layout - Make cards distinct blocks */
        .attendee-card {
            border: 1px solid #dee2e6 !important;
            border-radius: 0.5rem !important;
            background-color: #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.05) !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            margin-bottom: 0;
        }
        
        .attendee-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12), 0 2px 6px rgba(0, 0, 0, 0.08) !important;
            border-color: #adb5bd !important;
        }
        
        /* Card header styling for better separation */
        .attendee-card-header {
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%) !important;
            border-bottom: 2px solid #e9ecef !important;
            padding: 1rem 1.25rem !important;
            border-top-left-radius: 0.5rem !important;
            border-top-right-radius: 0.5rem !important;
        }
        
        @media (min-width: 768px) {
            .attendee-card-header {
                padding: 1.25rem 1.5rem !important;
            }
        }
        
        /* Add subtle background to card body for depth */
        .attendee-card .card-body {
            background-color: #ffffff;
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }
        
        /* Mobile optimizations - maximize width usage */
        @media (max-width: 575.98px) {
            .attendee-card .card-body {
                padding: 1rem 0.875rem !important;
            }
            .attendee-card-header {
                padding: 0.875rem 1rem !important;
            }
            
            /* Maintain good spacing between cards on mobile */
            .row.g-3 {
                --bs-gutter-x: 0.875rem;
                --bs-gutter-y: 1rem;
            }
        }
        
        @media (max-width: 767.98px) {
            .row.g-3 {
                --bs-gutter-x: 1rem;
                --bs-gutter-y: 1.25rem;
            }
        }
        
        /* Tablet and up - enhance card layout */
        @media (min-width: 768px) {
            .attendee-card {
                min-height: 100%;
            }
            .attendee-card .card-body {
                display: flex;
                flex-direction: column;
                padding: 1.75rem !important;
            }
        }
        
        /* Desktop - grid layout optimization */
        @media (min-width: 992px) {
            .attendee-card .card-body {
                padding: 2rem !important;
            }
        }
        
        /* Touch-friendly improvements */
        @media (hover: none) and (pointer: coarse) {
            .btn {
                min-height: 44px;
                padding: 0.625rem 1rem;
            }
            select.form-select {
                min-height: 44px;
                font-size: 1rem;
            }
        }
        
        /* Print styles for seat cards */
        @media print {
            body * {
                visibility: hidden;
            }
            .seat-card,
            .seat-card * {
                visibility: visible;
            }
            .seat-card {
                position: absolute;
                left: 0;
                top: 0;
                page-break-after: always;
            }
            .modal-dialog {
                max-width: 100%;
                margin: 0;
            }
            .modal-content {
                border: none;
                box-shadow: none;
            }
            .modal-header,
            .modal-footer {
                display: none;
            }
            .btn {
                display: none;
            }
        }
        
        /* Badge improvements for better visibility */
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }
        
        /* Form select improvements */
        .form-select {
            border-radius: 0.375rem;
        }
        
        /* HOF/Sabeel combined metadata card - consistent compact styling */
        .card.border .card-body {
            padding: 0.75rem 1rem;
        }
        
        @media (min-width: 768px) {
            .card.border .card-body {
                padding: 0.875rem 1.125rem;
            }
        }
        
        /* Additional visual separation for cards */
        .row.g-3 > [class*="col-"] {
            margin-bottom: 1rem;
        }
        
        @media (min-width: 768px) {
            .row.g-3 > [class*="col-"] {
                margin-bottom: 1.5rem;
            }
        }
    </style>
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
            
            // Add print media query for seat cards
            if (window.matchMedia) {
                var mediaQuery = window.matchMedia('print');
                mediaQuery.addListener(function(mq) {
                    if (mq.matches) {
                        // When printing, show all seat cards
                        var allSeats = document.querySelectorAll('.seat-card');
                        allSeats.forEach(function(seat) {
                            seat.style.display = 'block';
                        });
                    }
                });
            }
            
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
                // Try alternative ID (unified ID for all screen sizes)
                dropdown = document.getElementById('area_select_' + itsId);
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
                    
                    // Note: With unified IDs, we only have one dropdown per attendee now
                    
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

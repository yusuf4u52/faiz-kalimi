<?php

initial_processing();
do_for_post('_handle_form_submit');

function initial_processing()
{
    $en_sabeel = getAppData('arg1');
    $sabeel = do_decrypt($en_sabeel);

    $sabeel_data = get_thaalilist_data($sabeel);
    if (is_null($sabeel_data)) {
        do_redirect_with_message('/input-seat-selection', 'No records found. Please enter correct sabeel number or HOF ITS.');
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

    $attendees = get_attendees_for_seat_selection($hof_id);
    if (empty($attendees)) {
        do_redirect_with_message('/input-seat-selection', 'No eligible family members found for seat selection.');
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
            setSessionData(TRANSIT_DATA, 'Invalid selection. Please try again.');
            return;
        }
        
        // Check if admin pre-allocated (cannot change)
        if (is_admin_allocated($its_id)) {
            setSessionData(TRANSIT_DATA, 'This seat was assigned by admin and cannot be changed.');
            return;
        }
        
        // Validate area is eligible for this member
        $eligible_areas = get_eligible_areas_for_attendee($its_id, $hof_id);
        $area_codes = array_map(function($a) { return $a->area_code; }, $eligible_areas);
        
        if (!in_array($area_code, $area_codes)) {
            setSessionData(TRANSIT_DATA, 'Selected area is not available for this member.');
            return;
        }
        
        // Save allocation
        $success = allocate_seat($its_id, $hof_id, $area_code);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Seat selection saved successfully!');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to save seat selection. Please try again.');
        }
        
        // Refresh attendees data
        $attendees = get_attendees_for_seat_selection($hof_id);
        setAppData('attendees', $attendees);
    }
}

function content_display()
{
    $sabeel_data = getAppData('sabeel_data');
    $hof_id = getAppData('hof_id');
    $hijri_year = getAppData('hijri_year');
    $attendees = getAppData('attendees');
    
    $name = $sabeel_data->NAME ?? '';
    $sabeel = $sabeel_data->Thali ?? '';
    $whatsapp = $sabeel_data->WhatsApp ?? '';
    
    $arg1 = getAppData('arg1');
    ?>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Seat Selection - Shehrullah <?= $hijri_year ?>H</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-warning" role="alert">
                <strong>Important:</strong> Seat selection is on <strong>first come first serve</strong> basis. 
                Please complete your selection promptly.
            </div>
            
            <table class="table table-bordered mb-4">
                <tr>
                    <th style="width: 20%">HOF</th>
                    <td>[<?= $hof_id ?>] <?= $name ?></td>
                </tr>
                <tr>
                    <th>Sabeel</th>
                    <td><?= $sabeel ?></td>
                </tr>
            </table>
            
            <h5 class="mb-3">Select Seating Area for Family Members</h5>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>SN</th>
                            <th>Name</th>
                            <th>Gender/Age</th>
                            <th>Chair</th>
                            <th>Select Area</th>
                            <th>Seat #</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $index = 0;
                        foreach ($attendees as $attendee) {
                            $index++;
                            $its_id = $attendee->its_id;
                            $full_name = $attendee->full_name;
                            $gender = substr($attendee->gender, 0, 1);
                            $age = $attendee->age;
                            $chair = $attendee->chair_preference == 'Y' ? 'Yes' : 'No';
                            $allocated_area = $attendee->allocated_area ?? '';
                            $allocated_area_name = $attendee->allocated_area_name ?? '';
                            $seat_number = $attendee->seat_number ?? '';
                            $is_admin = !empty($attendee->allocated_by);
                            
                            // Get eligible areas for this attendee
                            $eligible_areas = get_eligible_areas_for_attendee($its_id, $hof_id);
                            
                            echo "<tr>";
                            echo "<td>$index</td>";
                            echo "<td>$full_name</td>";
                            echo "<td>$gender/$age</td>";
                            echo "<td>$chair</td>";
                            
                            // Area dropdown or display
                            echo "<td>";
                            if ($is_admin) {
                                // Admin allocated - show as read-only
                                echo "<span class='badge bg-info'>$allocated_area_name</span>";
                                echo "<br><small class='text-muted'>Assigned by Admin</small>";
                            } else {
                                echo "<form method='post' class='d-inline seat-form' id='form_$its_id'>";
                                echo "<input type='hidden' name='action' value='save_seat'>";
                                echo "<input type='hidden' name='its_id' value='$its_id'>";
                                echo "<select name='area_code' class='form-control form-control-sm' required>";
                                
                                if (empty($allocated_area)) {
                                    echo "<option value=''>-- Select --</option>";
                                }
                                
                                foreach ($eligible_areas as $area) {
                                    $selected = ($area->area_code == $allocated_area) ? 'selected' : '';
                                    $chair_badge = ($area->chairs_allowed == 'Y') ? ' [Chairs]' : '';
                                    echo "<option value='{$area->area_code}' $selected>{$area->area_name}$chair_badge</option>";
                                }
                                
                                if (empty($eligible_areas)) {
                                    echo "<option value='' disabled>No areas available</option>";
                                }
                                
                                echo "</select>";
                                echo "</form>";
                            }
                            echo "</td>";
                            
                            // Seat number
                            echo "<td>";
                            if (!empty($seat_number)) {
                                echo "<span class='badge bg-success'>$seat_number</span>";
                            } else if (!empty($allocated_area)) {
                                echo "<span class='badge bg-warning text-dark'>Pending</span>";
                            } else {
                                echo "--";
                            }
                            echo "</td>";
                            
                            // Action button
                            echo "<td>";
                            if ($is_admin) {
                                echo "<span class='text-muted'>--</span>";
                            } else if (!empty($eligible_areas)) {
                                echo "<button type='button' class='btn btn-primary btn-sm' onclick=\"document.getElementById('form_$its_id').submit();\">Save</button>";
                            } else {
                                echo "<span class='text-muted'>N/A</span>";
                            }
                            echo "</td>";
                            
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                <a href="<?= getAppData('BASE_URI') ?>/input-seat-selection" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>
    
    <script>
        function the_script() {
            // No additional scripts needed for now
        }
    </script>
    <?php
}

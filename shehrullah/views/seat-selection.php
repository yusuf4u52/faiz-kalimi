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
    $url = getAppData('BASE_URI');
    
    $name = $sabeel_data->NAME ?? '';
    $sabeel = $sabeel_data->Thali ?? '';
    
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
                $chair_tag = ($a->chairs_allowed == 'Y') ? ' [Chairs]' : '';
                $opts[$a->area_code] = $a->area_name . $chair_tag;
            }
            if (empty($eligible_areas)) $opts[''] = 'No areas available';
            
            $area_cell = "<form method=\"post\" class=\"d-inline\" id=\"form_{$its_id}\">"
                . "<input type=\"hidden\" name=\"action\" value=\"save_seat\">"
                . "<input type=\"hidden\" name=\"its_id\" value=\"{$its_id}\">"
                . ui_select('area_code', $opts, $allocated_area)
                . "</form>";
        }
        
        // Seat cell
        if (!empty($seat_number)) {
            $seat_cell = ui_badge($seat_number, 'success');
        } elseif (!empty($allocated_area)) {
            $seat_cell = ui_badge('Pending', 'warning');
        } else {
            $seat_cell = '--';
        }
        
        // Action cell
        if ($is_admin) {
            $action_cell = ui_muted('--');
        } elseif (!empty($eligible_areas)) {
            $action_cell = "<button type=\"button\" class=\"btn btn-primary btn-sm\" onclick=\"document.getElementById('form_{$its_id}').submit();\">Save</button>";
        } else {
            $action_cell = ui_muted('N/A');
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
    ?>
    <script>function the_script() {}</script>
    <?php
}

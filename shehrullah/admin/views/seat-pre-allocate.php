<?php

if (!is_user_a(SUPER_ADMIN)) {
    do_redirect_with_message('/home', 'Access denied. SUPER_ADMIN role required.');
}

do_for_post('_handle_post');

function _handle_post()
{
    $action = $_POST['action'] ?? '';
    
    if ($action === 'search') {
        $sabeel = $_POST['sabeel'] ?? '';
        if (!empty($sabeel)) {
            $thaali_data = get_thaalilist_data($sabeel);
            if (is_null($thaali_data)) {
                setSessionData(TRANSIT_DATA, 'No records found for: ' . $sabeel);
                return;
            }
            $hof_id = $thaali_data->ITS_No;
            $hijri_year = get_current_hijri_year();
            
            // Check if takhmeen is done
            $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
            if (is_null($takhmeen_data) || $takhmeen_data->takhmeen <= 0) {
                setSessionData(TRANSIT_DATA, 'Takhmeen is not done yet for this family. Please complete takhmeen first.');
                return;
            }
            
            setAppData('hof_id', $hof_id);
            setAppData('thaali_data', $thaali_data);
            setAppData('takhmeen_data', $takhmeen_data);
            
            // Get attendees for this family
            $attendees = get_attendees_for_seat_selection($hof_id);
            setAppData('attendees', $attendees);
        }
    } else if ($action === 'pre_allocate') {
        $its_id = $_POST['its_id'] ?? '';
        $hof_id = $_POST['hof_id'] ?? '';
        $area_code = $_POST['area_code'] ?? '';
        $seat_number = $_POST['seat_number'] ?? null;
        
        if (empty($seat_number)) {
            $seat_number = null;
        }
        
        if (empty($its_id) || empty($hof_id) || empty($area_code)) {
            setSessionData(TRANSIT_DATA, 'Please fill all required fields.');
            return;
        }
        
        $userData = getSessionData(THE_SESSION_ID);
        $allocated_by = $userData->itsid ?? '';
        
        $success = admin_pre_allocate_seat($its_id, $hof_id, $area_code, $seat_number, $allocated_by);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Seat pre-allocated successfully!');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to pre-allocate seat. Seat may already be taken by someone else.');
        }
        
        // Refresh data
        setAppData('hof_id', $hof_id);
        $thaali_data = get_thaalilist_data($hof_id);
        setAppData('thaali_data', $thaali_data);
        
        $hijri_year = get_current_hijri_year();
        $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
        setAppData('takhmeen_data', $takhmeen_data);
        
        $attendees = get_attendees_for_seat_selection($hof_id);
        setAppData('attendees', $attendees);
    }
}

function content_display()
{
    $url = getAppData('BASE_URI');
    $areas = get_seating_areas();
    
    $hof_id = getAppData('hof_id');
    $thaali_data = getAppData('thaali_data');
    $takhmeen_data = getAppData('takhmeen_data');
    $attendees = getAppData('attendees') ?? [];
    
    // Build area options and get available blocked seats for each area
    $area_opts = [];
    $area_blocked_seats = [];
    foreach ($areas as $a) {
        $area_opts[$a->area_code] = $a->area_name;
        $blocked = get_available_blocked_seats($a->area_code);
        if (!empty($blocked)) {
            $area_blocked_seats[$a->area_code] = $blocked;
        }
    }
    
    ui_card('Pre-Allocate Seats', 'Bypass rules and assign seats directly', "$url/seat-management");
    ?>
    <form method="post" class="mb-3">
        <input type="hidden" name="action" value="search">
        <div class="input-group input-group-sm" style="max-width: 400px;">
            <?= ui_input('sabeel', '', 'Sabeel or HOF ID') ?>
            <?= ui_btn('Search', 'primary') ?>
        </div>
    </form>
    
    <?php if ($thaali_data) { 
        ui_hr();
        
        // Show available blocked seats after search
        if (!empty($area_blocked_seats)) { ?>
    <div class="mb-4">
        <h6 class="text-muted mb-2">Available Blocked Seats by Area</h6>
        <div class="row g-2">
            <?php foreach ($area_blocked_seats as $area_code => $blocked_seats) { 
                $area_name = $area_opts[$area_code] ?? $area_code;
                $seat_numbers = array_map(function($s) { return $s->seat_number; }, $blocked_seats);
                $seat_list = implode(', ', $seat_numbers);
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-body py-2 px-3">
                    <div class="small">
                        <strong><?= h($area_name) ?></strong>
                        <div class="text-muted" style="font-size: 0.85rem;">
                            Seats: <?= h($seat_list) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
        <?php } 
    ?>
    <div class="mb-3">
        <strong><?= h($thaali_data->NAME) ?></strong> <?= ui_code($hof_id) ?> 
        <?= ui_muted("• Sabeel {$thaali_data->Thali}") ?>
    </div>
    
    <?php if ($takhmeen_data) { 
        $pending = $takhmeen_data->takhmeen - $takhmeen_data->paid_amount;
        $is_fully_paid = $pending <= 0;
        $payment_badge = $is_fully_paid ? 'success' : 'warning';
        $payment_text = $is_fully_paid ? 'PAID' : 'PENDING: Rs. ' . number_format($pending);
    ?>
    <div class="mb-3">
        <small class="text-muted">Takhmeen: <?= ui_money($takhmeen_data->takhmeen) ?> | 
        Paid: <?= ui_money($takhmeen_data->paid_amount) ?> | 
        <span class="badge bg-<?= $payment_badge ?>"><?= $payment_text ?></span>
        </small>
    </div>
    <?php } ?>
    
    <?php if (empty($attendees)) { 
        ui_alert('No eligible family members found (must have Misaq and be attending).', 'warning');
    } else { 
        ui_table(['Member', 'G/Age', 'Chair', 'Current', 'Area', 'Seat #', '']);
        
        foreach ($attendees as $att) {
            $chair = $att->chair_preference == 'Y' ? '✓' : '';
            $current_area = $att->allocated_area_name ?? '—';
            $current_seat = $att->seat_number ?? '';
            $seat_display = $current_seat ? "<br><strong class=\"text-success\">#{$current_seat}</strong>" : '';
            ?>
            <tr>
                <form method="post" class="contents">
                    <input type="hidden" name="action" value="pre_allocate">
                    <input type="hidden" name="its_id" value="<?= h($att->its_id) ?>">
                    <input type="hidden" name="hof_id" value="<?= h($hof_id) ?>">
                    <td><?= h($att->full_name) ?><br><?= ui_code($att->its_id) ?></td>
                    <td><?= ui_ga($att->gender, $att->age) ?></td>
                    <td><?= ui_muted($chair) ?></td>
                    <td><?= ui_muted($current_area) . $seat_display ?></td>
                    <td><?= ui_select('area_code', $area_opts, $att->allocated_area ?? '', 'Select...') ?></td>
                    <td><?= ui_input('seat_number', $current_seat, 'Auto', 'number', 'width:70px') ?></td>
                    <td><?= ui_btn('Assign', 'primary') ?></td>
                </form>
            </tr>
            <?php
        }
        ui_table_end();
    }
    } ?>
    <?php ui_card_end();
}

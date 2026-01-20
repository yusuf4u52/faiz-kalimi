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
            setAppData('hof_id', $hof_id);
            setAppData('thaali_data', $thaali_data);
            
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
            setSessionData(TRANSIT_DATA, 'Failed to pre-allocate seat.');
        }
        
        // Refresh data
        setAppData('hof_id', $hof_id);
        $thaali_data = get_thaalilist_data($hof_id);
        setAppData('thaali_data', $thaali_data);
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
    $attendees = getAppData('attendees') ?? [];
    
    // Build area options
    $area_opts = [];
    foreach ($areas as $a) $area_opts[$a->area_code] = $a->area_name;
    
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
    ?>
    <div class="mb-3">
        <strong><?= h($thaali_data->NAME) ?></strong> <?= ui_code($hof_id) ?> 
        <?= ui_muted("• Sabeel {$thaali_data->Thali}") ?>
    </div>
    
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

<?php

if (!is_user_a(SUPER_ADMIN)) {
    do_redirect_with_message('/home', 'Access denied. SUPER_ADMIN role required.');
}

do_for_post('_handle_post');

function _handle_post()
{
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_area') {
        $area_code = $_POST['area_code'] ?? '';
        $area_name = trim($_POST['area_name'] ?? '');
        $seat_start = $_POST['seat_start'] ?? null;
        $seat_end = $_POST['seat_end'] ?? null;
        $is_active = $_POST['is_active'] ?? 'Y';
        $blocked_seats_json = $_POST['blocked_seats'] ?? '[]';
        
        if (empty($seat_start)) $seat_start = null;
        if (empty($seat_end)) $seat_end = null;
        
        $success = update_seating_area($area_code, $area_name, $seat_start, $seat_end, $is_active);
        
        if ($success) {
            $blocked_seats = json_decode($blocked_seats_json, true);
            $userData = getSessionData(THE_SESSION_ID);
            $blocked_by = $userData->itsid ?? '';
            
            $current_blocked = get_blocked_seats($area_code);
            $current_blocked_numbers = array_map(function($b) { return $b->seat_number; }, $current_blocked);
            $new_blocked_numbers = array_map(function($b) { return $b['seat_number']; }, $blocked_seats);
            
            foreach ($current_blocked_numbers as $seat_num) {
                if (!in_array($seat_num, $new_blocked_numbers)) {
                    unblock_seat($area_code, $seat_num);
                }
            }
            
            foreach ($blocked_seats as $seat) {
                block_seat($area_code, $seat['seat_number'], $seat['reason'], $blocked_by);
            }
            
            setSessionData(TRANSIT_DATA, 'Changes saved successfully');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to update area');
        }
        do_redirect('/seating-areas');
    }
}

function content_display()
{
    $hijri_year = get_current_hijri_year();
    $url = getAppData('BASE_URI');
    $areas = get_seating_areas(false);
    $edit_area = $_GET['edit'] ?? '';
    
    if (!empty($edit_area)) {
        show_edit_area_page($edit_area, $url);
        return;
    }
    
    ui_card("Seating Areas - {$hijri_year}H", '', "$url/seat-management");
    ui_table(['Name', 'Gender', 'Seats', 'Status', '']);
    
    foreach ($areas as $area) {
        $seats = ($area->seat_start && $area->seat_end) 
            ? ui_muted("{$area->seat_start}-{$area->seat_end}") 
            : ui_muted('—');
        ui_tr([
            h($area->area_name),
            ui_muted($area->gender),
            $seats,
            ui_dot($area->is_active == 'Y'),
            ui_link('Edit', "?edit={$area->area_code}", 'link')
        ]);
    }
    
    ui_table_end();
    ui_card_end();
}

function show_edit_area_page($area_code, $url)
{
    $area = get_seating_area($area_code, false);
    if (!$area) {
        ui_alert('Area not found', 'danger');
        return;
    }
    
    $blocked_seats = get_blocked_seats($area_code);
    $subtitle = "{$area->gender} • Ages {$area->min_age}+ • Max " . ($area->max_seats_per_family ?: '∞') . "/family";
    
    ui_card("{$area->area_name} " . ui_code($area->area_code), $subtitle, '?');
    ?>
    <form method="post" id="editForm">
        <input type="hidden" name="action" value="update_area">
        <input type="hidden" name="area_code" value="<?= h($area->area_code) ?>">
        <input type="hidden" name="blocked_seats" id="blockedSeatsData">
        
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label small">Area Name</label>
                <?= ui_input('area_name', $area->area_name) ?>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Seat Start</label>
                <?= ui_input('seat_start', $area->seat_start, '—', 'number') ?>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Seat End</label>
                <?= ui_input('seat_end', $area->seat_end, '—', 'number') ?>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Status</label>
                <?= ui_select('is_active', ['Y' => 'Active', 'N' => 'Inactive'], $area->is_active) ?>
            </div>
        </div>
        
        <?php ui_hr(); ?>
        
        <label class="form-label small mb-2">Block Seats</label>
        <div class="input-group input-group-sm mb-3" style="max-width: 500px;">
            <input type="number" id="seatFrom" class="form-control" placeholder="From" style="max-width:80px">
            <input type="number" id="seatTo" class="form-control" placeholder="To" style="max-width:80px">
            <input type="text" id="seatReason" class="form-control" placeholder="Reason (optional)">
            <button type="button" class="btn btn-outline-warning" onclick="addSeats()">Block</button>
        </div>
        
        <div id="blockedList" class="mb-3">
            <div class="text-muted small">No blocked seats</div>
        </div>
        
        <?php ui_hr(); ?>
        
        <?= ui_btn('Save', 'primary') ?>
        <?= ui_link('Cancel', '?', 'link') ?>
    </form>
    <?php ui_card_end(); ?>
    
    <script>
    let blocked = <?= json_encode($blocked_seats) ?>.map(s => ({
        seat_number: parseInt(s.seat_number),
        reason: s.reason || ''
    }));
    
    function render() {
        blocked.sort((a, b) => a.seat_number - b.seat_number);
        const container = document.getElementById('blockedList');
        
        if (blocked.length === 0) {
            container.innerHTML = '<div class="text-muted small">No blocked seats</div>';
        } else {
            container.innerHTML = `
                <div class="mb-1 small text-muted">${blocked.length} blocked</div>
                <div class="d-flex flex-wrap gap-2">
                    ${blocked.map(s => `
                        <span class="badge bg-danger d-flex align-items-center gap-1">
                            ${s.seat_number}${s.reason ? ': ' + s.reason : ''}
                            <button type="button" class="btn-close btn-close-white" style="font-size:0.6rem" 
                                onclick="remove(${s.seat_number})"></button>
                        </span>
                    `).join('')}
                </div>
            `;
        }
        
        document.getElementById('blockedSeatsData').value = JSON.stringify(blocked);
    }
    
    function addSeats() {
        const from = parseInt(document.getElementById('seatFrom').value);
        const to = parseInt(document.getElementById('seatTo').value) || from;
        const reason = document.getElementById('seatReason').value.trim();
        
        if (!from) return;
        if (from > to) return;
        
        for (let i = from; i <= to; i++) {
            if (!blocked.some(s => s.seat_number === i)) {
                blocked.push({ seat_number: i, reason });
            }
        }
        
        document.getElementById('seatFrom').value = '';
        document.getElementById('seatTo').value = '';
        document.getElementById('seatReason').value = '';
        render();
    }
    
    function remove(num) {
        blocked = blocked.filter(s => s.seat_number !== num);
        render();
    }
    
    document.getElementById('seatFrom').addEventListener('keypress', e => {
        if (e.key === 'Enter') { e.preventDefault(); addSeats(); }
    });
    
    render();
    </script>
    <?php
}

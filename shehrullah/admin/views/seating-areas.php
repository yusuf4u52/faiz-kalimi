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
        do_redirect('/seat-management');
    }
}

function content_display()
{
    $hijri_year = get_current_hijri_year();
    $url = getAppData('BASE_URI');
    $areas = get_seating_areas();
    $edit_area = $_GET['edit'] ?? '';
    
    if (!empty($edit_area)) {
        show_edit_area_page($edit_area, $url);
        return;
    }
    ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Seating Areas - <?= $hijri_year ?>H</h5>
            <a href="<?= $url ?>/seat-management" class="btn btn-sm btn-secondary">← Back</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Seat Range</th>
                        <th>Status</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areas as $area) { ?>
                    <tr>
                        <td><code><?= $area->area_code ?></code></td>
                        <td><?= $area->area_name ?></td>
                        <td><?= $area->gender ?></td>
                        <td>
                            <?php if ($area->seat_start && $area->seat_end) { ?>
                                <?= $area->seat_start ?>-<?= $area->seat_end ?>
                            <?php } else { ?>
                                <span class="text-muted">—</span>
                            <?php } ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $area->is_active == 'Y' ? 'success' : 'secondary' ?>">
                                <?= $area->is_active == 'Y' ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <a href="?edit=<?= $area->area_code ?>" class="btn btn-sm btn-primary">Edit</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function show_edit_area_page($area_code, $url)
{
    $area = get_seating_area($area_code);
    if (!$area) {
        echo '<div class="alert alert-danger">Area not found</div>';
        return;
    }
    
    $blocked_seats = get_blocked_seats($area_code);
    ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><?= $area->area_name ?> <small class="text-muted">(<?= $area->area_code ?>)</small></h5>
                <small class="text-muted"><?= $area->gender ?> • Ages <?= $area->min_age ?>+ • Max <?= $area->max_seats_per_family ?: '∞' ?>/family</small>
            </div>
            <a href="?" class="btn btn-sm btn-secondary">← Back</a>
        </div>
        <div class="card-body">
            <form method="post" id="editForm">
                <input type="hidden" name="action" value="update_area">
                <input type="hidden" name="area_code" value="<?= $area->area_code ?>">
                <input type="hidden" name="blocked_seats" id="blockedSeatsData">
                
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Area Name</label>
                        <input type="text" name="area_name" class="form-control" value="<?= $area->area_name ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Seat Range Start</label>
                        <input type="number" name="seat_start" class="form-control" value="<?= $area->seat_start ?>" placeholder="Optional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Seat Range End</label>
                        <input type="number" name="seat_end" class="form-control" value="<?= $area->seat_end ?>" placeholder="Optional">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-select">
                            <option value="Y" <?= $area->is_active == 'Y' ? 'selected' : '' ?>>Active</option>
                            <option value="N" <?= $area->is_active == 'N' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <hr>
                
                <h6 class="mb-3">Block Seats</h6>
                <div class="row g-2 mb-3">
                    <div class="col-auto">
                        <input type="number" id="seatFrom" class="form-control form-control-sm" placeholder="From" style="width:80px">
                    </div>
                    <div class="col-auto">
                        <input type="number" id="seatTo" class="form-control form-control-sm" placeholder="To" style="width:80px">
                    </div>
                    <div class="col-auto" style="flex:1; min-width:200px">
                        <input type="text" id="seatReason" class="form-control form-control-sm" placeholder="Reason (optional)">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-warning" onclick="addSeats()">+ Block</button>
                    </div>
                </div>
                
                <div id="blockedList">
                    <div class="text-muted small">No blocked seats</div>
                </div>
                
                <hr class="my-4">
                
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="?" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
    
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
                <div class="mb-2 small text-muted">${blocked.length} blocked seat${blocked.length !== 1 ? 's' : ''}</div>
                <div class="d-flex flex-wrap gap-2">
                    ${blocked.map(s => `
                        <span class="badge bg-danger d-flex align-items-center gap-2" style="font-size:0.85rem">
                            Seat ${s.seat_number}${s.reason ? ': ' + s.reason : ''}
                            <button type="button" class="btn-close btn-close-white" style="font-size:0.6rem" 
                                onclick="remove(${s.seat_number})" aria-label="Remove"></button>
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

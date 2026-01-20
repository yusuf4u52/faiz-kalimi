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
        $area_name = $_POST['area_name'] ?? '';
        $seat_start = $_POST['seat_start'] ?? null;
        $seat_end = $_POST['seat_end'] ?? null;
        $is_active = $_POST['is_active'] ?? 'Y';
        $blocked_seats_json = $_POST['blocked_seats'] ?? '[]';
        
        if (empty($seat_start)) $seat_start = null;
        if (empty($seat_end)) $seat_end = null;
        
        // Update area basic info
        $success = update_seating_area($area_code, $area_name, $seat_start, $seat_end, $is_active);
        
        if ($success) {
            // Process blocked seats
            $blocked_seats = json_decode($blocked_seats_json, true);
            $userData = getSessionData(THE_SESSION_ID);
            $blocked_by = $userData->itsid ?? '';
            
            // Get current blocked seats from database
            $current_blocked = get_blocked_seats($area_code);
            $current_blocked_numbers = array_map(function($b) { return $b->seat_number; }, $current_blocked);
            
            // Get new blocked seat numbers from form
            $new_blocked_numbers = array_map(function($b) { return $b['seat_number']; }, $blocked_seats);
            
            // Unblock seats that are no longer in the list
            foreach ($current_blocked_numbers as $seat_num) {
                if (!in_array($seat_num, $new_blocked_numbers)) {
                    unblock_seat($area_code, $seat_num);
                }
            }
            
            // Block new seats or update existing ones
            foreach ($blocked_seats as $seat) {
                block_seat($area_code, $seat['seat_number'], $seat['reason'], $blocked_by);
            }
            
            setSessionData(TRANSIT_DATA, 'Area and blocked seats updated successfully!');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to update area.');
        }
        do_redirect('?edit=' . $area_code);
    }
}

function content_display()
{
    $hijri_year = get_current_hijri_year();
    $url = getAppData('BASE_URI');
    $areas = get_seating_areas();
    
    $edit_area = $_GET['edit'] ?? '';
    
    // If editing a specific area
    if (!empty($edit_area)) {
        show_edit_area_page($edit_area, $url, $hijri_year);
        return;
    }
    
    // Main listing page
    ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-title">Seating Areas Configuration - <?= $hijri_year ?>H</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Area Code</th>
                            <th>Area Name</th>
                            <th>Gender</th>
                            <th>Chairs</th>
                            <th>Min Age</th>
                            <th>Max/Family</th>
                            <th>Seat Range</th>
                            <th>Active</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($areas as $area) { ?>
                        <tr>
                            <td><?= $area->area_code ?></td>
                            <td><?= $area->area_name ?></td>
                            <td><?= $area->gender ?></td>
                            <td><?= $area->chairs_allowed == 'Y' ? 'Yes' : 'No' ?></td>
                            <td><?= $area->min_age ?></td>
                            <td><?= $area->max_seats_per_family ?: 'Unlimited' ?></td>
                            <td>
                                <?php if ($area->seat_start && $area->seat_end) { ?>
                                    <?= $area->seat_start ?> - <?= $area->seat_end ?>
                                <?php } else { ?>
                                    <span class="text-muted">Not set</span>
                                <?php } ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= $area->is_active == 'Y' ? 'success' : 'secondary' ?>">
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
    </div>
    
    <div class="mt-3">
        <a href="<?= $url ?>/seat-management" class="btn btn-secondary">Back to Seat Management</a>
    </div>
    <?php
}

function show_edit_area_page($area_code, $url, $hijri_year)
{
    $area = get_seating_area($area_code);
    if (!$area) {
        echo '<div class="alert alert-danger">Area not found.</div>';
        return;
    }
    
    $blocked_seats = get_blocked_seats($area_code);
    $blocked_seats_json = json_encode($blocked_seats);
    ?>
    <div class="mb-3">
        <a href="?" class="btn btn-secondary">← Back to All Areas</a>
    </div>
    
    <!-- Edit Area Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-title">Edit Seating Area - <?= $area->area_name ?> (<?= $area->area_code ?>)</h4>
        </div>
        <div class="card-body">
            <form method="post" id="areaEditForm">
                <input type="hidden" name="action" value="update_area">
                <input type="hidden" name="area_code" value="<?= $area->area_code ?>">
                <input type="hidden" name="blocked_seats" id="blockedSeatsInput" value="">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Area Code</label>
                        <input type="text" class="form-control" value="<?= $area->area_code ?>" disabled>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Area Name</label>
                        <input type="text" name="area_name" class="form-control" value="<?= $area->area_name ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Gender</label>
                        <input type="text" class="form-control" value="<?= $area->gender ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Chairs Allowed</label>
                        <input type="text" class="form-control" value="<?= $area->chairs_allowed == 'Y' ? 'Yes' : 'No' ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Min Age</label>
                        <input type="text" class="form-control" value="<?= $area->min_age ?>" disabled>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Max Seats/Family</label>
                        <input type="text" class="form-control" value="<?= $area->max_seats_per_family ?: 'Unlimited' ?>" disabled>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Seat Range Start</label>
                        <input type="number" name="seat_start" class="form-control" value="<?= $area->seat_start ?>" placeholder="Leave empty for no range">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Seat Range End</label>
                        <input type="number" name="seat_end" class="form-control" value="<?= $area->seat_end ?>" placeholder="Leave empty for no range">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="is_active" class="form-control">
                            <option value="Y" <?= $area->is_active == 'Y' ? 'selected' : '' ?>>Active</option>
                            <option value="N" <?= $area->is_active == 'N' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <!-- Blocked Seats Section (integrated) -->
                <h5 class="mb-3">Blocked Seats Management</h5>
                <p class="text-muted small">Note: Changes to blocked seats are saved only when you click "Save All Changes" below.</p>
                
                <!-- Block Range -->
                <div class="mb-4">
                    <h6>Block Range of Seats</h6>
                    <div class="row">
                        <div class="col-md-2">
                            <input type="number" id="rangeStart" class="form-control" placeholder="From">
                        </div>
                        <div class="col-md-2">
                            <input type="number" id="rangeEnd" class="form-control" placeholder="To">
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="blockReason" class="form-control" placeholder="Reason (optional)">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-warning" onclick="blockRange()">Block Range</button>
                        </div>
                    </div>
                </div>
                
                <!-- Block Single Seat -->
                <div class="mb-4">
                    <h6>Block Single Seat</h6>
                    <div class="row">
                        <div class="col-md-2">
                            <input type="number" id="singleSeat" class="form-control" placeholder="Seat #">
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="singleReason" class="form-control" placeholder="Reason (optional)">
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-warning" onclick="blockSingle()">Block Seat</button>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <!-- Blocked Seats List -->
                <h6>Currently Blocked Seats (<span id="blockedCount">0</span>)</h6>
                <div id="noBlockedSeats" class="text-muted" style="display: none;">No blocked seats for this area.</div>
                <div class="table-responsive" id="blockedSeatsTable" style="display: none;">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Seat #</th>
                                <th>Reason</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="blockedSeatsBody">
                        </tbody>
                    </table>
                </div>
                
                <hr class="my-4">
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary btn-lg">Save All Changes</button>
                    <a href="?" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // JavaScript for managing blocked seats in UI
    let blockedSeats = <?= $blocked_seats_json ?>;
    
    // Initialize the blocked seats display
    function initBlockedSeats() {
        blockedSeats = blockedSeats.map(seat => ({
            seat_number: parseInt(seat.seat_number),
            reason: seat.reason || ''
        }));
        renderBlockedSeats();
    }
    
    // Render the blocked seats table
    function renderBlockedSeats() {
        const tbody = document.getElementById('blockedSeatsBody');
        const count = document.getElementById('blockedCount');
        const noSeatsMsg = document.getElementById('noBlockedSeats');
        const table = document.getElementById('blockedSeatsTable');
        
        // Sort by seat number
        blockedSeats.sort((a, b) => a.seat_number - b.seat_number);
        
        count.textContent = blockedSeats.length;
        
        if (blockedSeats.length === 0) {
            noSeatsMsg.style.display = 'block';
            table.style.display = 'none';
            tbody.innerHTML = '';
        } else {
            noSeatsMsg.style.display = 'none';
            table.style.display = 'block';
            
            tbody.innerHTML = blockedSeats.map(seat => `
                <tr>
                    <td>${seat.seat_number}</td>
                    <td>${seat.reason || '-'}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-success" onclick="unblockSeat(${seat.seat_number})">
                            Unblock
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Update hidden input
        document.getElementById('blockedSeatsInput').value = JSON.stringify(blockedSeats);
    }
    
    // Block a range of seats
    function blockRange() {
        const start = parseInt(document.getElementById('rangeStart').value);
        const end = parseInt(document.getElementById('rangeEnd').value);
        const reason = document.getElementById('blockReason').value.trim();
        
        if (!start || !end) {
            alert('Please enter both start and end seat numbers.');
            return;
        }
        
        if (start > end) {
            alert('Start seat number must be less than or equal to end seat number.');
            return;
        }
        
        // Add seats to the blocked list
        let count = 0;
        for (let i = start; i <= end; i++) {
            if (!blockedSeats.some(s => s.seat_number === i)) {
                blockedSeats.push({
                    seat_number: i,
                    reason: reason
                });
                count++;
            }
        }
        
        if (count > 0) {
            alert(`Added ${count} seat(s) to blocked list. Click "Save All Changes" to apply.`);
            // Clear inputs
            document.getElementById('rangeStart').value = '';
            document.getElementById('rangeEnd').value = '';
            document.getElementById('blockReason').value = '';
            renderBlockedSeats();
        } else {
            alert('All seats in this range are already blocked.');
        }
    }
    
    // Block a single seat
    function blockSingle() {
        const seatNum = parseInt(document.getElementById('singleSeat').value);
        const reason = document.getElementById('singleReason').value.trim();
        
        if (!seatNum) {
            alert('Please enter a seat number.');
            return;
        }
        
        if (blockedSeats.some(s => s.seat_number === seatNum)) {
            alert('This seat is already blocked.');
            return;
        }
        
        blockedSeats.push({
            seat_number: seatNum,
            reason: reason
        });
        
        alert('Seat added to blocked list. Click "Save All Changes" to apply.');
        // Clear inputs
        document.getElementById('singleSeat').value = '';
        document.getElementById('singleReason').value = '';
        renderBlockedSeats();
    }
    
    // Unblock a seat
    function unblockSeat(seatNumber) {
        blockedSeats = blockedSeats.filter(s => s.seat_number !== seatNumber);
        renderBlockedSeats();
    }
    
    // Form submission handler
    document.getElementById('areaEditForm').addEventListener('submit', function(e) {
        // Update hidden input with latest blocked seats data
        document.getElementById('blockedSeatsInput').value = JSON.stringify(blockedSeats);
    });
    
    // Initialize on page load
    initBlockedSeats();
    </script>
    <?php
}

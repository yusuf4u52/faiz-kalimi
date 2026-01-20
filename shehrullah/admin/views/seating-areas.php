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
        
        if (empty($seat_start)) $seat_start = null;
        if (empty($seat_end)) $seat_end = null;
        
        $success = update_seating_area($area_code, $area_name, $seat_start, $seat_end, $is_active);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Area updated successfully!');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to update area.');
        }
    } else if ($action === 'block_seat') {
        $area_code = $_POST['area_code'] ?? '';
        $seat_number = $_POST['seat_number'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if (empty($area_code) || empty($seat_number)) {
            setSessionData(TRANSIT_DATA, 'Please provide area and seat number.');
            return;
        }
        
        $userData = getSessionData(THE_SESSION_ID);
        $blocked_by = $userData->itsid ?? '';
        
        $success = block_seat($area_code, $seat_number, $reason, $blocked_by);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Seat blocked successfully!');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to block seat.');
        }
    } else if ($action === 'unblock_seat') {
        $area_code = $_POST['area_code'] ?? '';
        $seat_number = $_POST['seat_number'] ?? '';
        
        $success = unblock_seat($area_code, $seat_number);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Seat unblocked successfully!');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to unblock seat.');
        }
    } else if ($action === 'block_range') {
        $area_code = $_POST['area_code'] ?? '';
        $start = intval($_POST['range_start'] ?? 0);
        $end = intval($_POST['range_end'] ?? 0);
        $reason = $_POST['reason'] ?? 'Bulk blocked';
        
        if (empty($area_code) || $start <= 0 || $end <= 0 || $start > $end) {
            setSessionData(TRANSIT_DATA, 'Invalid range provided.');
            return;
        }
        
        $userData = getSessionData(THE_SESSION_ID);
        $blocked_by = $userData->itsid ?? '';
        
        $count = 0;
        for ($i = $start; $i <= $end; $i++) {
            if (block_seat($area_code, $i, $reason, $blocked_by)) {
                $count++;
            }
        }
        
        setSessionData(TRANSIT_DATA, "Blocked $count seats ($start to $end).");
    }
}

function content_display()
{
    $hijri_year = get_current_hijri_year();
    $url = getAppData('BASE_URI');
    $areas = get_seating_areas();
    
    $selected_area = $_GET['area'] ?? ($areas[0]->area_code ?? '');
    $blocked_seats = [];
    if (!empty($selected_area)) {
        $blocked_seats = get_blocked_seats($selected_area);
    }
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
                            <form method="post">
                                <input type="hidden" name="action" value="update_area">
                                <input type="hidden" name="area_code" value="<?= $area->area_code ?>">
                                
                                <td><?= $area->area_code ?></td>
                                <td>
                                    <input type="text" name="area_name" class="form-control form-control-sm" value="<?= $area->area_name ?>" required>
                                </td>
                                <td><?= $area->gender ?></td>
                                <td><?= $area->chairs_allowed == 'Y' ? 'Yes' : 'No' ?></td>
                                <td><?= $area->min_age ?></td>
                                <td><?= $area->max_seats_per_family ?: 'Unlimited' ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <input type="number" name="seat_start" class="form-control form-control-sm" placeholder="Start" style="width: 70px" value="<?= $area->seat_start ?>">
                                        <span>-</span>
                                        <input type="number" name="seat_end" class="form-control form-control-sm" placeholder="End" style="width: 70px" value="<?= $area->seat_end ?>">
                                    </div>
                                </td>
                                <td>
                                    <select name="is_active" class="form-control form-control-sm">
                                        <option value="Y" <?= $area->is_active == 'Y' ? 'selected' : '' ?>>Yes</option>
                                        <option value="N" <?= $area->is_active == 'N' ? 'selected' : '' ?>>No</option>
                                    </select>
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    <a href="?area=<?= $area->area_code ?>" class="btn btn-sm btn-info">Blocked</a>
                                </td>
                            </form>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Blocked Seats Section -->
    <?php if (!empty($selected_area)) { 
        $area_info = get_seating_area($selected_area);
    ?>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Blocked Seats - <?= $area_info->area_name ?? $selected_area ?></h5>
        </div>
        <div class="card-body">
            <!-- Block Single Seat -->
            <form method="post" class="mb-3">
                <input type="hidden" name="action" value="block_seat">
                <input type="hidden" name="area_code" value="<?= $selected_area ?>">
                <div class="row">
                    <div class="col-md-2">
                        <input type="number" name="seat_number" class="form-control" placeholder="Seat #" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="reason" class="form-control" placeholder="Reason (optional)">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-danger">Block Seat</button>
                    </div>
                </div>
            </form>
            
            <!-- Block Range -->
            <form method="post" class="mb-3">
                <input type="hidden" name="action" value="block_range">
                <input type="hidden" name="area_code" value="<?= $selected_area ?>">
                <div class="row">
                    <div class="col-md-2">
                        <input type="number" name="range_start" class="form-control" placeholder="From" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="range_end" class="form-control" placeholder="To" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="reason" class="form-control" placeholder="Reason" value="Bulk blocked">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning">Block Range</button>
                    </div>
                </div>
            </form>
            
            <hr>
            
            <!-- Blocked Seats List -->
            <?php if (empty($blocked_seats)) { ?>
                <p class="text-muted">No blocked seats for this area.</p>
            <?php } else { ?>
            <h6>Currently Blocked (<?= count($blocked_seats) ?>)</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Seat #</th>
                            <th>Reason</th>
                            <th>Blocked By</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_seats as $bs) { ?>
                        <tr>
                            <td><?= $bs->seat_number ?></td>
                            <td><?= $bs->reason ?: '-' ?></td>
                            <td><?= $bs->blocked_by ?></td>
                            <td><?= date('d/m/Y', strtotime($bs->blocked_at)) ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="unblock_seat">
                                    <input type="hidden" name="area_code" value="<?= $selected_area ?>">
                                    <input type="hidden" name="seat_number" value="<?= $bs->seat_number ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Unblock</button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    
    <div class="mt-3">
        <a href="<?= $url ?>/seat-management" class="btn btn-secondary">Back to Seat Management</a>
    </div>
    <?php
}

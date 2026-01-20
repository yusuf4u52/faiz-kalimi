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
        do_redirect('?edit=' . $area_code);
    } else if ($action === 'block_seat') {
        $area_code = $_POST['area_code'] ?? '';
        $seat_number = $_POST['seat_number'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if (empty($area_code) || empty($seat_number)) {
            setSessionData(TRANSIT_DATA, 'Please provide area and seat number.');
            do_redirect('?edit=' . $area_code);
        }
        
        $userData = getSessionData(THE_SESSION_ID);
        $blocked_by = $userData->itsid ?? '';
        
        $success = block_seat($area_code, $seat_number, $reason, $blocked_by);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Seat blocked successfully!');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to block seat.');
        }
        do_redirect('?edit=' . $area_code);
    } else if ($action === 'unblock_seat') {
        $area_code = $_POST['area_code'] ?? '';
        $seat_number = $_POST['seat_number'] ?? '';
        
        $success = unblock_seat($area_code, $seat_number);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Seat unblocked successfully!');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to unblock seat.');
        }
        do_redirect('?edit=' . $area_code);
    } else if ($action === 'block_range') {
        $area_code = $_POST['area_code'] ?? '';
        $start = intval($_POST['range_start'] ?? 0);
        $end = intval($_POST['range_end'] ?? 0);
        $reason = $_POST['reason'] ?? 'Bulk blocked';
        
        if (empty($area_code) || $start <= 0 || $end <= 0 || $start > $end) {
            setSessionData(TRANSIT_DATA, 'Invalid range provided.');
            do_redirect('?edit=' . $area_code);
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
            <form method="post">
                <input type="hidden" name="action" value="update_area">
                <input type="hidden" name="area_code" value="<?= $area->area_code ?>">
                
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
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="?" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Blocked Seats Section -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Blocked Seats Management</h5>
        </div>
        <div class="card-body">
            <!-- Block Single Seat -->
            <div class="mb-4">
                <h6>Block Single Seat</h6>
                <form method="post">
                    <input type="hidden" name="action" value="block_seat">
                    <input type="hidden" name="area_code" value="<?= $area_code ?>">
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
            </div>
            
            <!-- Block Range -->
            <div class="mb-4">
                <h6>Block Range of Seats</h6>
                <form method="post">
                    <input type="hidden" name="action" value="block_range">
                    <input type="hidden" name="area_code" value="<?= $area_code ?>">
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
            </div>
            
            <hr>
            
            <!-- Blocked Seats List -->
            <?php if (empty($blocked_seats)) { ?>
                <p class="text-muted">No blocked seats for this area.</p>
            <?php } else { ?>
            <h6>Currently Blocked Seats (<?= count($blocked_seats) ?>)</h6>
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
                                    <input type="hidden" name="area_code" value="<?= $area_code ?>">
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
    <?php
}

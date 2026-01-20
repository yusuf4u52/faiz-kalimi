<?php

if (!is_user_a(SUPER_ADMIN)) {
    do_redirect_with_message('/home', 'Access denied. SUPER_ADMIN role required.');
}

do_for_post('_handle_post');

function _handle_post()
{
    $action = $_POST['action'] ?? '';
    
    if ($action === 'search') {
        $search = $_POST['search'] ?? '';
        if (!empty($search)) {
            setAppData('search_term', $search);
        }
    } else if ($action === 'pre_allocate') {
        $its_id = $_POST['its_id'] ?? '';
        $hof_id = $_POST['hof_id'] ?? '';
        $area_code = $_POST['area_code'] ?? '';
        $seat_number = $_POST['seat_number'] ?? null;
        
        if (empty($its_id) || empty($area_code)) {
            setSessionData(TRANSIT_DATA, 'Invalid data provided.');
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
    } else if ($action === 'assign_seats') {
        $area_code = $_POST['area_code'] ?? '';
        if (!empty($area_code)) {
            $assigned = assign_sequential_seats($area_code);
            setSessionData(TRANSIT_DATA, "Assigned $assigned seat numbers for area.");
        }
    } else if ($action === 'toggle_selection') {
        $open = $_POST['open'] ?? 'N';
        $success = toggle_seat_selection($open === 'Y');
        if ($success) {
            $status = $open === 'Y' ? 'OPENED' : 'CLOSED';
            setSessionData(TRANSIT_DATA, "Seat selection is now $status.");
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to toggle seat selection status.');
        }
    }
}

function content_display()
{
    $hijri_year = get_current_hijri_year();
    $url = getAppData('BASE_URI');
    $areas = get_seating_areas();
    $search_term = getAppData('search_term') ?? '';
    $is_selection_open = is_seat_selection_open();
    
    // Get all allocations
    $allocations = get_all_seat_allocations();
    
    // Filter by search if provided
    if (!empty($search_term)) {
        $allocations = array_filter($allocations, function($a) use ($search_term) {
            return stripos($a->hof_id, $search_term) !== false || 
                   stripos($a->full_name, $search_term) !== false ||
                   stripos($a->its_id, $search_term) !== false;
        });
    }
    ?>
    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h4 class="card-title mb-0">Seat Management - Shehrullah <?= $hijri_year ?>H</h4>

            <!-- Seat Selection Open/Close -->
            <form method="post" class="d-flex align-items-center gap-2 mb-0">
                <input type="hidden" name="action" value="toggle_selection">

                <span class="text-muted small mb-0">
                    Selection:
                    <?php if ($is_selection_open) { ?>
                        <span class="badge bg-success">OPEN</span>
                    <?php } else { ?>
                        <span class="badge bg-secondary">CLOSED</span>
                    <?php } ?>
                </span>

                <div class="btn-group btn-group-sm" role="group" aria-label="Seat selection status">
                    <button
                        type="submit"
                        name="open"
                        value="Y"
                        class="btn <?= $is_selection_open ? 'btn-success' : 'btn-outline-success' ?>"
                        onclick="return confirm('Open seat selection? Users will be able to select seats.');"
                    >
                        Open
                    </button>
                    <button
                        type="submit"
                        name="open"
                        value="N"
                        class="btn <?= $is_selection_open ? 'btn-outline-danger' : 'btn-danger' ?>"
                        onclick="return confirm('Close seat selection? Users will not be able to select seats.');"
                    >
                        Close
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <!-- Search and Pre-allocate Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <form method="post" class="mb-3">
                        <input type="hidden" name="action" value="search">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search by HOF ID, ITS or Name" value="<?= htmlspecialchars($search_term) ?>">
                            <button class="btn btn-primary" type="submit">Search</button>
                            <a href="<?= $url ?>/seat-management" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <a href="<?= $url ?>/seat-pre-allocate" class="btn btn-success">Pre-Allocate Seat</a>
                    <a href="<?= $url ?>/seating-areas" class="btn btn-info">Manage Areas</a>
                    <a href="<?= $url ?>/seat-exceptions" class="btn btn-warning">Exceptions</a>
                </div>
            </div>
            
            <!-- Quick Assign Seats by Area -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5>Assign Sequential Seat Numbers</h5>
                    <form method="post" class="form-inline">
                        <input type="hidden" name="action" value="assign_seats">
                        <div class="input-group" style="max-width: 400px;">
                            <select name="area_code" class="form-control" required>
                                <option value="">-- Select Area --</option>
                                <?php foreach ($areas as $area) { ?>
                                    <option value="<?= $area->area_code ?>"><?= $area->area_name ?></option>
                                <?php } ?>
                            </select>
                            <button class="btn btn-warning" type="submit" onclick="return confirm('This will assign seat numbers to all unassigned allocations in this area. Continue?')">Assign Seats</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Allocations Table -->
            <h5>All Seat Allocations (<?= count($allocations) ?>)</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>HOF ID</th>
                            <th>HOF Name</th>
                            <th>Member</th>
                            <th>G/Age</th>
                            <th>Area</th>
                            <th>Seat #</th>
                            <th>Allocated By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (empty($allocations)) {
                            echo "<tr><td colspan='8' class='text-center'>No allocations found</td></tr>";
                        }
                        foreach ($allocations as $alloc) { 
                            $gender = substr($alloc->gender, 0, 1);
                            $allocated_by = $alloc->allocated_by ? 'Admin' : 'Self';
                            $date = date('d/m/Y H:i', strtotime($alloc->allocated_at));
                        ?>
                            <tr>
                                <td><?= $alloc->hof_id ?></td>
                                <td><?= $alloc->hof_name ?></td>
                                <td><?= $alloc->full_name ?></td>
                                <td><?= $gender ?>/<?= $alloc->age ?></td>
                                <td><?= $alloc->area_name ?></td>
                                <td>
                                    <?php if ($alloc->seat_number) { ?>
                                        <span class="badge bg-success"><?= $alloc->seat_number ?></span>
                                    <?php } else { ?>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    <?php } ?>
                                </td>
                                <td><?= $allocated_by ?></td>
                                <td><?= $date ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

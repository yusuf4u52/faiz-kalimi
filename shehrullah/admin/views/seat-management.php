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
        if (!$success) {
            setSessionData(TRANSIT_DATA, 'Failed to update seat selection status.');
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
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Seat Management - <?= $hijri_year ?>H</h5>
                <form method="post" class="mb-0">
                    <input type="hidden" name="action" value="toggle_selection">
                    <input type="hidden" name="open" value="<?= $is_selection_open ? 'N' : 'Y' ?>">
                    <button type="submit" class="btn btn-sm <?= $is_selection_open ? 'btn-success' : 'btn-outline-secondary' ?>">
                        <?= $is_selection_open ? '● Open' : '○ Closed' ?>
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <!-- Toolbar -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <form method="post" class="flex-fill" style="max-width: 400px;">
                    <input type="hidden" name="action" value="search">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Search HOF, ITS, or Name..." value="<?= htmlspecialchars($search_term) ?>">
                        <button class="btn btn-outline-secondary" type="submit">Search</button>
                        <?php if ($search_term) { ?>
                        <a href="<?= $url ?>/seat-management" class="btn btn-outline-secondary">×</a>
                        <?php } ?>
                    </div>
                </form>
                <div class="btn-group btn-group-sm">
                    <a href="<?= $url ?>/seat-pre-allocate" class="btn btn-outline-primary">Allocate</a>
                    <a href="<?= $url ?>/seating-areas" class="btn btn-outline-primary">Areas</a>
                    <a href="<?= $url ?>/seat-exceptions" class="btn btn-outline-primary">Exceptions</a>
                </div>
            </div>
            
            <!-- Auto-assign seats -->
            <form method="post" class="mb-3">
                <input type="hidden" name="action" value="assign_seats">
                <div class="input-group input-group-sm" style="max-width: 450px;">
                    <span class="input-group-text">Auto-assign seats for</span>
                    <select name="area_code" class="form-select" required>
                        <option value="">Select area...</option>
                        <?php foreach ($areas as $area) { ?>
                            <option value="<?= $area->area_code ?>"><?= $area->area_name ?></option>
                        <?php } ?>
                    </select>
                    <button class="btn btn-outline-warning" type="submit">Assign</button>
                </div>
            </form>
            
            <!-- Allocations Table -->
            <div class="small text-muted mb-2"><?= count($allocations) ?> allocation<?= count($allocations) != 1 ? 's' : '' ?></div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>HOF</th>
                            <th>Family</th>
                            <th>Member</th>
                            <th>G/Age</th>
                            <th>Area</th>
                            <th>Seat</th>
                            <th>By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (empty($allocations)) {
                            echo "<tr><td colspan='8' class='text-center text-muted'>No allocations found</td></tr>";
                        }
                        foreach ($allocations as $alloc) { 
                            $gender = substr($alloc->gender, 0, 1);
                            $allocated_by = $alloc->allocated_by ? 'Admin' : 'Self';
                            $date = date('d/m H:i', strtotime($alloc->allocated_at));
                        ?>
                            <tr>
                                <td><code class="small"><?= $alloc->hof_id ?></code></td>
                                <td><?= $alloc->hof_name ?></td>
                                <td><?= $alloc->full_name ?></td>
                                <td><small><?= $gender ?>/<?= $alloc->age ?></small></td>
                                <td><small><?= $alloc->area_name ?></small></td>
                                <td>
                                    <?php if ($alloc->seat_number) { ?>
                                        <strong><?= $alloc->seat_number ?></strong>
                                    <?php } else { ?>
                                        <span class="text-muted">—</span>
                                    <?php } ?>
                                </td>
                                <td><small><?= $allocated_by ?></small></td>
                                <td><small class="text-muted"><?= $date ?></small></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
}

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
    $hijri_year = get_current_hijri_year();
    $url = getAppData('BASE_URI');
    $areas = get_seating_areas();
    
    $hof_id = getAppData('hof_id');
    $thaali_data = getAppData('thaali_data');
    $attendees = getAppData('attendees') ?? [];
    ?>
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Pre-Allocate Seats (SUPER_ADMIN)</h4>
            <p class="text-muted">Assign seats to any member, bypassing all rules</p>
        </div>
        <div class="card-body">
            <!-- Search Section -->
            <form method="post" class="mb-4">
                <input type="hidden" name="action" value="search">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" name="sabeel" placeholder="Enter Sabeel or HOF ID" pattern="^[0-9]{1,8}$" required>
                            <button class="btn btn-primary" type="submit">Search</button>
                        </div>
                    </div>
                </div>
            </form>
            
            <?php if ($thaali_data) { ?>
            <hr>
            <h5>Family: [<?= $hof_id ?>] <?= $thaali_data->NAME ?></h5>
            <p>Sabeel: <?= $thaali_data->Thali ?></p>
            
            <?php if (empty($attendees)) { ?>
                <div class="alert alert-warning">No eligible family members found (Misaq Done, Attending).</div>
            <?php } else { ?>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>G/Age</th>
                            <th>Chair</th>
                            <th>Current Allocation</th>
                            <th>Assign Area</th>
                            <th>Seat #</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendees as $attendee) { 
                            $gender = substr($attendee->gender, 0, 1);
                            $chair = $attendee->chair_preference == 'Y' ? 'Yes' : 'No';
                            $current_area = $attendee->allocated_area_name ?? 'Not assigned';
                            $current_seat = $attendee->seat_number ?? '-';
                        ?>
                        <tr>
                            <form method="post">
                                <input type="hidden" name="action" value="pre_allocate">
                                <input type="hidden" name="its_id" value="<?= $attendee->its_id ?>">
                                <input type="hidden" name="hof_id" value="<?= $hof_id ?>">
                                
                                <td><?= $attendee->full_name ?><br><small class="text-muted"><?= $attendee->its_id ?></small></td>
                                <td><?= $gender ?>/<?= $attendee->age ?></td>
                                <td><?= $chair ?></td>
                                <td>
                                    <?= $current_area ?>
                                    <?php if ($current_seat != '-') { ?>
                                        <br><span class="badge bg-success">Seat #<?= $current_seat ?></span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <select name="area_code" class="form-control form-control-sm" required>
                                        <option value="">-- Select --</option>
                                        <?php foreach ($areas as $area) { 
                                            $selected = ($area->area_code == $attendee->allocated_area) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $area->area_code ?>" <?= $selected ?>><?= $area->area_name ?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="seat_number" class="form-control form-control-sm" placeholder="Auto" style="width: 80px" value="<?= $current_seat != '-' ? $current_seat : '' ?>">
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-sm btn-success">Assign</button>
                                </td>
                            </form>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>
            <?php } ?>
            
            <div class="mt-4">
                <a href="<?= $url ?>/seat-management" class="btn btn-secondary">Back to Seat Management</a>
            </div>
        </div>
    </div>
    <?php
}

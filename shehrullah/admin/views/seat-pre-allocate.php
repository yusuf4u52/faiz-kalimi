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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Pre-Allocate Seats</h5>
                    <small class="text-muted">Bypass rules and assign seats directly</small>
                </div>
                <a href="<?= $url ?>/seat-management" class="btn btn-sm btn-outline-secondary">Back</a>
            </div>
        </div>
        <div class="card-body">
            <!-- Search Section -->
            <form method="post" class="mb-3">
                <input type="hidden" name="action" value="search">
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" name="sabeel" class="form-control" placeholder="Sabeel or HOF ID" pattern="^[0-9]{1,8}$" required>
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>
            
            <?php if ($thaali_data) { ?>
            <hr>
            <div class="mb-3">
                <strong><?= $thaali_data->NAME ?></strong> 
                <code class="small"><?= $hof_id ?></code> 
                <small class="text-muted">• Sabeel <?= $thaali_data->Thali ?></small>
            </div>
            
            <?php if (empty($attendees)) { ?>
                <div class="alert alert-warning small">No eligible family members found (must have Misaq and be attending).</div>
            <?php } else { ?>
            
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>G/Age</th>
                            <th>Chair</th>
                            <th>Current</th>
                            <th>Area</th>
                            <th>Seat #</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendees as $attendee) { 
                            $gender = substr($attendee->gender, 0, 1);
                            $chair = $attendee->chair_preference == 'Y' ? '✓' : '';
                            $current_area = $attendee->allocated_area_name ?? '—';
                            $current_seat = $attendee->seat_number ?? '';
                        ?>
                        <tr>
                            <form method="post" class="contents">
                                <input type="hidden" name="action" value="pre_allocate">
                                <input type="hidden" name="its_id" value="<?= $attendee->its_id ?>">
                                <input type="hidden" name="hof_id" value="<?= $hof_id ?>">
                                
                                <td>
                                    <?= $attendee->full_name ?>
                                    <br><code class="small text-muted"><?= $attendee->its_id ?></code>
                                </td>
                                <td><small><?= $gender ?>/<?= $attendee->age ?></small></td>
                                <td><small><?= $chair ?></small></td>
                                <td>
                                    <small><?= $current_area ?></small>
                                    <?php if ($current_seat) { ?>
                                        <br><strong class="text-success">#<?= $current_seat ?></strong>
                                    <?php } ?>
                                </td>
                                <td>
                                    <select name="area_code" class="form-select form-select-sm" required>
                                        <option value="">Select...</option>
                                        <?php foreach ($areas as $area) { 
                                            $selected = ($area->area_code == $attendee->allocated_area) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $area->area_code ?>" <?= $selected ?>><?= $area->area_name ?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="seat_number" class="form-control form-control-sm" placeholder="Auto" style="width:70px" value="<?= $current_seat ?>">
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-sm btn-primary">Assign</button>
                                </td>
                            </form>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>
            <?php } ?>
        </div>
    </div>
    <?php
}

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
            setAppData('search_hof_id', $hof_id);
            setAppData('search_thaali_data', $thaali_data);
            
            // Get takhmeen data
            $hijri_year = get_current_hijri_year();
            $takhmeen = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
            setAppData('search_takhmeen', $takhmeen);
            
            // Check if already has exception
            $has_exception = has_seat_exception($hof_id, $hijri_year);
            setAppData('has_exception', $has_exception);
            
            // Check if family has allocated seats
            $allocations = get_seat_allocations_for_family($hof_id);
            setAppData('search_allocations', $allocations);
        }
    } else if ($action === 'grant') {
        $hof_id = $_POST['hof_id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $hoob_clearance_date = $_POST['hoob_clearance_date'] ?? null;
        
        if (empty($hof_id)) {
            setSessionData(TRANSIT_DATA, 'Invalid HOF ID.');
            return;
        }
        
        if (empty($hoob_clearance_date)) {
            setSessionData(TRANSIT_DATA, 'Hoob clearance date is required.');
            return;
        }
        
        // Check if takhmeen is done before granting exception
        $hijri_year = get_current_hijri_year();
        $takhmeen = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
        
        if (!$takhmeen || $takhmeen->takhmeen <= 0) {
            setSessionData(TRANSIT_DATA, 'Cannot grant exception: Takhmeen not done for this family.');
            return;
        }
        
        $userData = getSessionData(THE_SESSION_ID);
        $granted_by = $userData->itsid ?? '';
        
        $success = grant_seat_exception($hof_id, $reason, $granted_by, $hoob_clearance_date);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Exception granted successfully! Family can now select seats.');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to grant exception.');
        }
    } else if ($action === 'revoke') {
        $hof_id = $_POST['hof_id'] ?? '';
        
        // Check if family has allocated seats
        $allocations = get_seat_allocations_for_family($hof_id);
        if (!empty($allocations)) {
            setSessionData(TRANSIT_DATA, 'Cannot revoke exception: Family has already allocated seats. Please deallocate seats first.');
            return;
        }
        
        $success = revoke_seat_exception($hof_id);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Exception revoked successfully.');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to revoke exception.');
        }
    }
}

function content_display()
{
    $hijri_year = get_current_hijri_year();
    $url = getAppData('BASE_URI');
    
    // Get all active exceptions
    $exceptions = get_all_seat_exceptions();
    
    // Search results
    $search_hof_id = getAppData('search_hof_id');
    $search_thaali_data = getAppData('search_thaali_data');
    $search_takhmeen = getAppData('search_takhmeen');
    $has_exception = getAppData('has_exception');
    $search_allocations = getAppData('search_allocations') ?: [];
    
    // Main search card
    ui_card("Seat Exceptions - {$hijri_year}H", 'Allow seat selection without full payment', "$url/seat-management");
    ?>
    <form method="post" class="mb-3">
        <input type="hidden" name="action" value="search">
        <div class="input-group input-group-sm" style="max-width: 400px;">
            <?= ui_input('sabeel', '', 'Sabeel or HOF ID') ?>
            <?= ui_btn('Search', 'primary') ?>
        </div>
    </form>
    
    <?php if ($search_thaali_data) { 
        ui_hr();
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <strong><?= h($search_thaali_data->NAME) ?></strong> <?= ui_code($search_hof_id) ?>
                <br><?= ui_muted("Sabeel {$search_thaali_data->Thali}") ?>
            </div>
            
            <table class="table table-sm table-bordered">
                <?php if ($search_takhmeen) { 
                    $pending = $search_takhmeen->takhmeen - $search_takhmeen->paid_amount;
                ?>
                <tr><th width="100">Takhmeen</th><td><?= ui_money($search_takhmeen->takhmeen) ?></td></tr>
                <tr><th>Paid</th><td><?= ui_money($search_takhmeen->paid_amount) ?></td></tr>
                <tr><th>Balance</th><td><?= $search_takhmeen->takhmeen > 0 && $pending <= 0 ? "<span class=\"text-success\">Fully Paid</span>" : "<span class=\"text-danger\">" . ui_money($pending) . "</span>" ?></td></tr>
                <?php } else { ?>
                <tr><td colspan="2" class="text-warning small">Takhmeen not done</td></tr>
                <?php } ?>
                <tr><th>Exception</th><td><?= ui_status($has_exception, ['Granted', 'Not granted']) ?></td></tr>
                <tr><th>Seats Allocated</th><td><?= count($search_allocations) > 0 ? "<span class=\"text-success\">" . count($search_allocations) . " seat(s)</span>" : ui_muted("None") ?></td></tr>
            </table>
            
            <?php if (!$has_exception) { ?>
            <?php if ($search_takhmeen) { ?>
            <form method="post">
                <input type="hidden" name="action" value="grant">
                <input type="hidden" name="hof_id" value="<?= h($search_hof_id) ?>">
                <div class="mb-2"><?= ui_input('reason', '', 'Reason for exception') ?></div>
                <div class="mb-2">
                    <label class="form-label small">Expected Hoob Clearance Date <span class="text-danger">*</span></label>
                    <input type="date" name="hoob_clearance_date" class="form-control form-control-sm" required>
                </div>
                <?= ui_btn('Grant Exception', 'success') ?>
            </form>
            <?php } else { ?>
            <div class="alert alert-warning small">
                Cannot grant exception: Takhmeen must be done first.
            </div>
            <?php } ?>
            <?php } else { ?>
            <?php if (count($search_allocations) > 0) { ?>
            <div class="alert alert-warning small">
                Cannot revoke exception: Family has <?= count($search_allocations) ?> allocated seat(s). Please deallocate seats first.
            </div>
            <?php } else { ?>
            <form method="post">
                <input type="hidden" name="action" value="revoke">
                <input type="hidden" name="hof_id" value="<?= h($search_hof_id) ?>">
                <?= ui_btn('Revoke Exception', 'danger') ?>
            </form>
            <?php } ?>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    <?php ui_card_end(); ?>
    
    <!-- Active Exceptions List -->
    <div class="card">
        <div class="card-header"><h6 class="mb-0">Active Exceptions <?= ui_muted("(" . count($exceptions) . ")") ?></h6></div>
        <div class="card-body">
    <?php
    if (empty($exceptions)) {
        echo ui_muted('No active exceptions.');
    } else {
        ui_table(['HOF', 'Name', 'Takhmeen', 'Paid', 'Balance', 'Seats', 'Reason', 'Hoob Clearance', 'Granted By', '']);
        foreach ($exceptions as $exc) {
            $pending = ($exc->takhmeen ?? 0) - ($exc->paid_amount ?? 0);
            $balance = ($exc->takhmeen ?? 0) > 0 && $pending <= 0 
                ? "<small class=\"text-success\">Paid</small>" 
                : "<small class=\"text-danger\">" . ui_money($pending) . "</small>";
            
            // Check if family has allocated seats
            $allocations = get_seat_allocations_for_family($exc->hof_id);
            $seats_count = count($allocations);
            $seats_display = $seats_count > 0 
                ? '<small class="text-success">' . $seats_count . '</small>' 
                : ui_muted('0');
            
            // Format hoob clearance date with color coding
            $hoob_date_display = '—';
            if (!empty($exc->hoob_clearance_date)) {
                $clearance_date = strtotime($exc->hoob_clearance_date);
                $today = strtotime(date('Y-m-d'));
                $days_diff = floor(($clearance_date - $today) / (60 * 60 * 24));
                
                if ($days_diff < 0) {
                    // Overdue
                    $hoob_date_display = '<small class="text-danger">' . ui_date($exc->hoob_clearance_date, 'd/m/y') . ' <span class="badge bg-danger">Overdue</span></small>';
                } elseif ($days_diff <= 7) {
                    // Due soon (within 7 days)
                    $hoob_date_display = '<small class="text-warning">' . ui_date($exc->hoob_clearance_date, 'd/m/y') . '</small>';
                } else {
                    // Future date
                    $hoob_date_display = '<small>' . ui_date($exc->hoob_clearance_date, 'd/m/y') . '</small>';
                }
            }
            
            // Only allow revoke if no seats are allocated
            if ($seats_count > 0) {
                $revoke = '<small class="text-muted" title="Cannot revoke: seats allocated">—</small>';
            } else {
                $revoke = '<form method="post" style="display:inline"><input type="hidden" name="action" value="revoke"><input type="hidden" name="hof_id" value="' . h($exc->hof_id) . '"><button type="submit" class="btn btn-sm btn-link text-danger p-0">Revoke</button></form>';
            }
            
            ui_tr([
                ui_code($exc->hof_id),
                h($exc->full_name),
                ui_muted(ui_money($exc->takhmeen ?? 0)),
                ui_muted(ui_money($exc->paid_amount ?? 0)),
                $balance,
                $seats_display,
                ui_muted($exc->reason ?: '—'),
                $hoob_date_display,
                ui_muted($exc->granted_by_name ?: '—'),
                $revoke
            ]);
        }
        ui_table_end();
    }
    ?>
        </div>
    </div>
    <?php
}

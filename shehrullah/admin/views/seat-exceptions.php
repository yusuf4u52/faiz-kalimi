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
    } else if ($action === 'send_message') {
        $hof_ids = isset($_POST['hof_ids']) && is_array($_POST['hof_ids']) ? array_map('trim', $_POST['hof_ids']) : [];
        $message_template = trim($_POST['message'] ?? '');
        $image_path = null;
        if (!empty($_FILES['image']['tmp_name']) && is_uploaded_file($_FILES['image']['tmp_name'])) {
            $image_path = $_FILES['image']['tmp_name'];
        }
        $api_url = getenv('MESSAGE_API_URL');
        $api_key = getenv('MESSAGE_API_KEY');
        $api_account_id = getenv('MESSAGE_API_ACCOUNT_ID');
        if (empty($api_url) || empty($api_key) || empty($api_account_id)) {
            setSessionData(TRANSIT_DATA, 'Message API not configured. Set MESSAGE_API_URL, MESSAGE_API_KEY, MESSAGE_API_ACCOUNT_ID in .env');
            return;
        }
        if (empty($hof_ids)) {
            setSessionData(TRANSIT_DATA, 'Select at least one recipient.');
            return;
        }
        if ($message_template === '') {
            setSessionData(TRANSIT_DATA, 'Message is required.');
            return;
        }
        $exceptions = get_all_seat_exceptions();
        $by_hof = [];
        foreach ($exceptions as $e) {
            $by_hof[$e->hof_id] = $e;
        }
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $config = ['url' => $api_url, 'api_key' => $api_key, 'account_id' => $api_account_id];
        foreach ($hof_ids as $hof_id) {
            $e = $by_hof[$hof_id] ?? null;
            if (!$e) continue;
            $whatsapp = trim($e->whatsapp ?? '');
            if ($whatsapp === '') {
                $skipped++;
                continue;
            }
            $pending = ($e->takhmeen ?? 0) - ($e->paid_amount ?? 0);
            $message = _seat_exception_replace_message_vars($message_template, $e, $pending);
            $to_number = $whatsapp;
            if (preg_match('/^[0-9]{10}$/', $to_number)) {
                $to_number = '+91' . $to_number;
            } elseif (strpos($to_number, '+') !== 0) {
                $to_number = '+' . $to_number;
            }
            $result = send_message_via_api($to_number, $message, $image_path, $config);
            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }
        }
        $parts = [];
        if ($sent > 0) $parts[] = "Sent: $sent";
        if ($failed > 0) $parts[] = "Failed: $failed";
        if ($skipped > 0) $parts[] = "Skipped (no number): $skipped";
        setSessionData(TRANSIT_DATA, implode('. ', $parts) ?: 'No messages sent.');
        return;
    }
}

function _seat_exception_replace_message_vars($template, $e, $pending) {
    $takhmeen = $e->takhmeen ?? 0;
    $paid = $e->paid_amount ?? 0;
    $hoob_date = !empty($e->hoob_clearance_date) ? date('d/m/Y', strtotime($e->hoob_clearance_date)) : '';
    $replace = [
        '@full_name' => $e->full_name ?? '',
        '@hof_id' => $e->hof_id ?? '',
        '@takhmeen' => number_format($takhmeen),
        '@paid_amount' => number_format($paid),
        '@pending' => number_format($pending),
        '@hoob_clearance_date' => $hoob_date,
        '@reason' => $e->reason ?? '',
        '@whatsapp' => $e->whatsapp ?? '',
        '@sabeel' => $e->sabeel ?? '',
        '@granted_by_name' => $e->granted_by_name ?? '',
    ];
    return str_replace(array_keys($replace), array_values($replace), $template);
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
        <form method="post" class="my-3">
            <input type="hidden" name="action" value="search">
            <div class="input-group input-group-sm" style="max-width: 400px;">
                <?= ui_input('sabeel', '', 'Sabeel or HOF ID') ?>
                <?= ui_btn('Search', 'light') ?>
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
                        <div class="mb-3"><?= ui_input('reason', '', 'Reason for exception') ?></div>
                        <div class="mb-3">
                            <label class="form-label small">Expected Hoob Clearance Date <span class="text-danger">*</span></label>
                            <input type="date" name="hoob_clearance_date" class="form-control form-control-sm" required>
                        </div>
                        <?= ui_btn('Grant Exception', 'light') ?>
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
                        <?= ui_btn('Revoke Exception', 'light') ?>
                    </form>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
    <?php } ?>
    <?php ui_card_end(); ?>
    
    <!-- Active Exceptions List + Send message -->
    <?php
    $has_message_api = getenv('MESSAGE_API_URL') && getenv('MESSAGE_API_KEY') && getenv('MESSAGE_API_ACCOUNT_ID');
    ?>
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Active Exceptions <?= ui_muted("(" . count($exceptions) . ")") ?></h4>
            <?php
            if (empty($exceptions)) {
                echo ui_muted('No active exceptions.');
            } else {
            ?>
            <form id="send-message-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="send_message">
                <div class="mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllExceptions(true)">Select all</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllExceptions(false)">Select none</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectOverdueWithBalance()">Select overdue with balance</button>
                </div>
                <?php
                ui_table(['Send', 'HOF', 'Name', 'Takhmeen', 'Paid', 'Balance', 'Seats', 'Reason', 'Hoob Clearance', 'Granted By', '']);
                foreach ($exceptions as $exc) {
                    $pending = ($exc->takhmeen ?? 0) - ($exc->paid_amount ?? 0);
                    $balance = ($exc->takhmeen ?? 0) > 0 && $pending <= 0 
                        ? "<small class=\"text-success\">Paid</small>" 
                        : "<small class=\"text-danger\">" . ui_money($pending) . "</small>";
                    $has_whatsapp = !empty(trim($exc->whatsapp ?? ''));
                    $clearance_date = !empty($exc->hoob_clearance_date) ? strtotime($exc->hoob_clearance_date) : null;
                    $today = strtotime(date('Y-m-d'));
                    $is_overdue = $clearance_date !== null && $clearance_date < $today;
                    $has_balance = $pending > 0;
                    $data_overdue = $is_overdue ? '1' : '0';
                    $data_balance = $has_balance ? '1' : '0';
                    $data_whatsapp = $has_whatsapp ? '1' : '0';
                    
                    // Check if family has allocated seats
                    $allocations = get_seat_allocations_for_family($exc->hof_id);
                    $seats_count = count($allocations);
                    $seats_display = $seats_count > 0 
                        ? '<small class="text-success">' . $seats_count . '</small>' 
                        : ui_muted('0');
                    
                    // Format hoob clearance date with color coding
                    $hoob_date_display = '—';
                    if (!empty($exc->hoob_clearance_date)) {
                        $days_diff = $clearance_date ? floor(($clearance_date - $today) / (60 * 60 * 24)) : 0;
                        if ($days_diff < 0) {
                            $hoob_date_display = '<small class="text-danger">' . ui_date($exc->hoob_clearance_date, 'd/m/y') . ' <span class="badge bg-danger">Overdue</span></small>';
                        } elseif ($days_diff <= 7) {
                            $hoob_date_display = '<small class="text-warning">' . ui_date($exc->hoob_clearance_date, 'd/m/y') . '</small>';
                        } else {
                            $hoob_date_display = '<small>' . ui_date($exc->hoob_clearance_date, 'd/m/y') . '</small>';
                        }
                    }
                    
                    if ($seats_count > 0) {
                        $revoke = '<small class="text-muted" title="Cannot revoke: seats allocated">—</small>';
                    } else {
                        $revoke = '<button type="button" class="btn btn-light btn-sm" onclick="revokeException(\'' . h($exc->hof_id) . '\')" title="Revoke"><i class="bi bi-x"></i></button>';
                    }
                    
                    $cb = $has_whatsapp
                        ? '<input type="checkbox" name="hof_ids[]" value="' . h($exc->hof_id) . '" class="send-checkbox" data-overdue="' . $data_overdue . '" data-balance="' . $data_balance . '" data-whatsapp="' . $data_whatsapp . '">'
                        : '<span class="text-muted small" title="No WhatsApp">—</span>';
                    
                    ui_tr([
                        $cb,
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
                ?>
                <!-- Send message card -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">Send message to selected</h5>
                        <?php if (!$has_message_api) { ?>
                        <p class="text-warning small">Message API not configured. Set MESSAGE_API_URL, MESSAGE_API_KEY, MESSAGE_API_ACCOUNT_ID in .env (copy from .env.example).</p>
                        <?php } else { ?>
                        <div class="mb-2">
                            <label class="form-label small">Message (use @variables)</label>
                            <textarea name="message" class="form-control form-control-sm" rows="4" placeholder="e.g. Assalamo Alaikum @full_name, your balance is Rs. @pending. Hoob clearance date: @hoob_clearance_date"></textarea>
                            <small class="text-muted">Variables: @full_name, @hof_id, @takhmeen, @paid_amount, @pending, @hoob_clearance_date, @reason, @whatsapp, @sabeel, @granted_by_name</small>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Image (optional)</label>
                            <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Send message to selected</button>
                        <?php } ?>
                    </div>
                </div>
            </form>
            <script>
            function revokeException(hofId) {
                var f = document.createElement('form');
                f.method = 'post';
                f.innerHTML = '<input type="hidden" name="action" value="revoke"><input type="hidden" name="hof_id" value="' + hofId + '">';
                document.body.appendChild(f);
                f.submit();
            }
            function selectAllExceptions(checked) {
                document.querySelectorAll('.send-checkbox').forEach(function(cb) { cb.checked = checked; });
            }
            function selectOverdueWithBalance() {
                document.querySelectorAll('.send-checkbox').forEach(function(cb) {
                    cb.checked = cb.getAttribute('data-overdue') === '1' && cb.getAttribute('data-balance') === '1';
                });
            }
            </script>
            <?php
            }
            ?>
        </div>
    </div>
    <?php
}

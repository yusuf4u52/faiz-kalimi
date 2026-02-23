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
    } else if ($action === 'send_message_prepare') {
        header('Content-Type: application/json');

        list($config, $configError) = get_message_api_config();
        if ($configError !== null) {
            echo json_encode([
                'result' => 'error',
                'message' => $configError,
            ]);
            exit;
        }

        $hof_ids = isset($_POST['hof_ids']) && is_array($_POST['hof_ids']) ? array_map('trim', $_POST['hof_ids']) : [];
        $message_template = trim($_POST['message'] ?? '');

        if (empty($hof_ids)) {
            echo json_encode([
                'result' => 'error',
                'message' => 'Select at least one recipient.',
            ]);
            exit;
        }
        if ($message_template === '') {
            echo json_encode([
                'result' => 'error',
                'message' => 'Message is required.',
            ]);
            exit;
        }

        $exceptions = get_all_seat_exceptions();
        $by_hof = [];
        foreach ($exceptions as $e) {
            $by_hof[$e->hof_id] = $e;
        }

        $jobs = [];
        $requested = count($hof_ids);
        $skipped_no_number = 0;

        foreach ($hof_ids as $hof_id) {
            $e = $by_hof[$hof_id] ?? null;
            if (!$e) {
                continue;
            }

            $whatsapp = trim($e->whatsapp ?? '');
            if ($whatsapp === '') {
                $skipped_no_number++;
                continue;
            }

            $pending = ($e->takhmeen ?? 0) - ($e->paid_amount ?? 0);
            $message = _seat_exception_replace_message_vars($message_template, $e, $pending);

            $to_number = normalize_whatsapp_number($whatsapp);

            $jobs[] = [
                'job_id' => (string)$hof_id,
                'hof_id' => (string)$hof_id,
                'to_number' => $to_number,
                'message' => $message,
            ];
        }

        echo json_encode([
            'result' => 'success',
            'jobs' => $jobs,
            'stats' => [
                'requested' => $requested,
                'prepared' => count($jobs),
                'skipped_no_number' => $skipped_no_number,
            ],
        ]);
        exit;
    } else if ($action === 'send_single_message') {
        header('Content-Type: application/json');

        list($config, $configError) = get_message_api_config();
        if ($configError !== null) {
            echo json_encode([
                'success' => false,
                'error' => $configError,
            ]);
            exit;
        }

        $to_number = trim($_POST['to_number'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($to_number === '' || $message === '') {
            echo json_encode([
                'success' => false,
                'error' => 'Missing to_number or message.',
            ]);
            exit;
        }

        // Normalise number again as a safety net
        $to_number = normalize_whatsapp_number($to_number);

        // Handle image upload (reused helper)
        list($image_path, $imageError) = get_validated_message_image_path('image');
        if ($imageError !== null) {
            echo json_encode([
                'success' => false,
                'error' => $imageError,
            ]);
            exit;
        }

        $result = send_message_via_api($to_number, $message, $image_path, $config);

        $success = !empty($result['success']);

        echo json_encode([
            'success' => $success,
            'error' => $success ? null : ($result['error'] ?? 'Unknown error'),
        ]);
        exit;
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
    $has_message_api = is_message_api_configured();
    ?>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="mb-0">Active Exceptions <?= ui_muted("(" . count($exceptions) . ")") ?></h4>
                <?php if (!empty($exceptions) && $has_message_api) { ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                    <i class="bi bi-send me-1"></i>Send message
                </button>
                <?php } ?>
            </div>
            <?php
            if (empty($exceptions)) {
                echo ui_muted('No active exceptions.');
            } else {
            ?>
            <form id="send-message-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="send_message_prepare">
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
                <!-- Send message modal (inside form so message + image submit with checkboxes) -->
                <div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="sendMessageModalLabel">Send message to selected</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (!$has_message_api) { ?>
                                <p class="text-warning small mb-0">Message API not configured. Set MESSAGE_API_URL, MESSAGE_API_KEY, MESSAGE_API_ACCOUNT_ID in .env (copy from .env.example).</p>
                                <?php } else { ?>
                                <div class="mb-3">
                                    <label class="form-label small">Message (use @variables)</label>
                                    <textarea name="message" class="form-control form-control-sm" rows="4" placeholder="e.g. Assalamo Alaikum @full_name, your balance is Rs. @pending. Hoob clearance date: @hoob_clearance_date"></textarea>
                                    <small class="text-muted">Variables: @full_name, @hof_id, @takhmeen, @paid_amount, @pending, @hoob_clearance_date, @reason, @whatsapp, @sabeel, @granted_by_name</small>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small">Image (optional)</label>
                                    <input type="file" name="image" class="form-control form-control-sm" accept="image/*">
                                    <small class="text-muted d-block mt-1">If an image is selected, it will be sent with each message. Maximum file size: 5MB. Supported formats: JPEG, PNG, GIF, WebP.</small>
                                </div>
                                <div class="mt-3">
                                    <div id="send-message-status" class="small text-muted"></div>
                                    <div id="send-message-log" class="small mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                                </div>
                                <?php } ?>
                            </div>
                            <?php if ($has_message_api) { ?>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" id="send-message-submit" class="btn btn-primary btn-sm">Send message to selected</button>
                            </div>
                            <?php } ?>
                        </div>
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

            (function() {
                var form = document.getElementById('send-message-form');
                if (!form) return;

                var sendButton = document.getElementById('send-message-submit');
                var statusEl = document.getElementById('send-message-status');
                var logEl = document.getElementById('send-message-log');
                var jobs = [];
                var currentIndex = 0;
                var sentCount = 0;
                var failedCount = 0;
                var skippedNoNumber = 0;
                var isSending = false;
                var SEND_DELAY_MS = 0;

                function setStatus(text) {
                    if (statusEl) {
                        statusEl.textContent = text;
                    }
                }

                function appendLog(text) {
                    if (!logEl) return;
                    var div = document.createElement('div');
                    div.textContent = text;
                    logEl.appendChild(div);
                }

                function updateSummary() {
                    var total = jobs.length;
                    var parts = [];
                    parts.push('Prepared: ' + total);
                    parts.push('Sent: ' + sentCount);
                    if (failedCount > 0) {
                        parts.push('Failed: ' + failedCount);
                    }
                    if (skippedNoNumber > 0) {
                        parts.push('Skipped (no number): ' + skippedNoNumber);
                    }
                    setStatus(parts.join(' • '));
                }

                var imageFile = null; // Store the image file for reuse in bulk sends

                function sendNextJob() {
                    if (currentIndex >= jobs.length) {
                        isSending = false;
                        if (sendButton) {
                            sendButton.disabled = false;
                        }
                        updateSummary();
                        return;
                    }

                    var job = jobs[currentIndex];
                    var indexLabel = (currentIndex + 1) + '/' + jobs.length;

                    var fd = new FormData();
                    fd.append('action', 'send_single_message');
                    fd.append('to_number', job.to_number);
                    fd.append('message', job.message);
                    fd.append('job_id', job.job_id || '');
                    
                    // Include image file if it was selected
                    if (imageFile) {
                        fd.append('image', imageFile);
                    }

                    fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    }).then(function(response) {
                        if (!response.ok) {
                            return { success: false, error: 'HTTP ' + response.status };
                        }
                        return response.json();
                    }).then(function(data) {
                        if (data && data.success) {
                            sentCount++;
                            appendLog(indexLabel + ' ✓ Sent to ' + job.to_number);
                        } else {
                            failedCount++;
                            appendLog(indexLabel + ' ✗ Failed to ' + job.to_number + (data && data.error ? ' (' + data.error + ')' : ''));
                        }
                        currentIndex++;
                        updateSummary();
                        setTimeout(sendNextJob, SEND_DELAY_MS);
                    }).catch(function(error) {
                        failedCount++;
                        appendLog(indexLabel + ' ✗ Failed to ' + job.to_number + ' (' + (error && error.message ? error.message : 'Network error') + ')');
                        currentIndex++;
                        updateSummary();
                        setTimeout(sendNextJob, SEND_DELAY_MS);
                    });
                }

                function handleSend(event) {
                    if (event) {
                        event.preventDefault();
                    }
                    if (isSending) {
                        return;
                    }

                    var selected = form.querySelectorAll('.send-checkbox:checked');
                    if (!selected.length) {
                        alert('Select at least one recipient.');
                        return;
                    }

                    if (statusEl) {
                        statusEl.textContent = '';
                    }
                    if (logEl) {
                        logEl.innerHTML = '';
                    }

                    isSending = true;
                    jobs = [];
                    currentIndex = 0;
                    sentCount = 0;
                    failedCount = 0;
                    skippedNoNumber = 0;

                    if (sendButton) {
                        sendButton.disabled = true;
                    }
                    setStatus('Preparing messages...');

                    // Capture the image file from the form for reuse in each send
                    var imageInput = form.querySelector('input[name="image"]');
                    imageFile = (imageInput && imageInput.files && imageInput.files.length > 0) ? imageInput.files[0] : null;

                    var fd = new FormData(form);
                    fd.set('action', 'send_message_prepare');

                    fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        body: fd
                    }).then(function(response) {
                        if (!response.ok) {
                            return { result: 'error', message: 'HTTP ' + response.status };
                        }
                        return response.json();
                    }).then(function(data) {
                        if (!data || data.result !== 'success') {
                            isSending = false;
                            if (sendButton) {
                                sendButton.disabled = false;
                            }
                            var msg = (data && data.message) ? data.message : 'Failed to prepare messages.';
                            setStatus(msg);
                            return;
                        }

                        jobs = data.jobs || [];
                        if (data.stats && typeof data.stats.skipped_no_number !== 'undefined') {
                            skippedNoNumber = data.stats.skipped_no_number;
                        }

                        if (!jobs.length) {
                            isSending = false;
                            if (sendButton) {
                                sendButton.disabled = false;
                            }
                            updateSummary();
                            return;
                        }

                        updateSummary();
                        sendNextJob();
                    }).catch(function(error) {
                        isSending = false;
                        if (sendButton) {
                            sendButton.disabled = false;
                        }
                        setStatus(error && error.message ? error.message : 'Error preparing messages.');
                    });
                }

                form.addEventListener('submit', handleSend);
                if (sendButton) {
                    sendButton.addEventListener('click', handleSend);
                }
            })();
            </script>
            <?php
            }
            ?>
        </div>
    </div>
    <?php
}

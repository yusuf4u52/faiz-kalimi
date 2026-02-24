<?php

// Handle AJAX actions for specific reports (e.g. vjb_pending) before rendering the page.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportArg = getAppData('arg1');
    $action = $_POST['action'] ?? '';

    if ($reportArg === 'vjb_pending' && in_array($action, ['send_message_prepare', 'send_single_message'], true)) {
        vjb_pending_handle_post($action);
        return;
    }
}

function content_display() {
    $arg = getAppData('arg1');
    if( function_exists($arg) ) {
        echo "<div class='card'>
            <div class='card-body'>";
            $arg();
        echo "</div></div>";
    } else {
        echo '<div class="card">
            <div class="card-body"><h2>No report found..</h2></div>
        </div>';
    }
}

function vjb_registration() {
    // if( !(is_user_role(SUPER_ADMIN)) ) {
    //     do_redirect_with_message('/home', 'You are not authorized to view this page');
    // }
    if (!is_user_a(SUPER_ADMIN, RECEPTION)) {
        do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
    }

    $slot_id = getAppData('arg2');

    $query = 'SELECT 
    l.Thali as sabeel, 
    i.ITS_ID, i.Full_Name, s.title
    FROM kl_shehrullah_vjb_allocation a 
    JOIN kl_shehrullah_vjb_slots s ON s.id = a.slot_id
    JOIN ITS_RECORD i ON i.ITS_ID  = a.hof_id
    JOIN thalilist l ON l.ITS_No = a.hof_id
    WHERE a.slot_id = ? and a.hijri_year = ?
    ';

    $hijri_year = get_current_hijri_year();

    $result = run_statement($query, $slot_id, $hijri_year);
    $records = $result->data;
    util_show_data_table($records, [
        '__show_row_sequence' => 'Sr#',   
        'sabeel' => 'Sabeel',
        'ITS_ID' => 'ITS ID',
        'Full_Name' => 'Name',
        'title' => 'Slot'
    ]);
}

function _vjb_pending_replace_message_vars($template, $row) {
    $replace = [
        '@full_name' => $row->Full_Name ?? '',
        '@its_id' => $row->ITS_ID ?? '',
        '@mobile' => $row->Mobile ?? '',
        '@whatsapp' => $row->WhatsApp_No ?? '',
        '@sabeel' => $row->sabeel ?? '',
    ];
    return str_replace(array_keys($replace), array_values($replace), $template);
}

function vjb_pending_handle_post($action) {
    if ($action === 'send_message_prepare') {
        header('Content-Type: application/json');

        list($config, $configError) = get_message_api_config();
        if ($configError !== null) {
            echo json_encode([
                'result' => 'error',
                'message' => $configError,
            ]);
            exit;
        }

        $its_ids = isset($_POST['its_ids']) && is_array($_POST['its_ids']) ? array_map('trim', $_POST['its_ids']) : [];
        $message_template = trim($_POST['message'] ?? '');

        if (empty($its_ids)) {
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

        // Fetch ITS records (with optional sabeel) for selected IDs
        $records_by_id = [];
        foreach ($its_ids as $its_id) {
            if ($its_id === '') {
                continue;
            }
            $res = run_statement(
                'SELECT i.*, l.Thali as sabeel 
                 FROM ITS_RECORD i 
                 LEFT JOIN thalilist l ON l.ITS_No = i.ITS_ID 
                 WHERE i.ITS_ID = ?',
                $its_id
            );
            if (!empty($res->data[0])) {
                $records_by_id[$its_id] = $res->data[0];
            }
        }

        $jobs = [];
        $requested = count($its_ids);
        $skipped_no_number = 0;

        foreach ($its_ids as $its_id) {
            $row = $records_by_id[$its_id] ?? null;
            if (!$row) {
                continue;
            }

            $whatsapp = trim($row->WhatsApp_No ?? '');
            if ($whatsapp === '') {
                $whatsapp = trim($row->Mobile ?? '');
            }

            if ($whatsapp === '') {
                $skipped_no_number++;
                continue;
            }

            $message = _vjb_pending_replace_message_vars($message_template, $row);

            $to_number = $whatsapp;
            if (preg_match('/^[0-9]{10}$/', $to_number)) {
                $to_number = '+91' . $to_number;
            } elseif (strpos($to_number, '+') !== 0) {
                $to_number = '+' . $to_number;
            }

            $jobs[] = [
                'job_id' => (string)$its_id,
                'its_id' => (string)$its_id,
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
    } elseif ($action === 'send_single_message') {
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

function vjb_pending() {
    // if( !(is_user_role(SUPER_ADMIN)) ) {
    //     do_redirect_with_message('/home', 'You are not authorized to view this page');
    // }

    $query = 'SELECT * FROM ITS_RECORD
    WHERE ITS_ID NOT IN (SELECT hof_id FROM kl_shehrullah_vjb_allocation WHERE hijri_year = ?)
    AND ITS_ID = HOF_ID';

    $hijri_year = get_current_hijri_year();

    $result = run_statement($query, $hijri_year);
    $records = $result->data;

    $has_message_api = is_message_api_configured();
    ?>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="mb-0">Pending Vajebaat Mumineen <?= ui_muted("(" . count($records) . ")") ?></h4>
                <?php if (!empty($records) && $has_message_api) { ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendMessageModal">
                    <i class="bi bi-send me-1"></i>Send message
                </button>
                <?php } ?>
            </div>
            <?php
            if (empty($records)) {
                echo ui_muted('No pending records.');
            } else {
            ?>
            <form id="send-message-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="send_message_prepare">
                <div class="mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllPending(true)">Select all</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectAllPending(false)">Select none</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Send</th>
                                <th scope="col">ITS ID</th>
                                <th scope="col">Name</th>
                                <th scope="col">Mobile</th>
                                <th scope="col">WhatsApp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $row) {
                                $whatsapp = trim($row->WhatsApp_No ?? '');
                                $has_whatsapp = $whatsapp !== '';
                            ?>
                            <tr>
                                <td>
                                    <?php if ($has_whatsapp) { ?>
                                        <input type="checkbox" name="its_ids[]" value="<?= h($row->ITS_ID) ?>" class="send-checkbox" data-whatsapp="1">
                                    <?php } else { ?>
                                        <span class="text-muted small" title="No WhatsApp">—</span>
                                    <?php } ?>
                                </td>
                                <td><?= ui_code($row->ITS_ID) ?></td>
                                <td><?= h($row->Full_Name) ?></td>
                                <td><?= h($row->Mobile) ?></td>
                                <td>
                                    <?php if ($whatsapp) { ?>
                                        <a href="https://wa.me/<?= h($whatsapp) ?>" target="_blank"><?= h($whatsapp) ?></a>
                                    <?php } else { ?>
                                        <span class="text-muted small">—</span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
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
                                    <textarea name="message" class="form-control form-control-sm" rows="4" placeholder="e.g. Assalamo Alaikum @full_name, your vajebaat baithak is pending."></textarea>
                                    <small class="text-muted">Variables: @full_name, @its_id, @mobile, @whatsapp, @sabeel</small>
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
            function selectAllPending(checked) {
                document.querySelectorAll('.send-checkbox').forEach(function(cb) { cb.checked = checked; });
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


function fmb_lq_niyat() {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT * from kl_fmb_data WHERE updated > "2025-03-17" and year=?';

    $result = run_statement($query, $hijri_year);
    $records = $result->data;
    // util_show_data_table($records, [
    //     '__show_row_sequence' => 'Sr#',        
    //     'its_id' => 'ITS ID',
    //     'name' => 'Name',
    //     'takhmeen'=>'takhmeen',
    //     'lq_niyat' => 'LQ Niyat'
    // ]);

    $query = 'SELECT sum(takhmeen) takhmeen_sum,sum(lq_niyat) niyat_sum FROM kl_fmb_data WHERE updated > "2025-03-17" and year=?';

    $result = run_statement($query, $hijri_year);
    $summary_data = $result->data[0];


    $cols = [
        '__show_row_sequence' => 'Sr#',        
        'its_id' => 'ITS ID',
        'name' => 'Name',
        'takhmeen'=>'takhmeen',
        'lq_niyat' => 'LQ Niyat'
    ];

    $colKeys = [];
    $colLabels = [];
    foreach ($cols as $key => $value) {
        $colLabels[] = $value;
        $colKeys[] = $key;
    }

    ?>
    <h2 class="mb-3">FMB LQ Niyat Report</h2>                    
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <?= util_table_header_row($colLabels) ?>
            </thead>
            <tbody>
                <?php util_table_data_rows($records, $colKeys);
                if( !is_null($summary_data) ) {
                ?>
                <tr>
                    <td colspan="3">TOTAL ==></td>
                    <td><?=$summary_data->takhmeen_sum?></td>
                    <td><?=$summary_data->niyat_sum?></td>                   
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}

function registered_users() {
    if( !(is_user_role(SUPER_ADMIN)) ) {
        do_redirect_with_message('/home', 'You are not authorized to view this page');
    }

    function __chair_preference($row, $index) {
        $chair_preference = $row->chair_preference ?? 'N';  
        return $chair_preference == 'Y' ? 'Yes' : 'No';
    }

    $hijri_year = get_current_hijri_year();
    
    $query = 'SELECT DISTINCT i.its_id, i.full_name, i.age, i.gender, i.misaq, a.chair_preference
              FROM its_data i
              INNER JOIN kl_shehrullah_attendees a ON i.its_id = a.its_id
              WHERE a.year = ? AND a.attendance_type = "Y"
              ORDER BY i.its_id ASC';

    $result = run_statement($query, $hijri_year);
    $records = $result->data;
    
    $uri = getAppData('BASE_URI');
    ?>
    <div class="card">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-6">
                    <h2 class="mb-3">Registered Users Report (Shehrullah Form Filled)</h2>
                </div>
                <div class="col-6 text-end">
                    <a href="<?=$uri?>/report.registered_users_download" class="btn btn-light mb-3">Download Excel</a>
                </div>
            </div>
            <?php util_show_data_table($records, [
                '__show_row_sequence' => 'Sr#',        
                'its_id' => 'ITS ID',
                'full_name' => 'Name',
                'gender' => 'Gender',
                'age' => 'Age',
                'misaq' => 'Misaq',
                '__chair_preference' => 'Chair'
            ]); ?>
        </div>
            </div>
        </div>
    </div>
    <?php
}





<?php
if (!is_user_a(SUPER_ADMIN)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}

// Handle bulk mark as received/pending actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;
    $hijri_year = get_current_hijri_year();
    
    // Get same filters as the display
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    $status_filter = $_POST['status_filter'] ?? 'all';
    
    if ($action === 'bulk_mark_received') {
        $udata = getSessionData(THE_SESSION_ID);
        $received_by = $udata->itsid ?? null;
        $result = bulk_mark_receipts_as_received($hijri_year, $from_date, $to_date, $status_filter, $received_by);
        $count = $result['count'];
        $amount = number_format($result['total_amount']);
        setSessionData(TRANSIT_DATA, "Successfully marked $count CASH receipt(s) as received (Rs. $amount).");
    } elseif ($action === 'bulk_mark_pending') {
        $result = bulk_mark_receipts_as_pending($hijri_year, $from_date, $to_date, $status_filter);
        $count = $result['count'];
        $amount = number_format($result['total_amount']);
        setSessionData(TRANSIT_DATA, "Successfully marked $count CASH receipt(s) as pending (Rs. $amount).");
    }
    
    // Redirect to prevent form resubmission, preserving filters
    $redirect_url = getAppData('BASE_URI') . '/receipt_list';
    if ($from_date || $to_date || $status_filter != 'all') {
        $params = [];
        if ($from_date) $params[] = 'from_date=' . urlencode($from_date);
        if ($to_date) $params[] = 'to_date=' . urlencode($to_date);
        if ($status_filter != 'all') $params[] = 'status_filter=' . urlencode($status_filter);
        $redirect_url .= '?' . implode('&', $params);
    }
    header('Location: ' . $redirect_url);
    exit;
}

function content_display()
{
    $hijri_year = get_current_hijri_year();
    
    // Get filter parameters
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $status_filter = $_GET['status_filter'] ?? 'all';
    
    // Get filtered receipt data
    $receipt_data = get_filtered_receipt_data($hijri_year, $from_date, $to_date, $status_filter);
    
    // Get cash receipt summary for bulk actions
    $cash_summary = get_cash_receipt_summary($hijri_year, $from_date, $to_date, $status_filter);
    
    // Calculate overall statistics from filtered data
    $total_count = count($receipt_data);
    $total_amount = 0;
    $cash_count = 0;
    $cash_amount = 0;
    $online_count = 0;
    $online_amount = 0;
    $cheque_count = 0;
    $cheque_amount = 0;
    $pending_count = 0;
    $pending_amount = 0;
    $received_count = 0;
    $received_amount = 0;
    
    foreach ($receipt_data as $receipt) {
        $total_amount += $receipt->amount;
        
        // Count by payment mode
        if ($receipt->payment_mode === 'cash') {
            $cash_count++;
            $cash_amount += $receipt->amount;
        } elseif ($receipt->payment_mode === 'online') {
            $online_count++;
            $online_amount += $receipt->amount;
        } elseif ($receipt->payment_mode === 'cheque') {
            $cheque_count++;
            $cheque_amount += $receipt->amount;
        }
        
        // Count by received status
        if ($receipt->received_status === 'pending') {
            $pending_count++;
            $pending_amount += $receipt->amount;
        } else {
            $received_count++;
            $received_amount += $receipt->amount;
        }
    }
    ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-3">Receipt History</h2>
            
            <!-- Filter Form -->
            <form method="get" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="from_date" class="form-label fw-semibold small">
                            <i class="bi bi-calendar-event me-1"></i>From Date
                        </label>
                        <input type="date" class="form-control form-control-sm" 
                               id="from_date" name="from_date" 
                               value="<?= htmlspecialchars($from_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="to_date" class="form-label fw-semibold small">
                            <i class="bi bi-calendar-event me-1"></i>To Date
                        </label>
                        <input type="date" class="form-control form-control-sm" 
                               id="to_date" name="to_date" 
                               value="<?= htmlspecialchars($to_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status_filter" class="form-label fw-semibold small">
                            <i class="bi bi-funnel me-1"></i>Status
                        </label>
                        <select class="form-select form-select-sm" id="status_filter" name="status_filter">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="received" <?= $status_filter === 'received' ? 'selected' : '' ?>>Received</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-light btn-sm me-2">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="<?= getAppData('BASE_URI') ?>/receipt_list" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-x-circle me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
            
            <?php if ($from_date || $to_date || $status_filter != 'all'): ?>
                <div class="alert alert-info alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Filter Applied:</strong> 
                    <?php if ($status_filter != 'all'): ?>
                        Showing <strong><?= ucfirst($status_filter) ?></strong> receipts
                    <?php endif; ?>
                    <?php if ($from_date && $to_date): ?>
                        from <strong><?= htmlspecialchars($from_date) ?></strong> 
                        to <strong><?= htmlspecialchars($to_date) ?></strong>
                    <?php elseif ($from_date): ?>
                        from <strong><?= htmlspecialchars($from_date) ?></strong> onwards
                    <?php elseif ($to_date): ?>
                        up to <strong><?= htmlspecialchars($to_date) ?></strong>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Summary Statistics -->
            <div class="card mb-4 border-secondary">
                <div class="card-header bg-secondary text-white py-2">
                    <i class="bi bi-graph-up me-2"></i>Summary (Filtered Results)
                </div>
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>ALL RECEIPTS:</strong> <?= $total_count ?> receipts | Rs. <?= number_format($total_amount) ?>
                            <ul class="mb-0 mt-2">
                                <li><i class="bi bi-cash-stack text-success"></i> CASH: <?= $cash_count ?> receipts | Rs. <?= number_format($cash_amount) ?></li>
                                <li><i class="bi bi-credit-card text-info"></i> Online: <?= $online_count ?> receipts | Rs. <?= number_format($online_amount) ?></li>
                                <li><i class="bi bi-wallet2 text-warning"></i> Cheque: <?= $cheque_count ?> receipts | Rs. <?= number_format($cheque_amount) ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-around">
                                <div class="text-center">
                                    <div class="badge bg-success fs-6">Received</div>
                                    <div class="fw-bold"><?= $received_count ?> (Rs. <?= number_format($received_amount) ?>)</div>
                                </div>
                                <div class="text-center">
                                    <div class="badge bg-warning fs-6">Pending</div>
                                    <div class="fw-bold"><?= $pending_count ?> (Rs. <?= number_format($pending_amount) ?>)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bulk Action Buttons -->
            <?php if ($cash_summary['count'] > 0): ?>
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white py-2">
                        <i class="bi bi-lightning-fill me-2"></i>Bulk Actions (CASH ONLY)
                    </div>
                    <div class="card-body py-3">
                        <div class="alert alert-info py-2 mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> Only CASH payment receipts can be marked as received/pending. 
                            Online and Cheque receipts are excluded from bulk actions.
                        </div>
                        
                        <form method="post" id="bulk-action-form">
                            <input type="hidden" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
                            <input type="hidden" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
                            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
                            <input type="hidden" name="action" id="bulk-action-type" value="">
                            
                            <div class="d-flex gap-3 justify-content-center">
                                <?php if ($cash_summary['pending_count'] > 0): ?>
                                    <button type="button" class="btn btn-success" 
                                            onclick="setBulkAction('bulk_mark_received', <?= $cash_summary['pending_count'] ?>, <?= $cash_summary['pending_amount'] ?>)">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Mark All <?= $cash_summary['pending_count'] ?> Cash Receipts as Received 
                                        (Rs. <?= number_format($cash_summary['pending_amount']) ?>)
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($cash_summary['received_count'] > 0): ?>
                                    <button type="button" class="btn btn-warning" 
                                            onclick="setBulkAction('bulk_mark_pending', <?= $cash_summary['received_count'] ?>, <?= $cash_summary['received_amount'] ?>)">
                                        <i class="bi bi-arrow-counterclockwise me-2"></i>
                                        Mark All <?= $cash_summary['received_count'] ?> Cash Receipts as Pending 
                                        (Rs. <?= number_format($cash_summary['received_amount']) ?>)
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php __display_table_records([$receipt_data]) ?>
        </div>
    </div>
    
    <script>
    function setBulkAction(action, count, amount) {
        // Set the action type
        document.getElementById('bulk-action-type').value = action;
        
        // Build confirmation message
        let message = '';
        const formattedAmount = amount.toLocaleString('en-IN');
        
        if (action === 'bulk_mark_received') {
            message = `Are you sure you want to mark ${count} CASH receipt(s) as received (Rs. ${formattedAmount})?`;
        } else if (action === 'bulk_mark_pending') {
            message = `Are you sure you want to mark ${count} CASH receipt(s) as pending (Rs. ${formattedAmount})?`;
        }
        
        // Confirm and submit
        if (confirm(message)) {
            document.getElementById('bulk-action-form').submit();
        }
    }
    </script>
    
    <style>
    .badge {
        font-size: 0.85rem;
    }
    .card-header {
        font-weight: 600;
    }
    </style>
    <?php
}

function __display_table_records($data)
{
    $records = $data[0];
    util_show_data_table($records, [
        '__show_row_sequence' => 'SN#',
        'id' => 'Receipt ID',
        'hof_id' => 'HOF ID',
        'hof_name' => 'HOF Name',
        'amount' => 'Amount (Rs.)',
        'payment_mode' => 'Payment Mode',
        '__transaction_ref' => 'Reference',
        '__created_date' => 'Date',
        'createdby_name' => 'Created By',
        '__received_status_badge' => 'Status',
        '__print_link' => 'Print'
    ]);
}

function __created_date($row, $index) {
    if (empty($row->created)) {
        return '-';
    }
    // Convert the datetime to dd/mm/yyyy format
    $date = new DateTime($row->created);
    return $date->format('d/m/Y');
}

function __transaction_ref($row, $index) {
    return $row->transaction_ref ?: '-';
}

function __received_status_badge($row, $index) {
    if ($row->received_status === 'received') {
        $badge = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Received</span>';
        if ($row->received_by_name && $row->received_at) {
            $date = new DateTime($row->received_at);
            $formatted_date = $date->format('d/m/Y g:i A');
            $badge .= '<br><small class="text-muted">by ' . htmlspecialchars($row->received_by_name) . ' on ' . $formatted_date . '</small>';
        }
        return $badge;
    } else {
        return '<span class="badge bg-warning"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';
    }
}

function __print_link($row, $index) {
    $receipt_num = $row->id;
    $uri = getAppData('BASE_URI');
    return "<a target='receipt' class='btn btn-sm btn-light' href='$uri/receipt2/$receipt_num'><i class='bi bi-printer'></i></a>";
}

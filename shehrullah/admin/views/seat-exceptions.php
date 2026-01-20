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
        }
    } else if ($action === 'grant') {
        $hof_id = $_POST['hof_id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if (empty($hof_id)) {
            setSessionData(TRANSIT_DATA, 'Invalid HOF ID.');
            return;
        }
        
        $userData = getSessionData(THE_SESSION_ID);
        $granted_by = $userData->itsid ?? '';
        
        $success = grant_seat_exception($hof_id, $reason, $granted_by);
        
        if ($success) {
            setSessionData(TRANSIT_DATA, 'Exception granted successfully! Family can now select seats.');
        } else {
            setSessionData(TRANSIT_DATA, 'Failed to grant exception.');
        }
    } else if ($action === 'revoke') {
        $hof_id = $_POST['hof_id'] ?? '';
        
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
    ?>
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Seat Exceptions - <?= $hijri_year ?>H</h5>
                    <small class="text-muted">Allow seat selection without full payment</small>
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
            
            <?php if ($search_thaali_data) { ?>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong><?= $search_thaali_data->NAME ?></strong> 
                        <code class="small"><?= $search_hof_id ?></code>
                        <br><small class="text-muted">Sabeel <?= $search_thaali_data->Thali ?></small>
                    </div>
                    
                    <table class="table table-sm table-bordered">
                        <?php if ($search_takhmeen) { 
                            $pending = $search_takhmeen->takhmeen - $search_takhmeen->paid_amount;
                        ?>
                        <tr>
                            <th width="100">Takhmeen</th>
                            <td>Rs. <?= number_format($search_takhmeen->takhmeen) ?></td>
                        </tr>
                        <tr>
                            <th>Paid</th>
                            <td>Rs. <?= number_format($search_takhmeen->paid_amount) ?></td>
                        </tr>
                        <tr>
                            <th>Balance</th>
                            <td>
                                <?php if ($pending > 0) { ?>
                                    <span class="text-danger">Rs. <?= number_format($pending) ?></span>
                                <?php } else { ?>
                                    <span class="text-success">Fully Paid</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php } else { ?>
                        <tr>
                            <td colspan="2" class="text-warning small">Takhmeen not done</td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <th>Exception</th>
                            <td>
                                <?php if ($has_exception) { ?>
                                    <span class="text-success">● Granted</span>
                                <?php } else { ?>
                                    <span class="text-muted">○ Not granted</span>
                                <?php } ?>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Grant/Revoke Form -->
                    <?php if (!$has_exception) { ?>
                    <form method="post">
                        <input type="hidden" name="action" value="grant">
                        <input type="hidden" name="hof_id" value="<?= $search_hof_id ?>">
                        <div class="mb-2">
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason for exception" required>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success">Grant Exception</button>
                    </form>
                    <?php } else { ?>
                    <form method="post">
                        <input type="hidden" name="action" value="revoke">
                        <input type="hidden" name="hof_id" value="<?= $search_hof_id ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Revoke Exception</button>
                    </form>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
    
    <!-- Active Exceptions List -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Active Exceptions <span class="text-muted">(<?= count($exceptions) ?>)</span></h6>
        </div>
        <div class="card-body">
            <?php if (empty($exceptions)) { ?>
                <p class="text-muted small mb-0">No active exceptions.</p>
            <?php } else { ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>HOF</th>
                            <th>Name</th>
                            <th>Takhmeen</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Reason</th>
                            <th>Granted</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exceptions as $exc) { 
                            $pending = ($exc->takhmeen ?? 0) - ($exc->paid_amount ?? 0);
                        ?>
                        <tr>
                            <td><code class="small"><?= $exc->hof_id ?></code></td>
                            <td><?= $exc->full_name ?></td>
                            <td><small>Rs. <?= number_format($exc->takhmeen ?? 0) ?></small></td>
                            <td><small>Rs. <?= number_format($exc->paid_amount ?? 0) ?></small></td>
                            <td>
                                <?php if ($pending > 0) { ?>
                                    <small class="text-danger">Rs. <?= number_format($pending) ?></small>
                                <?php } else { ?>
                                    <small class="text-success">Paid</small>
                                <?php } ?>
                            </td>
                            <td><small><?= $exc->reason ?: '—' ?></small></td>
                            <td><small class="text-muted"><?= date('d/m/y H:i', strtotime($exc->granted_at)) ?></small></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="revoke">
                                    <input type="hidden" name="hof_id" value="<?= $exc->hof_id ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0">Revoke</button>
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

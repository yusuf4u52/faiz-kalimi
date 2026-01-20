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
    <div class="card mb-4">
        <div class="card-header">
            <h4 class="card-title">Seat Selection Exceptions - <?= $hijri_year ?>H</h4>
            <p class="text-muted">Grant exceptions to allow seat selection without full payment</p>
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
            
            <?php if ($search_thaali_data) { ?>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <h5>Family Details</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th>HOF ID</th>
                            <td><?= $search_hof_id ?></td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td><?= $search_thaali_data->NAME ?></td>
                        </tr>
                        <tr>
                            <th>Sabeel</th>
                            <td><?= $search_thaali_data->Thali ?></td>
                        </tr>
                        <?php if ($search_takhmeen) { ?>
                        <tr>
                            <th>Takhmeen</th>
                            <td>Rs. <?= number_format($search_takhmeen->takhmeen) ?></td>
                        </tr>
                        <tr>
                            <th>Paid</th>
                            <td>Rs. <?= number_format($search_takhmeen->paid_amount) ?></td>
                        </tr>
                        <tr>
                            <th>Pending</th>
                            <td>
                                <?php 
                                $pending = $search_takhmeen->takhmeen - $search_takhmeen->paid_amount;
                                if ($pending > 0) {
                                    echo "<span class='text-danger'>Rs. " . number_format($pending) . "</span>";
                                } else {
                                    echo "<span class='text-success'>Fully Paid</span>";
                                }
                                ?>
                            </td>
                        </tr>
                        <?php } else { ?>
                        <tr>
                            <td colspan="2" class="text-warning">Takhmeen not done</td>
                        </tr>
                        <?php } ?>
                        <tr>
                            <th>Exception Status</th>
                            <td>
                                <?php if ($has_exception) { ?>
                                    <span class="badge bg-success">Exception Granted</span>
                                <?php } else { ?>
                                    <span class="badge bg-secondary">No Exception</span>
                                <?php } ?>
                            </td>
                        </tr>
                    </table>
                    
                    <!-- Grant/Revoke Form -->
                    <?php if (!$has_exception) { ?>
                    <form method="post">
                        <input type="hidden" name="action" value="grant">
                        <input type="hidden" name="hof_id" value="<?= $search_hof_id ?>">
                        <div class="mb-3">
                            <label class="form-label">Reason for Exception</label>
                            <input type="text" name="reason" class="form-control" placeholder="Enter reason" required>
                        </div>
                        <button type="submit" class="btn btn-success" onclick="return confirm('Grant seat selection exception to this family?')">Grant Exception</button>
                    </form>
                    <?php } else { ?>
                    <form method="post">
                        <input type="hidden" name="action" value="revoke">
                        <input type="hidden" name="hof_id" value="<?= $search_hof_id ?>">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Revoke seat selection exception? Family will not be able to select seats until payment is complete.')">Revoke Exception</button>
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
            <h5 class="card-title">Active Exceptions (<?= count($exceptions) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($exceptions)) { ?>
                <p class="text-muted">No active exceptions.</p>
            <?php } else { ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>HOF ID</th>
                            <th>Name</th>
                            <th>Takhmeen</th>
                            <th>Paid</th>
                            <th>Pending</th>
                            <th>Reason</th>
                            <th>Granted At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exceptions as $exc) { 
                            $pending = ($exc->takhmeen ?? 0) - ($exc->paid_amount ?? 0);
                        ?>
                        <tr>
                            <td><?= $exc->hof_id ?></td>
                            <td><?= $exc->full_name ?></td>
                            <td>Rs. <?= number_format($exc->takhmeen ?? 0) ?></td>
                            <td>Rs. <?= number_format($exc->paid_amount ?? 0) ?></td>
                            <td>
                                <?php if ($pending > 0) { ?>
                                    <span class="text-danger">Rs. <?= number_format($pending) ?></span>
                                <?php } else { ?>
                                    <span class="text-success">Paid</span>
                                <?php } ?>
                            </td>
                            <td><?= $exc->reason ?: '-' ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($exc->granted_at)) ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="revoke">
                                    <input type="hidden" name="hof_id" value="<?= $exc->hof_id ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Revoke this exception?')">Revoke</button>
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
    
    <div class="mt-3">
        <a href="<?= $url ?>/seat-management" class="btn btn-secondary">Back to Seat Management</a>
    </div>
    <?php
}

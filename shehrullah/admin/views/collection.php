<?php
if_not_post_redirect('/home');

if (!is_user_a(SUPER_ADMIN, TAKHMEENER)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}

do_for_post('__handle_post');

function __handle_post()
{
    $action = $_POST['action'] ?? null;
    $hof_id = $_POST['hof_id'] ?? null;
    $hijri_year = get_current_hijri_year();
    $uri = getAppData('BASE_URI');
    if ($action === 'register') {

        $amount = $_POST['amount'] ?? null;
        $payment_mode = $_POST['payment_mode'] ?? null;
        $transaction_ref = $_POST['transaction_ref'] ?? null;
        $remarks = $_POST['remarks'] ?? null;
        
        $receipt_num = save_collection_record($hijri_year, $hof_id, $amount, $payment_mode, 
        $transaction_ref, $remarks);

        if( $receipt_num == -1 ) {
            setSessionData(TRANSIT_DATA , 'Oops! Could not save that.');
        } else {            
            do_redirect('/receipt2/'.$receipt_num);
        }

    }

    $hof_data = get_hof_data($hof_id);
    setAppData('hof_data', $hof_data);

    $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
    if (is_null($takhmeen_data)) {
        do_redirect_with_message('/home', 'Oops! data not found.');
    }
    if( $takhmeen_data->takhmeen == 0 ) {
        do_redirect_with_message('/home', 'Oops! takhmeen entry not done.');
    }

    setAppData('takhmeen_data', $takhmeen_data);
}

function content_display()
{
    $takhmeen_data = getAppData('takhmeen_data');
    $hof_id = $_POST['hof_id'] ?? null;
    $hof_data = getAppData('hof_data');
    
    $pending_amount = $takhmeen_data->takhmeen - $takhmeen_data->paid_amount;
    $paid_amount = $takhmeen_data->paid_amount ?? 0;
    $total_takhmeen = $takhmeen_data->takhmeen ?? 0;
    ?>
    
    <?php ui_card("Payment Collection", "Record payment for HOF: <strong>" . htmlspecialchars($hof_data->full_name) . "</strong>"); ?>
    
    <form method="post" id="collection-form">
        <input type="hidden" name="hof_id" value="<?=$hof_id?>">
        <input type="hidden" name="action" value="register">
        
        <div class="row g-2">
            <!-- Left Column: HOF Info and Payment Summary -->
            <div class="col-md-5">
                <!-- HOF Information Card -->
                <div class="card mb-2 border-primary">
                    <div class="card-header bg-primary text-white py-1">
                        <i class="bi bi-person-circle me-2"></i>HOF Information
                    </div>
                    <div class="card-body py-2">
                        <div class="row">
                            <label class="col-sm-4 col-form-label fw-bold small">HOF Name</label>
                            <div class="col-sm-8">
                                <div class="form-control-plaintext fw-semibold small"><?= htmlspecialchars($hof_data->full_name) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Summary Card -->
                <div class="card mb-2 border-info">
                    <div class="card-header bg-info text-white py-1">
                        <i class="bi bi-cash-coin me-2"></i>Payment Summary
                    </div>
                    <div class="card-body py-2">
                        <div class="row mb-2">
                            <label class="col-sm-5 col-form-label fw-semibold small">Total Takhmeen</label>
                            <div class="col-sm-7">
                                <div class="form-control-plaintext fw-bold text-primary small">
                                    <i class="bi bi-currency-rupee me-1"></i><?= number_format($total_takhmeen) ?>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="col-sm-5 col-form-label fw-semibold small">Paid Amount</label>
                            <div class="col-sm-7">
                                <div class="form-control-plaintext text-success fw-semibold small">
                                    <i class="bi bi-check-circle me-1"></i>Rs. <?= number_format($paid_amount) ?>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row">
                            <label class="col-sm-5 col-form-label fw-bold small">Pending Amount</label>
                            <div class="col-sm-7">
                                <div class="alert alert-warning mb-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                        <span class="fw-bold">
                                            Rs. <?= number_format($pending_amount) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Payment Details and Actions -->
            <div class="col-md-7">
                <!-- Payment Details Card -->
                <div class="card mb-2">
                    <div class="card-header bg-light py-1">
                        <i class="bi bi-credit-card me-2"></i>Payment Details
                    </div>
                    <div class="card-body py-2">
                        <div class="row mb-2">
                            <label for="amount" class="col-sm-4 col-form-label fw-semibold small">
                                <i class="bi bi-currency-rupee me-1"></i>Payment Amount
                            </label>
                            <div class="col-sm-8">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">Rs.</span>
                                    <input type="text" pattern="^[0-9]{1,9}$" required 
                                        class="form-control" id="amount" name="amount"
                                        value="" placeholder="Enter payment amount">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="fill-pending-btn" 
                                        onclick="fillPendingAmount()" title="Fill pending amount">
                                        <i class="bi bi-arrow-down-circle"></i> Fill
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <label for="payment_mode" class="col-sm-4 col-form-label fw-semibold small">
                                <i class="bi bi-wallet2 me-1"></i>Payment Mode
                            </label>
                            <div class="col-sm-8">
                                <select class="form-select form-select-sm" required name="payment_mode" id="payment_mode">
                                    <option value="">Select payment mode...</option>
                                    <option value="cash">Cash</option>
                                    <option value="online">Online Transfer</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-2" id="transaction_ref_section" style="display: none;">
                            <label for="transaction_ref" class="col-sm-4 col-form-label fw-semibold small">
                                <i class="bi bi-receipt me-1"></i>Reference Number
                            </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" 
                                    id="transaction_ref" name="transaction_ref" value=""
                                    placeholder="Enter transaction/cheque reference number">
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <label for="remarks" class="col-sm-4 col-form-label fw-semibold small">
                                <i class="bi bi-chat-left-text me-1"></i>Remarks
                            </label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control form-control-sm" id="remarks" name="remarks"
                                    placeholder="Optional remarks or notes">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-grid gap-1">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-2"></i>Save Payment
                    </button>
                    <div class="d-flex gap-1">
                        <a href="<?= getAppData('BASE_URI') ?>/takhmeen?hof_id=<?= urlencode($hof_id) ?>" class="btn btn-outline-secondary flex-fill">
                            <i class="bi bi-arrow-left me-2"></i>Back to Takhmeen
                        </a>
                        <a href="<?= getAppData('BASE_URI') ?>/home" class="btn btn-outline-secondary flex-fill">
                            <i class="bi bi-house me-2"></i>Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <?php ui_card_end(); ?>
    
    <script>
        const pendingAmount = <?= $pending_amount ?>;
        
        function fillPendingAmount() {
            $('#amount').val(pendingAmount);
            $('#amount').focus();
            // Add visual feedback
            $('#amount').addClass('border-success');
            setTimeout(function() {
                $('#amount').removeClass('border-success');
            }, 1000);
        }
        
        function the_script() {
            // Hide transaction reference section initially
            $('#transaction_ref_section').hide();
            
            // Show/hide transaction reference based on payment mode
            $('#payment_mode').on('change', function() {
                var selectedValue = $(this).val();
                if(selectedValue == "online" || selectedValue == "cheque") {
                    $('#transaction_ref_section').slideDown(300);
                    $("#transaction_ref").prop('required', true);
                } else {
                    $('#transaction_ref_section').slideUp(300);
                    $("#transaction_ref").prop('required', false);
                    $("#transaction_ref").val('');
                }
            });
            
            // Validate amount doesn't exceed pending
            $('#amount').on('blur', function() {
                var enteredAmount = parseFloat($(this).val()) || 0;
                if (enteredAmount > pendingAmount) {
                    alert('Payment amount cannot exceed pending amount of Rs. ' + pendingAmount.toLocaleString('en-IN'));
                    $(this).val('');
                    $(this).focus();
                }
            });
            
            // Format amount input
            $('#amount').on('input', function() {
                var value = $(this).val().replace(/[^0-9]/g, '');
                $(this).val(value);
            });
        }
    </script>
    <style>
        .card-header {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-select:focus, .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .border-success {
            border-color: #198754 !important;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25) !important;
        }
        #transaction_ref_section {
            transition: all 0.3s ease;
        }
        .small {
            font-size: 0.875rem;
        }
        #collection-form .card-body {
            padding: 0.5rem;
        }
        #collection-form .row {
            margin-bottom: 0.25rem;
        }
        body {
            overflow-x: hidden;
        }
    </style>
    
    <?php
}
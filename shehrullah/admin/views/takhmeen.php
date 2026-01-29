<?php
if (!is_post() && !isset($_GET['hof_id'])) {
    do_redirect('/home');
}

if (!is_user_a(SUPER_ADMIN, TAKHMEENER)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}

// Helper function to load and set data
function load_takhmeen_data($hof_id) {
    $hijri_year = get_current_hijri_year();
    $hub_data = get_shehrullah_data_for($hijri_year);
    $hof_data = get_hof_data($hof_id);
    $takhmeen_data = get_shehrullah_takhmeen_for($hof_id, $hijri_year);
    
    if (is_null($takhmeen_data)) {
        do_redirect_with_message('/home', 'Oops! data not found.');
    }
    
    $receipt_data = get_receipt_data_for($hijri_year, $hof_id);
    
    setAppData('hof_id', $hof_id);
    setAppData('hof_data', $hof_data);
    setAppData('hub_data', $hub_data);
    setAppData('takhmeen_data', $takhmeen_data);
    setAppData('receipt_data', $receipt_data);
}

// Handle GET request
if (is_get() && isset($_GET['hof_id'])) {
    load_takhmeen_data($_GET['hof_id']);
}

do_for_post('__handle_post');

function __handle_post() {
    $action = $_POST['action'];
    $hof_id = $_POST['hof_id'];
    
    load_takhmeen_data($hof_id);
    
    $hub_data = getAppData('hub_data');
    $takhmeen_data = getAppData('takhmeen_data');
    $receipt_data = getAppData('receipt_data');
    
    if ($action === 'register') {
        $hijri_year = get_current_hijri_year();
        $niyaz_hub = $_POST['niyaz_hub'];
        $iftar_count = $_POST['iftar_count'];
        $zabihat_count = $_POST['zabihat_count'];
        $fateha_count = $_POST['fateha_count'];
        $khajoor_count = $_POST['khajoor_count'];
        $pirsa_count = $_POST['pirsa_count'];
        $chair_count = $_POST['chair_count'];
        
        $takhmeen = $niyaz_hub + ($iftar_count * $hub_data->iftar)
            + ($zabihat_count * $hub_data->zabihat)
            + ($fateha_count * $hub_data->fateha)
            + ($khajoor_count * $hub_data->khajoor)
            + ($pirsa_count * $hub_data->pirsu)
            + ($chair_count * $hub_data->chair);
        
        // Check if can decrease takhmeen when receipts exist
        if (is_array($receipt_data) && count($receipt_data) > 0 && $takhmeen_data->takhmeen > $takhmeen) {
            setSessionData(TRANSIT_DATA, 'Oops! No you can not decrease the takhmeen amount. One or more recipt is created.');
        } else {
            add_shehrullah_takh_hub($hijri_year, $hof_id, $niyaz_hub, $iftar_count, 
                $zabihat_count, $fateha_count, $khajoor_count, $pirsa_count, $chair_count, $takhmeen);
        }
        
        // Reload data and redirect with saved=1
        load_takhmeen_data($hof_id);
        do_redirect('/takhmeen?hof_id=' . urlencode($hof_id) . '&saved=1');
        exit();
    }
    
    // For SEARCH and other actions, redirect to GET without saved parameter
    do_redirect('/takhmeen?hof_id=' . urlencode($hof_id));
    exit();
}

function content_display()
{
    $hof_id = getAppData('hof_id');
    $hof_data = getAppData('hof_data');
    $hub_data = getAppData('hub_data');
    $takhmeen_data = getAppData('takhmeen_data');
    $receipt_data = getAppData('receipt_data');
    
    // Check for success message
    $saved = isset($_GET['saved']) && $_GET['saved'] == '1';
    ?>
    
    <?php ui_card("Takhmeen Entry", "Enter takhmeen details for HOF: <strong>" . htmlspecialchars($hof_data->full_name) . "</strong>"); ?>
    
    <?php if ($saved) { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><strong>Success!</strong> Takhmeen has been saved successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>
    
    <form method="post" id="takhmeen-form">
        <input type="hidden" name="hof_id" value="<?= $hof_id ?>">
        <input type="hidden" name="action" value="register">
        
        <!-- HOF Information Card -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-circle me-2"></i>HOF Information
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label class="col-sm-3 col-form-label fw-bold">HOF Name</label>
                    <div class="col-sm-9">
                        <input type="text" readonly class="form-control-plaintext fw-semibold" value="<?= htmlspecialchars($hof_data->full_name) ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Takhmeen Details Card -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <i class="bi bi-calculator me-2"></i>Takhmeen Details
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <label for="niyaz_hub" class="col-sm-3 col-form-label fw-semibold">
                        <i class="bi bi-currency-rupee me-1"></i>Niyaz Hub
                    </label>
                    <div class="col-sm-9">
                        <input type="text" pattern="^[0-9]{1,9}$" required 
                            class="form-control" id="niyaz_hub" name="niyaz_hub"
                            value="<?= $takhmeen_data->niyaz_hub ?? 0 ?>">
                        <small class="text-muted">Enter the base niyaz hub amount</small>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="row mb-3">
                    <label for="iftar_count" class="col-sm-3 col-form-label fw-semibold">
                        <i class="bi bi-egg-fried me-1"></i>Iftar Count
                    </label>
                    <div class="col-sm-6">
                        <select class="form-select" name="iftar_count" id="iftar_count">
                            <?= getCountDropdown(0, 5, $takhmeen_data->iftar_count ?? 0) ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Rate: Rs. <?= $hub_data->iftar ?></small>
                        <small class="text-info fw-semibold" id="iftar_total">Total: Rs. 0</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <label for="zabihat_count" class="col-sm-3 col-form-label fw-semibold">
                        <i class="bi bi-droplet me-1"></i>Zabihat Count
                    </label>
                    <div class="col-sm-6">
                        <select class="form-select" name="zabihat_count" id="zabihat_count">
                            <?= getCountDropdown(0, 5, $takhmeen_data->zabihat_count ?? 0) ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Rate: Rs. <?= $hub_data->zabihat ?></small>
                        <small class="text-info fw-semibold" id="zabihat_total">Total: Rs. 0</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <label for="fateha_count" class="col-sm-3 col-form-label fw-semibold">
                        <i class="bi bi-book me-1"></i>Fateha Count
                    </label>
                    <div class="col-sm-6">
                        <select class="form-select" name="fateha_count" id="fateha_count">
                            <?= getCountDropdown(0, 5, $takhmeen_data->fateha_count ?? 0) ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Rate: Rs. <?= $hub_data->fateha ?></small>
                        <small class="text-info fw-semibold" id="fateha_total">Total: Rs. 0</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <label for="khajoor_count" class="col-sm-3 col-form-label fw-semibold">
                        <i class="bi bi-circle me-1"></i>Khajoor Count
                    </label>
                    <div class="col-sm-6">
                        <select class="form-select" name="khajoor_count" id="khajoor_count">
                            <?= getCountDropdown(0, 5, $takhmeen_data->khajoor_count ?? 0) ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Rate: Rs. <?= $hub_data->khajoor ?></small>
                        <small class="text-info fw-semibold" id="khajoor_total">Total: Rs. 0</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <label for="pirsa_count" class="col-sm-3 col-form-label fw-semibold">
                        <i class="bi bi-circle-fill me-1"></i>Pirsa Count
                    </label>
                    <div class="col-sm-6">
                        <select class="form-select" name="pirsa_count" id="pirsa_count">
                            <?= getCountDropdown(0, 5, $takhmeen_data->pirsa_count ?? 0) ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Rate: Rs. <?= $hub_data->pirsu ?></small>
                        <small class="text-info fw-semibold" id="pirsa_total">Total: Rs. 0</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <label for="chair_count" class="col-sm-3 col-form-label fw-semibold">
                        <i class="bi bi-chair me-1"></i>Chair Count
                    </label>
                    <div class="col-sm-6">
                        <select class="form-select" name="chair_count" id="chair_count">
                            <?= getCountDropdown(0, 5, $takhmeen_data->chair_count ?? 0) ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Rate: Rs. <?= $hub_data->chair ?></small>
                        <small class="text-info fw-semibold" id="chair_total">Total: Rs. 0</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Final Takhmeen Summary Card -->
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <i class="bi bi-cash-stack me-2"></i>Final Takhmeen Amount
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <label for="takhmeen" class="col-sm-3 col-form-label fw-bold fs-5">Total Amount</label>
                    <div class="col-sm-9">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-success text-white fw-bold">Rs.</span>
                            <input type="text" readonly required 
                                class="form-control form-control-lg fw-bold text-success" 
                                id="takhmeen" name="takhmeen" 
                                value="<?= $takhmeen_data->takhmeen ?? 0 ?>"
                                style="font-size: 1.5rem;">
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="bi bi-info-circle me-1"></i>This amount is calculated automatically based on your selections above.
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-save me-2"></i>Save Takhmeen
            </button>
            <?php if ($takhmeen_data->takhmeen > 0) { ?>
                <button type="button" id='paynow_link' class="btn btn-primary btn-lg" onclick="submitToCollection(<?= $hof_id ?>)">
                    <i class="bi bi-credit-card me-2"></i>Proceed to Payment
                </button>
            <?php } ?>
            <a href="<?= getAppData('BASE_URI') ?>/home" class="btn btn-outline-secondary btn-lg">
                <i class="bi bi-arrow-left me-2"></i>Back to Home
            </a>
        </div>
        
        <!-- Receipt History -->
        <?php if (is_array($receipt_data) && count($receipt_data) > 0) { ?>
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-receipt me-2"></i>Receipt History
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php __display_table_records([$receipt_data]) ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </form>
    
    <?php ui_card_end(); ?>
    <script>
        const rates = {
            iftar: <?= $hub_data->iftar ?>,
            zabihat: <?= $hub_data->zabihat ?>,
            fateha: <?= $hub_data->fateha ?>,
            khajoor: <?= $hub_data->khajoor ?>,
            pirsu: <?= $hub_data->pirsu ?>,
            chair: <?= $hub_data->chair ?>
        };
        
        function formatCurrency(amount) {
            return 'Rs. ' + amount.toLocaleString('en-IN');
        }
        
        function updateItemTotal(item, count, rate) {
            const total = count * rate;
            $('#' + item + '_total').text('Total: ' + formatCurrency(total));
        }
        
        function on_change_dropdown() {
            var niyaz_hub = Number($('#niyaz_hub').val()) || 0;
            var iftar_count = Number($('#iftar_count').val()) || 0;
            var zabihat_count = Number($('#zabihat_count').val()) || 0;
            var fateha_count = Number($('#fateha_count').val()) || 0;
            var khajoor_count = Number($('#khajoor_count').val()) || 0;
            var pirsa_count = Number($('#pirsa_count').val()) || 0;
            var chair_count = Number($('#chair_count').val()) || 0;
            
            var iftar_hub = iftar_count * rates.iftar;
            var zabihat_hub = zabihat_count * rates.zabihat;
            var fateha_hub = fateha_count * rates.fateha;
            var khajoor_hub = khajoor_count * rates.khajoor;
            var pirsa_hub = pirsa_count * rates.pirsu;
            var chair_hub = chair_count * rates.chair;

            var takhmeen = niyaz_hub + iftar_hub + zabihat_hub + fateha_hub + khajoor_hub + pirsa_hub + chair_hub;

            // Update individual totals
            updateItemTotal('iftar', iftar_count, rates.iftar);
            updateItemTotal('zabihat', zabihat_count, rates.zabihat);
            updateItemTotal('fateha', fateha_count, rates.fateha);
            updateItemTotal('khajoor', khajoor_count, rates.khajoor);
            updateItemTotal('pirsa', pirsa_count, rates.pirsu);
            updateItemTotal('chair', chair_count, rates.chair);
            
            // Update final takhmeen
            $('#takhmeen').val(takhmeen);
            
            // Show/hide Pay button based on takhmeen amount
            if (takhmeen > 0) {
                $('#paynow_link').show();
            } else {
                $('#paynow_link').hide();
            }
        }

        function submitToCollection(hof_id) {
            if (!confirm('Do you want to proceed to payment collection?')) {
                return;
            }
            
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= getAppData("BASE_URI") ?>/collection';
            
            var hofInput = document.createElement('input');
            hofInput.type = 'hidden';
            hofInput.name = 'hof_id';
            hofInput.value = hof_id;
            form.appendChild(hofInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        function the_script() {
            // Initialize totals on page load
            on_change_dropdown();
            
            // Update on any change
            $('#niyaz_hub, #iftar_count, #zabihat_count, #fateha_count, #khajoor_count, #pirsa_count, #chair_count').on("change paste keyup", function () {
                on_change_dropdown();
            });
            
            // Scroll to Pay button after save
            <?php if (isset($_GET['saved']) && $_GET['saved'] == '1') { ?>
                setTimeout(function() {
                    var payButton = document.getElementById('paynow_link');
                    if (payButton) {
                        payButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Highlight the button briefly
                        payButton.classList.add('pulse');
                        setTimeout(function() {
                            payButton.classList.remove('pulse');
                        }, 2000);
                    }
                }, 300);
            <?php } ?>
        }
    </script>
    <style>
        .pulse {
            animation: pulse 1s ease-in-out;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .card-header {
            font-weight: 600;
        }
        .form-select:focus, .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
    <?php
}

function __display_table_records($data)
{
    //case when sa.masalla is null then '' else sa.masalla end as masalla, 
// case when sa.attendance_type is null then 'Yes' else sa.attendance_type end as attendance_type,
// case when sa.chair_preference is null then 'No' else sa.chair_preference end as chair_preference,
// m.its_id,m.full_name,m.age,m.gender
    $records = $data[0];
    util_show_data_table($records, [
        '__show_row_sequence' => 'SN#',
        'id' => 'Receipt ID',
        'payment_mode' => 'Mode',
        'amount' => 'amount',
        '__print_link' => 'Print'
    ]);
}

function __print_link($row, $index) {
    $receipt_num = $row->id;
    $uri = getAppData('BASE_URI');
    return "<a target='receipt' class='btn btn-primary' href='$uri/receipt2/$receipt_num'>Print</a>";
}
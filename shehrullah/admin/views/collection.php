<?php
if_not_post_redirect('/home');

if (!is_user_a(SUPER_ADMIN, TAKHMEENER)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}

do_for_post('__handle_post');

function __handle_post()
{
    $action = $_POST['action'];
    $hof_id = $_POST['hof_id'];
    $hijri_year = get_current_hijri_year();
    $uri = getAppData('BASE_URI');
    if ($action === 'register') {

        $amount = $_POST['amount'];
        $payment_mode = $_POST['payment_mode'];
        $transaction_ref = $_POST['transaction_ref'];
        $remarks = $_POST['remarks'];
        
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
    $hof_id = $_POST['hof_id'];
    $hof_data = getAppData('hof_data');   
    ?>
    <form method="post">
        <input type="hidden" name="hof_id" value="<?=$hof_id?>">
        <input type="hidden" name="action" value="register">
        <div class='col-xs-12'>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">HOF Name</label>
                <div class="col-sm-9">
                    <input type="text" readonly class="form-control" 
                        value="<?= $hof_data->full_name ?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Takhmeen</label>
                <div class="col-sm-9">
                    <input type="text" readonly class="form-control" 
                        value="<?= $takhmeen_data->takhmeen ?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Paid</label>
                <div class="col-sm-9">
                    <input type="text" readonly class="form-control" 
                        value="<?= $takhmeen_data->paid_amount ?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Payable</label>
                <div class="col-sm-9">
                    <input type="text" pattern="^[0-9]{1,9}$" required class="form-control" id="amount" name="amount"
                        value="">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="gender" class="col-sm-3 col-form-label">Mode (online | cash)</label>
                <div class="col-sm-9">
                <select class="form-control form-control-lg" required name="payment_mode" id="payment_mode">
                        <option  value="">Select...</option>
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
            </div> 
            <div class="mb-3 row" id="transaction_ref_section">
                <label for="hof_id" class="col-sm-3 col-form-label">Reference No</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="transaction_ref" name="transaction_ref"
                        value="">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">Remarks</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="remarks" name="remarks">
                </div>
            </div>           
            <div class="form-group" style="font-weight:20px;margin-top: 25px;">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </div>        
    </form>
    <script>
        function the_script() {
            $('#transaction_ref_section').hide();
            $('#payment_mode').on('change', function() {
                var selectedValue = $(this).val();
                if( selectedValue == "online" || selectedValue == "cheque" ) {
                    $('#transaction_ref_section').show();
                    $("#transaction_ref").prop('required',true);
                } else {
                    $('#transaction_ref_section').hide();
                    $("#transaction_ref").prop('required',false);
                }
            }); 
        }
    </script>
    
    <?php
}
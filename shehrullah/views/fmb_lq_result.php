<?php
if_not_post_redirect('/input-sabeel');

function add_lq_niyat_for($hijri_year, $hof_id, $niyat)
{
    $query = 'UPDATE kl_fmb_data SET lq_niyat =?, updated=now() WHERE its_id=? and year=?;';
    $params = [$niyat, $hof_id, $hijri_year];
    $result = run_statement($query, $params);
    return $result->count > 0 ? null : $result->message;
}


do_for_post('_handle_form_submit');

function _handle_form_submit()
{
    $hof_id = $_POST['hof_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $niyat = $_POST['niyat'] ?? 0;


    $hijri_year = get_current_hijri_year();
    // setAppData('hijri_year', $hijri_year);
    // $hof_id = $_POST['hof_id'];
    // $hof_data = get_fmb_data_for($hof_id, $hijri_year);
    // if (is_null($hof_data) ) {
    //     do_redirect_with_message('/input-sabeel', 'Error: This ITS ['.$hof_id.'] is not a HOF or not belong in FMB data. Please contact Moiz Bhai Mulla (9096778753)');
    // }

    // setAppData('hof_id', $hof_id);
    // setAppData('hof_data', $hof_data);

    //if( $action === 'fmb_lq_niyat_input' ) {
    //$niyat = $_POST['niyat'] ?? 0;
    // $payment_mode = $_POST['payment_mode'];
    // $transaction_ref = $_POST['transaction_ref'];

    $result = add_lq_niyat_for($hijri_year, $hof_id, $niyat);
    // if( isset($result) ) {
    //     do_redirect_with_message('/input-sabeel', 'Error : Niyat amount not recorded. ' . $result);
    // } else {
    //     do_redirect_with_message('/input-sabeel', 'Success: Your FMB niyat is submitted.');            
    // }
    //}
}

function content_display()
{
    $hof_id = $_POST['hof_id'] ?? 0;
    $niyat = $_POST['niyat'] ?? 0;
    ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-2">FMB</h4>
            <h6 class="mb-3">Online transfer details.</h6>
            <div class="alert alert-success" role="alert">
                Shukran! Your niyat of Rs. <?= $niyat ?>/- is recorded. Please submit in cash, cheque OR online at below
                details.
            </div>
            <div class="alert alert-primary" role="alert">
                Cheque in favor of : <strong>D B J Trust K M Poona - FMB</strong>
            </div>
            <div class="alert alert-primary" role="alert">
                HDFC Bank A/C No <strong>50200068209839</strong> IFSC Code <strong>HDFC0000029</strong>
            </div>
            <div class="alert alert-primary" role="alert">
                UPI : <strong>dawoodibohrajamattru.62428145@hdfcbank</strong>
            </div>    
            <div class="alert alert-primary" role="alert">
                If the amount is transferred online please share a screen shot on the number <a href="https://wa.me/917499860950?text=<?=$hof_id?>">+917499860950</a> or Email:
                kalimimohallapoona@gmail.com
            </div>
            <img src="<?= getAppData('BASE_URI') ?>/_assets/images/only_qr.png" class="img-fluid" alt="Responsive image">
        </div>
        <div class="row">
            <div class="col-6">
                <a class="btn btn-light"
                        href="upi://pay?pa=dawoodibohrajamattru.62428145@hdfcbank&pn=KALIMI MOHALLAH FMB&cu=INR&amount=<?= $niyat ?>&am=<?= $niyat ?>&note=<?=$hof_id?>&tr=<?=$hof_id?>">Pay
                        Now (Mobile)</a>
            </div>
            <div class="col-6">
                <a href="<?= getAppData('BASE_URI') ?>/input-sabeel" class="btn btn-light">Back to search</a>
            </div>
        </div>
    </div>
    <?php
}
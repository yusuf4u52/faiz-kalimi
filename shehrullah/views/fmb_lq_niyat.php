<?php
if_not_post_redirect('/input-sabeel');

//initial_process();

function get_fmb_data_for($hof_id, $hijri_year) {
    $query = "SELECT * FROM kl_fmb_data WHERE its_id =? and year=?";
    $result = run_statement($query , $hof_id, $hijri_year);
    if( $result->count > 0 ) {
        return $result->data[0];
    }
    return null;
}



do_for_post('_handle_form_submit');

function _handle_form_submit() {    
    $hof_id = $_POST['hof_id'] ?? 0;
    $action = $_POST['action'] ?? '';


    $hijri_year = get_current_hijri_year();
    setAppData('hijri_year', $hijri_year);
    $hof_id = $_POST['hof_id'];
    $hof_data = get_fmb_data_for($hof_id, $hijri_year);
    if (is_null($hof_data) ) {
        do_redirect_with_message('/input-sabeel', 'Error: This ITS ['.$hof_id.'] is not a HOF or not belong in FMB data. Please contact Moiz Bhai Mulla (9096778753)');
    }

    setAppData('hof_id', $hof_id);
    setAppData('hof_data', $hof_data);

    if( $action === 'fmb_lq_niyat_input' ) {
        $niyat = $_POST['niyat'] ?? 0;
        // $payment_mode = $_POST['payment_mode'];
        // $transaction_ref = $_POST['transaction_ref'];

        $result = add_lq_niyat_for($hijri_year, $hof_id, $niyat);
        if( isset($result) ) {
            do_redirect_with_message('/input-sabeel', 'Error : Niyat amount not recorded. ' . $result);
        } else {
            do_redirect_with_message('/input-sabeel', 'Success: Your FMB niyat is submitted.');            
        }
    }
}

function content_display() {
    $uri = getAppData('BASE_URI');
    $hof_data = getAppData('hof_data');
    $hof_id = getAppData('hof_id');
    ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-2">FMB</h4>
            <h6 class="mb-3">Enter the niyat payment on Lailatul Qadr.</h6>
            <form action="fmb_lq_result" method="post">
            <input type="hidden" name="action" value="fmb_lq_niyat_input">
            <input type="hidden" name='hof_id' value="<?= $hof_id ?>">            
                <div class="row mb-3">
                    <label for="itsid" class="col-4 form-label">HOF ID</label>
                    <div class="col-8">                                                    
                        <input type='text' class='form-control' name='niyat' value="<?= $hof_id ?>" readonly />
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="itsid" class="col-4 form-label">Name</label>
                    <div class="col-8">                                                    
                        <input type='text' class='form-control' name='niyat' value="<?= $hof_data->name ?>" readonly />
                    </div>
                </div>
                <div class="row mb-3">
                    <label for="itsid" class="col-4 form-label">FMB Takhmeen (1446-1447)</label>
                    <div class="col-8">                                                    
                        <input type='text' class='form-control' name='niyat' value="<?= $hof_data->takhmeen ?>" readonly />
                    </div>
                </div>                  
                <div class="row mb-3">
                    <label for="roles" class="col-12 form-label">Lailat-ul-Qadr ma FMB ma je raqam si shamil thaso, ye enter kariye.</label>
                </div>
                <div class="row mb-3">
                    <label for="roles" class="col-4 form-label">Niyat for LQ</label>
                    <div class="col-8">
                        <input type='text' class='form-control' name='niyat' value="<?=$hof_data->lq_niyat??''?>" required />
                    </div>
                </div>                
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-light">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php
}

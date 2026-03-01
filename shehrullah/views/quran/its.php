<?php

do_for_post('_handle_form_submission');

function content_display()
{
    ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-2">Quran Khtam Data Entry</h2>
            <p class="mb-3"><small>Please enter ITS ID to proceed</small></p>
            <form method="post" action="quran.tilawat" class="forms-sample">
                <input type="hidden" name="action" value="sabeel_search"/>
                <div class="row mb-3">
                    <label class="col-4 form-label">ITS ID (Numbers only)</label>
                    <div class="col-8">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="ITS ID" pattern="^[0-9]{8}$"
                                id="its_id" name="its_id" aria-label="ITS number" aria-describedby="button-addon2" required>
                            <button class="btn btn-light" type="submit" name='click' value='search' id="button-addon2">Search</button>
                        </div>
                    </div>
                </div>
            </form>            
        </div>
    </div>
    <?php
}

function _handle_form_submission()
{
    $action = $_POST['action'] ?? '';
    if( function_exists($action) ) {
        $action();
    } else {
        //do_redirect('/vajebaat');
    }
    
}

function sabeel_search() {
    $itsid = $_POST['its_id'];
    $hof_data = get_hof_data($itsid);
    if (is_null($hof_data)) {
        do_redirect_with_message('/quran.its', "This ITS ID is not found");
    }

    $hijri_year = get_current_hijri_year();
    setAppData('hijri_year', $hijri_year);

    $enc_itsid = do_encrypt($itsid);
    do_redirect('/quran.its/' . $enc_itsid);
}

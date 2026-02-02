<?php

if_not_post_redirect('/home');
if (!is_user_a(SUPER_ADMIN, RECEPTION, DATA_ENTRY)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}

do_for_post('_handle_post');

function _handle_post()
{
    $sabeel = $_POST['sabeel'];
    setAppData('sabeel', $sabeel);

    $sabeel_data = get_thaalilist_data($sabeel);
    if (is_null($sabeel_data)) {
        do_redirect_with_message('\home', "Sabeel number ($sabeel) not found. Get the sabeel added to thaali data.");
    }
    setAppData('sabeel_data', $sabeel_data);

    $action = $_POST['action'] ?? '';
    if( function_exists($action) ) {
        $action();
    }
    // if( $action === 'doChangeClearance' ) {
    //     doChangeClearance();
    // }
}

function doSearch() {
    $sabeel = getAppData('sabeel');
    $sabeel_data = getAppData('sabeel_data');
    $hof_id = $sabeel_data->ITS_No;
    $data = getClearanceData($hof_id);
    $notes = '';
    $clearance = '';
    if( isset($data) ) {
        $notes = $data->notes;
        $clearance = $data->clearance;
    }
    setAppData('notes', $notes);
    setAppData('clearance', $clearance);
}

function doChangeClearance() {
    $sabeel = getAppData('sabeel');
    $sabeel_data = getAppData('sabeel_data');
    $hof_id = $sabeel_data->ITS_No;
    $name = $sabeel_data->NAME;
    $notes = $_POST['notes'];
    $clearance = $_POST['clearance'];

    $udata = getSessionData(THE_SESSION_ID);
    $userid = $udata->itsid;

    $result = addClearanceData($sabeel,$hof_id, $clearance, $notes, $userid);

    if( $result ) {
        do_redirect_with_message('/home', 'Clearance record updated.');
    } else {
        setAppData('notes', $notes);
        setAppData('clearance', $clearance);
        setSessionData(TRANSIT_DATA, 'Something went wrong. Try again.');
    }
}

function content_display()
{
    $sabeel = getAppData('sabeel');
    $sabeel_data = getAppData('sabeel_data');
    $hof_id = $sabeel_data->ITS_No;
    $name = $sabeel_data->NAME;
    $notes = getAppData('notes');
    $clearance = getAppData('clearance');
    ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-3">Sabeel Clearance</h2>
            <form method="post">
                <input type="hidden" value="doChangeClearance" name="action" id="action" />
                <input type="hidden" value="<?= $hof_id ?>" name="hof_id" id="hof_id">
                <input type="hidden" name="sabeel" value="<?= $sabeel ?>">
                <div class="mb-3 row">
                    <label for="staticEmail" class="col-4 form-label">HOF</label>
                    <div class="col-sm-8">
                        <input type="text" readonly class="form-control" id="staticEmail"
                            value="<?= "$hof_id - $name" ?>">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="clearance" class="col-4 form-label">Clearance (Y or N)</label>
                    <div class="col-sm-8">
                        <input type="text" pattern="^(?:y|Y|n|N)$" required class="form-control" id="clearance" name="clearance"
                            value="<?= $clearance ?? '' ?>">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="clearance" class="col-4 form-label">Notes</label>
                    <div class="col-sm-8">
                        <textarea class="form-control" name="notes" id="notes"><?= $notes ?? '' ?></textarea>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="submit" class="btn btn-light">Save</button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

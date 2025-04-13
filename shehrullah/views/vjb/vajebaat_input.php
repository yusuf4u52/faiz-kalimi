<?php
/*
Logic;
Get the HOF ID from URI
Find out if the HOF has already done the booking?
If no, redirect to slot booking page
If yes, continue to vajebaat input page

Once he submits the vajebaat input
save and show the vajebaat page.

508
254
254
*/


initial_process();

function initial_process() {
    $hof_id_encrypted = getAppData('arg1');
    $hof_id = do_decrypt($hof_id_encrypted);
    setAppData('hof_id', $hof_id);


    $hof_data = get_its_record_for($hof_id);

    // $hof_data = get_itsdata_for($hof_id);
    if (is_null($hof_data)) {
        do_redirect_with_message('/vajebaat' , 'No records found for input ' . $hof_id . '. Enter correct HOF ITS.');
    }

    setAppData('hof_data', $hof_data);

    $hijri_year = get_current_hijri_year();
    setAppData('hijri_year', $hijri_year);

}

// do_for_post('_handle_form_submit');

// function _handle_form_submit() {
//     $data = $_POST;
//     $data['token'] = $_POST['token'];
//     $data['jamaat'] = $_POST['jamaat'];
//     $data['itsid'] = $_POST['itsid'];
//     $data['name'] = $_POST['name'];
//     $data['mobile'] = $_POST['mobile'];
//     $data['address'] = $_POST['address'];
//     $data['last_vajebaat'] = $_POST['last_vajebaat2'];
//     $data['form_mardo_count'] = $_POST['form_mardo_count'];
//     $data['form_bairo_count'] = $_POST['form_bairo_count'];
//     $data['form_kids_count'] = $_POST['form_kids_count'];
//     $data['form_amwat_count'] = $_POST['form_amwat_count'];
//     $data['form_hamal_count'] = $_POST['form_hamal_count'];

//     $result = add_vajebaat($data);
//     if (is_null($result)) {
//         do_redirect_with_message('/vjb.vajebaat_input/' . do_encrypt($data['itsid']), 'Your Vajebaat is booked successfully');
//     } else {
//         setSessionData(TRANSIT_DATA, $result);
//     }
// }


function content_display() {
    $uri = getAppData('BASE_URI');
    $hof_data = getAppData('hof_data');
    $hof_id = getAppData('hof_id');
    $family_stats = family_stats_for($hof_id);
    ?>

<section class='content'>
        <div class='container-fluid'>
            <!-- <form action='<?= $uri ?>/vjb.find_vjb' method='post'>
                <input type='hidden' name='itsid' value='' />
                <input type="submit" class="btn btn-warning" value="Back">
            </form> -->
            <form id="contact-form" method="post" action="<?= $uri ?>/vjb.vjb_print" role="form">
                <!--  INPUT FORM START -->
                <div class='card'>
                    <div class="card-header">
                        <h4>Vajebaat Form Input</h4>
                        <h6>for sila fitra calculation</h6>
                    </div>
                    <div class="card-body controls">

                        <div class="row">
                            <div class="col-md-12">
                                <input type="hidden" name='token' value="<?php echo $_GET['token'] ?? 'ERROR'; ?>">
                                <input type="hidden" name='jamaat' value="<?= $personInfo->Jamaat ?? '' ?>">
                                <input type="hidden" name='itsid' value="<?= $hof_data->ITS_ID ?? '0' ?>">
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="form_name">ITS ID *</label> <input readonly type="text" name="itsid" id="itsid"
                                        class="form-control" placeholder="Please enter your ITS ID *" required="required"
                                        data-error="Firstname is required." value='<?= $hof_data->ITS_ID ?? '' ?>'>
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="form_lastname">Full name *</label> <input id="name" type="text" name="name"
                                        class="form-control" placeholder="Please enter your full name *" required="required"
                                        data-error="Lastname is required." value='<?= $hof_data->Full_Name ?? '' ?>'>
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="form_email">Mobile *</label> <input id="mobile" type="text" name="mobile"
                                        class="form-control" placeholder="Please enter your Mobile *" required="required"
                                        data-error="Valid email is required." value='<?= $hof_data->Mobile ?? '' ?>'>
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type='hidden' value='<?= $personInfo->sfvjb ?? '' ?>' name='last_vajebaat' /> <label
                                        for="form_email">Last Vajebaat *</label>
                                    <input id="vajebaat" type="text" name="last_vajebaat2" class="form-control"
                                        value='<?= $personInfo->sfvjb ?? '0' ?>'>
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="form_email">Address *</label>
                                    <textarea id='address' name='address' class="form-control" placeholder="Address *"
                                        required="required"
                                        data-error="Valid email is required."><?= $hof_data->Address ?? '' ?></textarea>
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="form_email">Gents Count *</label> <input id="form_mardo_count" type="number"
                                        name="form_mardo_count" class="form-control"
                                        placeholder="Please enter mardo count *" required="required"
                                        data-error="Valid email is required." value='<?= $family_stats->mardo ?? 0 ?>'>
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="form_email">Ladies Count *</label> <input id="form_bairo_count"
                                        type="number" name="form_bairo_count" class="form-control"
                                        placeholder="Please enter mardo count *" required="required"
                                        data-error="Valid email is required." value='<?= $family_stats->bairao ?? 0 ?>'>
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="form_email">Kids *</label> <input id="form_kids_count" type="number"
                                        name="form_kids_count" class="form-control" placeholder="Please enter mardo count *"
                                        required="required" data-error="Valid email is required."
                                        value='<?= $family_stats->kids ?? 0 ?>'>
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="form_email">Marhoom Count *</label> <input id="form_amwat_count" value="0"
                                        type="number" name="form_amwat_count" class="form-control"
                                        placeholder="Please enter mardo count *" required="required"
                                        data-error="Valid email is required.">
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="form_email">Hamal Count *</label> <input id="form_hamal_count" value="0"
                                        type="number" name="form_hamal_count" class="form-control"
                                        placeholder="Please enter mardo count *" required="required"
                                        data-error="Valid email is required.">
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
                            <!--
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="form_email">Token *</label> <input id="token" value="0" type="number"
                                        name="token" class="form-control" placeholder="Please enter mardo count *"
                                        required="required" data-error="Valid email is required.">
                                    <div class="help-block with-errors"></div>
                                </div>
                            </div>
-->
                        </div>
                        <br />
                        <div class="row">
                            <div class="col-md-12">
                                <input type="submit" class="btn btn-success btn-send" value="Register">
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.card-body -->
            </form>
        </div>
        <!-- /.card -->
    </section>

    <?php
}

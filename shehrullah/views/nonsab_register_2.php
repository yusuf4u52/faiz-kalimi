<?php
$enc_hof_id = getAppData('arg1');
$hof_id = do_decrypt($enc_hof_id); 
//|| !is_numeric($hof_id)
if( is_null($hof_id) || !is_numeric($hof_id) ) {
    do_redirect_with_message('/input-sabeel' , 'Invalid request. Try again..');
}

setAppData('hof_id', $hof_id);

do_for_post('_handle_form_submission');

function content_display() {
    $enc_hof_id = getAppData('arg1');

    $hof_id = getAppData('hof_id');
    $uri = getAppData('BASE_URI');
    ?>
    <div class="row">
        <div class="col-8">
            <h6>Add Member</h6>
        </div>
    </div>
    <form method="post">
        <input type="hidden" value="register" name="action" id="action" />
        <input type="hidden" value="<?= $hof_id ?>" name="hof_id" id="hof_id">

        <div class='col-xs-12'>
            <div class="mb-3 row">
                <label for="staticEmail" class="col-sm-3 col-form-label">HOF ID</label>
                <div class="col-sm-9">
                    <?php if($hof_id == 0) { ?>
                        <input type="text" readonly class="form-control-plaintext" value="Please enter HOF details.">
                    <?php } else { ?>
                        <input type="text" readonly class="form-control-plaintext" value="<?= $hof_id ?>">
                    <?php } ?>
                    
                </div>
            </div>
            <div class="mb-3 row">
                <label for="its_id" class="col-sm-3 col-form-label">ITS ID</label>
                <div class="col-sm-9">
                    <?php 
                    $action = $_POST['action']??'';
                    if( $action === 'hof' ) {                    
                    ?>
                    <input type="hidden"  id="its_id" name="its_id" value="<?= $_POST['its_id'] ?? '' ?>">
                        <input type="text" readonly class="form-control-plaintext" value="<?= $hof_id ?>">
                    <?php } else { ?>
                        <input type="text" pattern="^[0-9]{8}$" required 
                        class="form-control" id="its_id" name="its_id" value="<?= $_POST['its_id'] ?? '' ?>">
                    <?php } ?>                    
                </div>
            </div>
            <div class="mb-3 row">
                <label for="full_name" class="col-sm-3 col-form-label">Full name</label>
                <div class="col-sm-9">
                    <input type="text" required class="form-control" 
                    id="full_name" name="full_name"  value="<?= $_POST['full_name'] ?? '' ?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="gender" class="col-sm-3 col-form-label">Gender (M or F)</label>
                <div class="col-sm-9">
                    <input type="text" pattern="^(?:m|M|f|F)$" required 
                    class="form-control" id="gender" name="gender" value="<?= $_POST['gender'] ?? '' ?>">                    
                </div>
            </div>
            <div class="mb-3 row">
                <label for="age" class="col-sm-3 col-form-label">Age</label>
                <div class="col-sm-9">
                    <input type="text" pattern="^[0-9]{1,2}$" required 
                    class="form-control" id="age" name="age" value="<?= $_POST['age'] ?? '' ?>">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="misaq" class="col-sm-3 col-form-label">Misaq (Y or N)</label>
                <div class="col-sm-9">
                <input type="text" pattern="^(?:y|Y|n|N)$" required 
                class="form-control" id="misaq" name="misaq" value="<?= $_POST['misaq'] ?? '' ?>">
                    <!-- <select class="form-control" name="misaq">
                        <option value="Done">Done</option>
                        <option value="Not Done">Not Done</option>
                    </select> -->
                </div>
            </div>
            <div class="form-group" style="font-weight:20px;margin-top: 25px;">
                <button type="submit" class="btn btn-success">Save</button>
                <a href='<?=$uri . '/nonsab_register/' . $enc_hof_id?>' class="btn btn-warning">Cancel</a>
            </div>
        </div>
    </form>
    <?php
}

function _handle_form_submission() {
    $enc_hof_id = getAppData('arg1');
    $action = $_POST['action'];

    if( $action === 'register' ) {
        $hof_id = $_POST['hof_id'];
        $its_id = $_POST['its_id'];
        $full_name = $_POST['full_name'];
        $age = $_POST['age'];
        $gender_post = $_POST['gender'];
        $misaq_post = $_POST['misaq'];
    
    
        if( $hof_id == 0 ) {
            $hof_id = $its_id;
        }
    
        $gender = 'Male';
        if( strtolower($gender_post)  === 'f' ) {
            $gender = 'Female';
        }
        $misaq = 'Done';
        if( strtolower($misaq_post)  === 'n' ) {
            $misaq = 'Not Done';
        }
    
        //first check if HOF already exist in ITS_DATA
        $hof_data = get_hof_data($its_id);
        if (is_null($hof_data)) {
            add_family_member($hof_id, '', $its_id, $full_name, $gender, $age, $misaq);
            do_redirect_with_message('/nonsab_register/' . $enc_hof_id , 'Member added successfully. Add more or submit');
        } else {
            if( $hof_id === $hof_data->hof_id) {
                setSessionData(TRANSIT_DATA, "ITS ID ($its_id) already exist in your family tree.");
            } else {
                setSessionData(TRANSIT_DATA, "Oops! This ITS ID ($its_id) belongs to different family HOF ($hof_data->hof_id).");
            }        
        }
    } else {
        setSessionData(TRANSIT_DATA, "Add HOF data");
    }
}
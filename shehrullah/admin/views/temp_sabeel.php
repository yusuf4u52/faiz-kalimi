<?php

do_for_post('__handle_post');

function __handle_post() {
    $hof_id = $_POST['hof_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $whatsapp = $_POST['whatsapp'];
    $address = $_POST['address'];
    //$gender = $_POST['gender'];
    $age = $_POST['age'];

    $gender_post = $_POST['gender'];
    $misaq_post = $_POST['misaq'];

    $gender = 'Male';
    if( strtolower($gender_post)  === 'f' ) {
        $gender = 'Female';
    }
    $misaq = 'Done';
    if( strtolower($misaq_post)  === 'n' ) {
        $misaq = 'Not Done';
    }

    $thaali_data =  get_thaalilist_data($hof_id);
    if( is_null($thaali_data) ) {
        $its_data = get_itsdata_for($hof_id);
        if( is_null($its_data) ) {
            $result = add_family_member($hof_id, 0, $hof_id, $full_name, $gender, $age, $misaq);
            if( !$result->success ) {
                //setSessionData(TRANSIT_DATA, $result->message);
                do_redirect_with_message('/home', $result->message);
            } 
        } else if( $its_data->its_id != $its_data->hof_id ){
            do_redirect_with_message('/home', "ITS ID ($hof_id) already exist under different HOF($its_data->hof_id) ");
        }
                
        $thali_num = add_thalilist($full_name,$mobile,$hof_id, $email, 
        $address, $whatsapp);

        if($thali_num > 0 ) {
            do_redirect_with_message('/home' , "Thali number $thali_num created for $hof_id - $full_name");
        }
        
    } else {
        $sabeel = $thaali_data->Thali;
        setSessionData('\home', "Sabeel (KL-$sabeel) already exist for this HOF ($hof_id).");
    }

    // $is_hof_in_its = is_hof_in_its($hof_id);

    // if ($is_hof_in_sabeel) {
    //     setSessionData('\home', 'HOF already in sabeel');
    // }


}

function content_display() {
    ?>
    <form method="post">
        <div class='col-xs-12'>
            <div class="mb-3 row">
                <label for="hof_id" class="col-sm-3 col-form-label">HOF ID</label>                
                <div class="col-sm-9">
                    <input type="text" pattern="^[0-9]{8}$" required class="form-control" id="hof_id" name="hof_id">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="full_name" class="col-sm-3 col-form-label">Full name</label>
                <div class="col-sm-9">
                    <input type="text" required class="form-control" id="full_name" name="full_name">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="email" class="col-sm-3 col-form-label">Email Adrress (Gmail Only)</label>
                <div class="col-sm-9">
                    <input type="email" required class="form-control" id="email" name="email">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="mobile" class="col-sm-3 col-form-label">Mobile</label>
                <div class="col-sm-9">
                    <input type="email" required class="form-control" id="mobile" name="mobile">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="whatsapp" class="col-sm-3 col-form-label">Whatsapp</label>
                <div class="col-sm-9">
                    <input type="email" required class="form-control" id="whatsapp" name="whatsapp">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="age" class="col-sm-3 col-form-label">Address</label>
                <div class="col-sm-9">
                    <textarea class="form-control" id="address" name="address" required></textarea>
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
                    <input type="text" pattern="^[0-9]{1,2}$" required class="form-control" id="age" name="age">
                </div>
            </div>
            <div class="mb-3 row">
                <label for="misaq" class="col-sm-3 col-form-label">Misaq (Y or N)</label>
                <div class="col-sm-9">
                <input type="text" pattern="^(?:y|Y|n|N)$" required 
                class="form-control" id="misaq" name="misaq" value="<?= $_POST['misaq'] ?? '' ?>">
                </div>
            </div>
            <div class="form-group" style="font-weight:20px;margin-top: 25px;">
                <button type="submit" class="btn btn-success">Save</button>
            </div>
        </div>
    </form>
    <?php
}
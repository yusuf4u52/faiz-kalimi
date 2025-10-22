<?php

function content_display()
{
    $uri = getAppData('BASE_URI');
    $user_session_data = getSessionData(THE_SESSION_ID);
    $its_id = 0;
    if( isset($user_session_data) && is_array($user_session_data) ) {
        $its_id = array_key_exists('ITSID' , $user_session_data) ? $user_session_data['ITSID'] : 0;
    }

    ?>
    <div class="row">
        <div class="col-12 col-md-6">
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Miqaats</h5>
                    <p class="card-text">List of current & future miqaats</p>
                    <a href="<?=$uri?>/miqaats" class="btn btn-light">Go >></a>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Roti Miqaats</h5>
                    <p class="card-text">List of current & future roti miqaats</p>
                    <a href="<?=$uri?>/roti_miqaats" class="btn btn-light">Go >></a>
                </div>
            </div>
        </div>
        <?php if( $its_id == 30359589 ) { ?>
        <div class="col-12 col-md-6">
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">ITS RECORDs</h5>
                    <p class="card-text">Only these columns allowed</p>
                    <p class="card-text">ITS id,HOF id,Sabeel no,Full name,Age,Gender,Misaq,Address,Sector,Subsector,mohallah</p>
                    <form action="<?=$uri?>/its_upload" method="post" enctype="multipart/form-data">
                        <div class="input-group mb-3">
                            <input type='hidden' name='action' value="file"/>    
                            <input type='file' class='form-control file-upload-browse' name='itsdatafile' required />
                            <button class="btn btn-outline-primary" type="submit" id="button-addon2">GO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
<?php } ?>

<?php

function content_display()
{
    $uri = getAppData('BASE_URI');
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
    </div>
<?php } ?>

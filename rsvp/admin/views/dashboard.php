<?php

function content_display()
{
    $uri = getAppData('BASE_URI');
    ?>
    <div class="row">
        <div class="col-md-3 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Miqaats</h5>
                    <p class="card-text">List of current & future miqaats</p>
                    <a href="<?=$uri?>/miqaats" class="btn btn-warning">Go >></a>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">ROTI Miqaats</h5>
                    <p class="card-text">List of current & future miqaats</p>
                    <a href="<?=$uri?>/roti_miqaats" class="btn btn-warning">Go >></a>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

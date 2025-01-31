<?php

function content_display()
{
    $super_admin = is_super_admin();
    $data_entry = is_data_entry();

    $hijri_year = get_current_hijri_year();
    $url = getAppData('BASE_URI');
    ?>
    <div class="row">
        <?php
        //superadmin, reception and data entry 
        if (is_user_a(ROLE->SA, ROLE->RC, ROLE->DE)) { ?>
            <div class="col-md-4 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Printing</h5>
                        <p class="card-text">Shehrullah Niyaz Form Print</p>
                        <form action="print-form-for" method="post">
                            <input type="hidden" name="action" value="SEARCH">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="HOF ID" name="hof_id" id="hof_id"
                                    aria-label="HOF ID" aria-describedby="button-addon2" pattern="^[0-9]{8}$" required>
                                <button class="btn btn-outline-primary" type="submit" id="button-addon2">GO</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php }
        //super admin and takhmeen
        if (is_user_a(ROLE->SA, ROLE->TK)) { ?>
            <div class="col-md-4 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Takhmeen</h5> 
                        <p class="card-text">Takhmeen Entry</p>
                        <form action="takhmeen" method="post">
                            <input type="hidden" name="action" value="SEARCH">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="HOF ID" name="hof_id" id="hof_id"
                                    aria-label="HOF ID" aria-describedby="button-addon2" pattern="^[0-9]{8}$" required>
                                <button class="btn btn-outline-primary" type="submit" id="button-addon2">GO</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php }
        //superadmin
        if (is_user_a(ROLE->SA)) { ?>
            <div class="col-md-4 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Hijri</h5>
                        <p class="card-text">Hijri Years / Add new / Set Markaz</p>
                        <a href="<?= $url ?>/years" class="btn btn-primary">GO >></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Users</h5>
                        <p class="card-text">User management</p>
                        <a href="<?= $url ?>/users" class="btn btn-primary">GO >></a>
                    </div>
                </div>
            </div>
        <?php } ?>

    </div>
    <!-- End of row -->
    <?php
}


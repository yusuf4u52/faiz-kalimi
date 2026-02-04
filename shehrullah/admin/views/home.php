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
        if (is_user_a(SUPER_ADMIN, RECEPTION, DATA_ENTRY)) { ?>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Printing</h5>
                        <p class="card-text">Shehrullah Niyaz Form Print</p>
                        <form action="print-form-for" method="post">
                            <input type="hidden" name="action" value="SEARCH">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Sabeel" name="sabeel" id="sabeel"
                                    aria-label="HOF ID" aria-describedby="button-addon2" pattern="^[0-9]{1,8}$" required>
                                <button class="btn btn-light" type="submit" id="button-addon2">GO</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Add Member</h5>
                        <p class="card-text">Add mehman or member</p>
                        <form action="member_list" method="post">
                            <input type="hidden" name="action" value="SEARCH">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Sabeel" name="sabeel" id="sabeel"
                                    aria-label="Sabeel" aria-describedby="button-addon2" pattern="^[0-9]{1,8}$" required>
                                <button class="btn btn-light" type="submit" id="button-addon2">GO</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Clearance</h5>
                        <p class="card-text">Shehrullah clearance</p>
                        <form action="sabeel_clearance" method="post">
                            <input type="hidden" name="action" value="doSearch">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="Sabeel" name="sabeel" id="sabeel"
                                    aria-label="Sabeel" aria-describedby="button-addon2" pattern="^[0-9]{1,8}$" required>
                                <button class="btn btn-light" type="submit" id="button-addon2">GO</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Temp Sabeel</h5>
                        <p class="card-text">Module to create temp sabeel</p>
                        <a href="<?= $url ?>/temp_sabeel" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div> -->
        <?php }
        //super admin and takhmeen
        if (is_user_a(SUPER_ADMIN, TAKHMEENER)) { ?>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Takhmeen</h5>
                        <p class="card-text">Takhmeen Entry (Enter HOF ID)</p>
                        <form action="takhmeen" method="post">
                            <input type="hidden" name="action" value="SEARCH">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="HOF ID" name="hof_id" id="hof_id"
                                    aria-label="HOF ID" aria-describedby="button-addon2" pattern="^[0-9]{8,8}$" required>
                                <button class="btn btn-light" type="submit" id="button-addon2">GO</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Collection</h5>
                        <p class="card-text">collection</p>
                        <form action="collection" method="post">
                            <input type="hidden" name="action" value="SEARCH">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" placeholder="HOF ID" name="hof_id" id="hof_id"
                                    aria-label="HOF ID" aria-describedby="button-addon2" pattern="^[0-9]{8,8}$" required>
                                <button class="btn btn-light" type="submit" id="button-addon2">GO</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div> 
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Receipts</h5>
                        <p class="card-text">Report - List of receipts</p>
                        <a href="<?= $url ?>/receipt_list" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div>
        <?php }
        //superadmin
        if (is_user_a(SUPER_ADMIN)) { ?>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">FMB - Lailatul Qadr Niyat</h5>
                        <p class="card-text">List of niyat online.</p>
                        <a href="<?= $url ?>/report/fmb_lq_niyat" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Report</h5>
                        <p class="card-text">All Registrations</p>
                        <a href="<?= $url ?>/registrations" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Report</h5>
                        <p class="card-text">All Registered Users</p>
                        <a href="<?= $url ?>/report/registered_users" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Shehrullah Year</h5>
                        <p class="card-text">Shehrullah Hub Details</p>
                        <a href="<?= $url ?>/years" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Users</h5>
                        <p class="card-text">User management</p>
                        <a href="<?= $url ?>/users" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Vajebaat</h5>
                        <p class="card-text">Baithak Slots</p>
                        <a href="<?= $url ?>/vjb.slot" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Vajebaat</h5>
                        <p class="card-text">Pending Mumineen List</p>
                        <a href="<?= $url ?>/report/vjb_pending" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">ITS DATA</h5>
                        <p class="card-text">Synchronize the ITS data</p>
                        <form action="its_upload" method="post" enctype="multipart/form-data">
                            <div class="input-group mb-3">
                                <input type='file' class='form-control file-upload-browse' name='itsdatafile' required />
                                <button class="btn btn-light" type="submit" id="button-addon2">GO</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">ITS RECORDs</h5>
                        <p class="card-text">DUMP OF ITS DATA</p>
                        <form action="full_its_record_upload" method="post" enctype="multipart/form-data">
                            <div class="input-group mb-3">
                                <input type='file' class='form-control file-upload-browse' name='itsdatafile' required />
                                <button class="btn btn-light" type="submit" id="button-addon2">GO</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="d-flex col-md-4 col-sm-6 col-12">
                <div class="card mb-4 w-100">
                    <div class="card-body">
                        <h5 class="card-title">Seat Management</h5>
                        <p class="card-text">Manage seat allocations</p>
                        <a href="<?= $url ?>/seat-management" class="btn btn-light">Go <i class="bi bi-chevron-double-right"></i></a>
                    </div>
                </div>
            </div>
        <?php } ?>

    </div>
    <!-- End of row -->
    <?php
}


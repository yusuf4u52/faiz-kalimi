<?php
$enc_hof_id = getAppData('arg1');
$hof_id = do_decrypt($enc_hof_id); 
if( is_null($hof_id) || !is_numeric($hof_id) ) {
    do_redirect_with_message('/input-sabeel' , 'Invalid request. Try again...');
}

setAppData('hof_id', $hof_id);

if( $hof_id == 0 ) {
    do_redirect_with_message('/input-sabeel', 'Oops! unexpected flow.');
}
$hijri_year = get_current_hijri_year();

$mumineen_data = get_attendees_data_for_nonsab($hof_id, $hijri_year, true);

setAppData('mumineen_data', $mumineen_data);

do_for_post('_handle_form_submission');

function content_display()
{
    $mumineen_data = getAppData('mumineen_data');
    $hof_id = getAppData('hof_id');
    $uri = getAppData('BASE_URI');
    ?>
    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-header">
                    <h4>Shukran! Your data is submitted. You may get the form print during Takhmeen.</h4>
                </div>
                <div class="card-body">
                    <form class="forms-sample" action="" method="POST">
                        <div class="form-group row">
                            <?php __display_family_list([$mumineen_data]) ?>
                        </div>                        
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function __display_family_list($data)
{
    $records = $data[0];
    util_show_data_table($records, [
        'its_id' => 'ITS ID',
        'full_name' => 'Name',
        'chair_preference'=>'Chair'
    ]);
}


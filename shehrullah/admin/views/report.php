<?php

function content_display() {
    $arg = getAppData('arg1');
    if( function_exists($arg) ) {
        $arg();
    } else {
        echo 'No report found..';
    }
}

function vjb_registration() {
    if( !(is_user_role(SUPER_ADMIN)) ) {
        do_redirect_with_message('/home', 'You are not authorized to view this page');
    }

    $slot_id = getAppData('arg2');

    $query = 'SELECT 
    i.ITS_ID, i.Full_Name, s.title
    FROM kl_shehrullah_vjb_allocation a 
    JOIN kl_shehrullah_vjb_slots s ON s.id = a.slot_id
    JOIN ITS_RECORD i ON i.ITS_ID  = a.hof_id
    WHERE a.slot_id = ? and a.hijri_year = ?
    ';

    $hijri_year = get_current_hijri_year();

    $result = run_statement($query, $slot_id, $hijri_year);
    $records = $result->data;
    util_show_data_table($records, [
        '__show_row_sequence' => 'Sr#',        
        'ITS_ID' => 'ITS ID',
        'Full_Name' => 'Name',
        'title' => 'Slot'
    ]);
}



function vjb_pending() {

    function __whatsapp($row, $index) {
        $whatsapp = $row->WhatsApp_No;
        return "<a href='https://wa.me/$whatsapp' target='_blank'>$whatsapp</a>";
    }
    // if( !(is_user_role(SUPER_ADMIN)) ) {
    //     do_redirect_with_message('/home', 'You are not authorized to view this page');
    // }

    // $slot_id = getAppData('arg2');

    $query = 'SELECT * FROM ITS_RECORD
    WHERE ITS_ID NOT IN (SELECT hof_id FROM kl_shehrullah_vjb_allocation WHERE hijri_year = ?)
    AND ITS_ID = HOF_ID';

    $hijri_year = get_current_hijri_year();

    $result = run_statement($query, $hijri_year);
    $records = $result->data;
    util_show_data_table($records, [
        '__show_row_sequence' => 'Sr#',        
        'ITS_ID' => 'ITS ID',
        'Full_Name' => 'Name',
        'Mobile' => 'Mobile',
        '__whatsapp'=>'Whatsapp',

    ]);

    
}


function fmb_lq_niyat() {
    $hijri_year = get_current_hijri_year();
    $query = 'SELECT * from kl_fmb_data WHERE updated > "2025-03-17" and year=?';

    $result = run_statement($query, $hijri_year);
    $records = $result->data;
    // util_show_data_table($records, [
    //     '__show_row_sequence' => 'Sr#',        
    //     'its_id' => 'ITS ID',
    //     'name' => 'Name',
    //     'takhmeen'=>'takhmeen',
    //     'lq_niyat' => 'LQ Niyat'
    // ]);

    $query = 'SELECT sum(takhmeen) takhmeen_sum,sum(lq_niyat) niyat_sum FROM kl_fmb_data WHERE updated > "2025-03-17" and year=?';

    $result = run_statement($query, $hijri_year);
    $summary_data = $result->data[0];


    $cols = [
        '__show_row_sequence' => 'Sr#',        
        'its_id' => 'ITS ID',
        'name' => 'Name',
        'takhmeen'=>'takhmeen',
        'lq_niyat' => 'LQ Niyat'
    ];

    $colKeys = [];
    $colLabels = [];
    foreach ($cols as $key => $value) {
        $colLabels[] = $value;
        $colKeys[] = $key;
    }

    ?>
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <?= util_table_header_row($colLabels) ?>
            </thead>
            <tbody>
                <?php util_table_data_rows($records, $colKeys);
                if( !is_null($summary_data) ) {
                ?>
                <tr>
                    <td colspan="3">TOTAL ==></td>
                    <td><?=$summary_data->takhmeen_sum?></td>
                    <td><?=$summary_data->niyat_sum?></td>                   
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}

function registered_users() {
    if( !(is_user_role(SUPER_ADMIN)) ) {
        do_redirect_with_message('/home', 'You are not authorized to view this page');
    }

    function __chair_preference($row, $index) {
        $chair_preference = $row->chair_preference ?? 'N';
        return $chair_preference == 'Y' ? 'Yes' : 'No';
    }

    $hijri_year = get_current_hijri_year();
    
    $query = 'SELECT DISTINCT i.its_id, i.full_name, i.age, i.gender, i.misaq, a.chair_preference
              FROM its_data i
              INNER JOIN kl_shehrullah_attendees a ON i.its_id = a.its_id
              WHERE a.year = ? AND a.attendance_type = "Y"
              ORDER BY i.its_id ASC';

    $result = run_statement($query, $hijri_year);
    $records = $result->data;
    
    $uri = getAppData('BASE_URI');
    ?>
    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-header row">
                    <div class="col-12">Registered Users Report (Shehrullah Form Filled)</div>                    
                </div>
                <div class="card-body">
                    <p><a href="<?=$uri?>/report.registered_users_download" class="btn btn-gradient-primary btn-rounded btn-fw">Download Excel</a></p>
                    <?php util_show_data_table($records, [
                        '__show_row_sequence' => 'Sr#',        
                        'its_id' => 'ITS ID',
                        'full_name' => 'Name',
                        'gender' => 'Gender',
                        'age' => 'Age',
                        'misaq' => 'Misaq',
                        '__chair_preference' => 'Chair'
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}



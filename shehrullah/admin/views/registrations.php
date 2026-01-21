<?php
if( !is_super_admin() ) {
    do_redirect_with_message('/home', 'Unauthorized.');
}

//Report of registered mumineen..
function content_display()
{
    $hirji_year = get_current_hijri_year();
    $registration_data = get_registration_data($hirji_year);
    $registration_summary = get_registration_summary($hirji_year);
    ?>
    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-header row">
                    <div class="col-12">Registration</div>
                </div>
                <div class="card-body">                    
                    <?php __display_table($registration_data, $registration_summary);?>                    
                </div>
            </div>
        </div>
    </div>
    <?php
}

function __display_table($records, $registration_summary)
{
    $cols = [
        '__show_row_sequence' => 'Sr#',
        'hof_id' => 'HOF ID',
        'full_name' => 'Name',
        'whatsapp' => 'WhatsApp',
        'male' => 'Male (>11)',
        'female' => 'Female (>11)',
        'kids' => 'Kids (5~11)',
        'infant' => 'infant (<5)',
        'chairs' => 'Chairs',
        'pirsa_count' => 'Pirsa',
        'zabihat_count' => 'Zabihat',
        'takhmeen' => 'Takhmeen',
        'paid_amount' => 'Paid'
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
                if( !is_null($registration_summary) ) {
                ?>
                <tr>
                    <td colspan="4">TOTAL ==></td>
                    <td><?=$registration_summary->males?></td>
                    <td><?=$registration_summary->females?></td>
                    <td><?=$registration_summary->kids?></td>
                    <td><?=$registration_summary->infants?></td>
                    <td><?=$registration_summary->chairs?></td>
                    <td><?=$registration_summary->pirsas?></td>
                    <td><?=$registration_summary->zabihat?></td>
                    <td><?=$registration_summary->takhmeen?></td>
                    <td><?=$registration_summary->paid?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php



    // $records = $data[0];
    // util_show_data_table($records, [
    //     '__show_row_sequence' => 'Sr#',
    //     'hof_id' => 'HOF ID',
    //     'full_name' => 'Name',
    //     'male' => 'Male (>11)',
    //     'female' => 'Female (>11)',
    //     'kids' => 'Kids (5~11)',
    //     'infant' => 'infant (<5)',
    //     'chairs' => 'Chairs',
    //     'pirsa_count' => 'Pirsa',
    //     'takhmeen' => 'Takhmeen',
    //     'paid_amount' => 'Paid'
    // ]);
}


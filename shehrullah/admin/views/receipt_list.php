<?php
if (!is_user_a(SUPER_ADMIN, TAKHMEENER)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}
function content_display()
{
    $hijri_year = get_current_hijri_year();
    $receipt_data = get_all_receipt_data_for($hijri_year);
    ?>
    <p>Receipt History</p>
    <?php __display_table_records([$receipt_data]) ?>
    <?php
}

function __display_table_records($data)
{
    //case when sa.masalla is null then '' else sa.masalla end as masalla, 
// case when sa.attendance_type is null then 'Yes' else sa.attendance_type end as attendance_type,
// case when sa.chair_preference is null then 'No' else sa.chair_preference end as chair_preference,
// m.its_id,m.full_name,m.age,m.gender
    $records = $data[0];
    util_show_data_table($records, [
        '__show_row_sequence' => 'SN#',
        'id' => 'Receipt ID',
        'payment_mode' => 'Mode',
        'hof_id' => 'HOF',
        'amount' => 'amount',
        'createdby' => 'Created By',
        '__print_link' => 'Print'
    ]);
}

function __print_link($row, $index) {
    $receipt_num = $row->id;
    $uri = getAppData('BASE_URI');
    return "<a target='receipt' class='btn btn-primary' href='$uri/receipt2/$receipt_num'>Print</a>";
}
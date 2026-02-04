<?php
if (!is_user_a(SUPER_ADMIN, TAKHMEENER)) {
    do_redirect_with_message('/home', 'Redirected as tried to access unauthorized area.');
}
function content_display()
{
    $hijri_year = get_current_hijri_year();
    
    // Get date filter parameters
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    
    // Get filtered receipt data
    $receipt_data = get_filtered_receipt_data($hijri_year, $from_date, $to_date);
    ?>
    <div class="card">
        <div class="card-body">
            <h2 class="mb-3">Receipt History</h2>
            
            <!-- Date Filter Form -->
            <form method="get" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="from_date" class="form-label fw-semibold small">
                            <i class="bi bi-calendar-event me-1"></i>From Date
                        </label>
                        <input type="date" class="form-control form-control-sm" 
                               id="from_date" name="from_date" 
                               value="<?= htmlspecialchars($from_date) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="to_date" class="form-label fw-semibold small">
                            <i class="bi bi-calendar-event me-1"></i>To Date
                        </label>
                        <input type="date" class="form-control form-control-sm" 
                               id="to_date" name="to_date" 
                               value="<?= htmlspecialchars($to_date) ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-light btn-sm me-2">
                            <i class="bi bi-funnel me-1"></i>Filter
                        </button>
                        <a href="<?= getAppData('BASE_URI') ?>/receipt_list" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-x-circle me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
            
            <?php if ($from_date || $to_date): ?>
                <div class="alert alert-info alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Filter Applied:</strong> 
                    <?php if ($from_date && $to_date): ?>
                        Showing receipts from <strong><?= htmlspecialchars($from_date) ?></strong> 
                        to <strong><?= htmlspecialchars($to_date) ?></strong>
                    <?php elseif ($from_date): ?>
                        Showing receipts from <strong><?= htmlspecialchars($from_date) ?></strong> onwards
                    <?php elseif ($to_date): ?>
                        Showing receipts up to <strong><?= htmlspecialchars($to_date) ?></strong>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php __display_table_records([$receipt_data]) ?>
        </div>
    </div>
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
        '__created_date' => 'Created Date',
        '__created_by_name' => 'Created By',
        '__print_link' => 'Print'
    ]);
}

function __created_date($row, $index) {
    if (empty($row->created)) {
        return '-';
    }
    // Convert the datetime to dd/mm/yyyy format
    $date = new DateTime($row->created);
    return $date->format('d/m/Y');
}

function __created_by_name($row, $index) {
    if (empty($row->createdby)) {
        return '-';
    }
    $user = get_user_record_for($row->createdby);
    return $user ? $user->name : $row->createdby;
}

function __print_link($row, $index) {
    $receipt_num = $row->id;
    $uri = getAppData('BASE_URI');
    return "<a target='receipt' class='btn btn-light' href='$uri/receipt2/$receipt_num'><i class='bi bi-printer'></i></a>";
}
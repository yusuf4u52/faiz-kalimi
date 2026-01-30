<?php

DEFINE('NO_TEMPLATE', true);

if( !(is_user_role(SUPER_ADMIN)) ) {
    do_redirect_with_message('/home', 'You are not authorized to view this page');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="registered_users_' . date('Y-m-d') . '.csv";');

$hijri_year = get_current_hijri_year();

$query = 'SELECT DISTINCT i.its_id, i.full_name, i.age, i.gender
          FROM its_data i
          INNER JOIN kl_shehrullah_attendees a ON i.its_id = a.its_id
          WHERE a.year = ?
          ORDER BY i.its_id ASC';

$result = run_statement($query, $hijri_year);
$report_data = $result->data;

// Output CSV header with BOM for Excel UTF-8 support
echo "\xEF\xBB\xBF";
echo "S/No,ITS ID,Name,Gender,Age" . PHP_EOL;

$sno = 0;
foreach($report_data as $row) {
    $sno++;
    $its_id = $row->its_id ?? '';
    $full_name = $row->full_name ?? '';
    $gender = $row->gender ?? '';
    $age = $row->age ?? '';
    
    echo "$sno,$its_id,$full_name,$gender,$age" . PHP_EOL;
}

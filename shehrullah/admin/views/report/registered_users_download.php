<?php

DEFINE('NO_TEMPLATE', true);

if( !(is_user_role(SUPER_ADMIN)) ) {
    do_redirect_with_message('/home', 'You are not authorized to view this page');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="registered_users_' . date('Y-m-d') . '.csv";');

$query = 'SELECT its_id, full_name, age 
          FROM its_data 
          ORDER BY its_id ASC';

$result = run_statement($query);
$report_data = $result->data;

// Output CSV header with BOM for Excel UTF-8 support
echo "\xEF\xBB\xBF";
echo "S/No,ITS ID,Name,Age" . PHP_EOL;

$sno = 0;
foreach($report_data as $row) {
    $sno++;
    $its_id = $row->its_id ?? '';
    $full_name = $row->full_name ?? '';
    $age = $row->age ?? '';
    
    echo "$sno,$its_id,$full_name,$age" . PHP_EOL;
}

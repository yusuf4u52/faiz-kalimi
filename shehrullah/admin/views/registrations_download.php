<?php

DEFINE('NO_TEMPLATE', true);

if( !is_super_admin() ) {
    do_redirect_with_message('/home', 'Unauthorized.');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="registrations_' . date('Y-m-d') . '.csv";');

$hirji_year = get_current_hijri_year();
$registration_data = get_registration_data($hirji_year);
$registration_summary = get_registration_summary($hirji_year);

// Calculate pending amount for each record
foreach ($registration_data as &$record) {
    $record->pending = $record->takhmeen - $record->paid_amount;
}
unset($record); // Break the reference

// Output CSV header with BOM for Excel UTF-8 support
echo "\xEF\xBB\xBF";
echo "S/No,HOF ID,Name,WhatsApp,Male (>11),Female (>11),Kids (5~11),Infant (<5),Chairs,Pirsa,Zabihat,Takhmeen,Paid,Pending" . PHP_EOL;

$sno = 0;
foreach($registration_data as $record) {
    $sno++;
    $hof_id = $record->hof_id ?? '';
    $full_name = $record->full_name ?? '';
    $whatsapp = $record->whatsapp ?? '';
    $male = $record->male ?? 0;
    $female = $record->female ?? 0;
    $kids = $record->kids ?? 0;
    $infant = $record->infant ?? 0;
    $chairs = $record->chairs ?? 0;
    $pirsa_count = $record->pirsa_count ?? 0;
    $zabihat_count = $record->zabihat_count ?? 0;
    $takhmeen = $record->takhmeen ?? 0;
    $paid_amount = $record->paid_amount ?? 0;
    $pending = $record->pending ?? 0;
    
    echo "$sno,$hof_id,$full_name,$whatsapp,$male,$female,$kids,$infant,$chairs,$pirsa_count,$zabihat_count,$takhmeen,$paid_amount,$pending" . PHP_EOL;
}

// Add summary row if available
if (!is_null($registration_summary)) {
    echo "TOTAL ==>,,,";
    echo ($registration_summary->males ?? 0) . ",";
    echo ($registration_summary->females ?? 0) . ",";
    echo ($registration_summary->kids ?? 0) . ",";
    echo ($registration_summary->infants ?? 0) . ",";
    echo ($registration_summary->chairs ?? 0) . ",";
    echo ($registration_summary->pirsas ?? 0) . ",";
    echo ($registration_summary->zabihat ?? 0) . ",";
    echo ($registration_summary->takhmeen ?? 0) . ",";
    echo ($registration_summary->paid ?? 0) . ",";
    echo (($registration_summary->takhmeen ?? 0) - ($registration_summary->paid ?? 0)) . PHP_EOL;
}

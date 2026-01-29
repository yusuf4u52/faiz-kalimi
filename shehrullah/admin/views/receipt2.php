<?php
DEFINE('NO_TEMPLATE', '');

$id = getAppData('arg1');
if (is_null($id)) {
    do_redirect_with_message('/home', 'Invalid receipt ID');
}

$hijri_year = get_current_hijri_year();
$receipt_data = get_collection_record($id, $hijri_year);

if (is_null($receipt_data)) {
    do_redirect_with_message('/home', 'Invalid receipt ID');
}

$receipt_record = $receipt_data;
$payment_mode = $receipt_record->payment_mode;
$receipt_id = $receipt_record->id;

$hofid = $receipt_record->hof_id;
$name = $receipt_record->full_name;
$full_name = $hofid . ' - ' . $name;
$paid = $receipt_record->paid_amount;
$receipt_amount = $receipt_record->amount;
$takhmeen = $receipt_record->takhmeen;
$date_created = $receipt_record->created;
$date_cr = date_create($date_created);
$date_format = date_format($date_cr, "j/F/Y");
$pending = $takhmeen - $paid;
$transaction_ref = $receipt_record->transaction_ref;

// Include the HTML template
include __DIR__ . '/receipt2.html';
?>
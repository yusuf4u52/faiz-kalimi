<?php
DEFINE('NO_TEMPLATE', '');

$id = getAppData('arg1');
if (is_null($id)) {
    do_redirect_with_message('/home', 'Invalid receipt ID');
}

$id = getAppData('arg1');
$hijri_year = get_current_hijri_year();
$receipt_data = get_collection_record($id, $hijri_year);

if (is_null($receipt_data)) {
    do_redirect_with_message('/home', 'Invalid receipt ID');
}

$receipt_record = $receipt_data;
$payment_mode = $receipt_record->payment_mode;
$receipt_id = substr(strtoupper($payment_mode), 0, 1)
    . "-" . $receipt_record->id;

$hofid = $receipt_record->hof_id;
$name = $receipt_record->full_name;
$full_name = $hofid . ' - ' . $name;
$paid = $receipt_record->paid_amount;
$receipt_amount = $receipt_record->amount . '/-';
$takhmeen = $receipt_record->takhmeen;
$date_created = $receipt_record->created;
$date_cr = date_create($date_created);
$date_format = date_format($date_cr, "j/F/Y");
$pending = $takhmeen - $paid;
$transaction_ref = $receipt_record->transaction_ref;

$font = '../fpdf/font/ARLRDBD.TTF';

$img = imagecreatefromjpeg('../_assets/images/Receipt.jpg');
$white = imagecolorallocate($img, 0, 0, 100);

do_data($img, $white, $font, $receipt_id, 
$date_format,$hijri_year, $full_name, $receipt_amount);
do_data($img, $white, $font, 
$receipt_id, $date_format,$hijri_year, $full_name, $receipt_amount, 700);
function do_data($img, $white, $font, $receipt_id, $date_format,$hijri_year, $full_name, $receipt_amount, $more = 0)
{
    $fontSize = 14;
    $x = 160;
    $y = 240 + $more;

    //Print Receipt Number
//Add name
    imagettftext($img, $fontSize, 0, $x, $y, $white, $font, ' Receipt No : ' . $receipt_id);

    $x = 900;
    //Print Date
    imagettftext($img, $fontSize, 0, $x, $y, $white, $font, ' Date : ' . $date_format);


    $fontSize = 26;
    $x = 660;
    $y = 290 + $more;
    // year
    imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $hijri_year);

    $fontSize = 14;
    $x = 160;
    $y = 440 + $more;
    //Add name
    imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $full_name);

    $x = 620;
    $y = 490 + $more;
    //Add amount
    imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $receipt_amount);

}

//-------

imagejpeg($img, '1.jpg', 100);

include_once '../fpdf/fpdf.php';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->Image('1.jpg', 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
$pdf->Output();
unlink("1.jpg");

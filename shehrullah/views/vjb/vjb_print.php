<?php
if_not_post_redirect('/input-sabeel');
// do_redirect_with_message('/input-sabeel', 'Session expired..');

$show=false;

function breakString($str , $breakPoint) {
//     $oStr = [''];
//     $oStrIndex = 0;
//     $pieces = explode(" ", $str);
//     foreach( $pieces as $p ) {
//         if( strlen( $oStr[$oStrIndex] ) + strlen($p) > $breakPoint ) {
//             $oStrIndex++;
//         }
//         $oStr[$oStrIndex] .= $p . ' ';
//     }
    
    return $str;
}

// function updateRecord($itsid) {
//     $connect = mysqli_connect('localhost', 'olivezrt_rnd', '@livezrt_rnd', 'olivezrt_rnd');
//     if ($connect === false) {
//         //throw new DBConnectionFailure("Database connection error.");
//     } else {
//         mysqli_query($connect, "UPDATE Pune_Mufaddal_Mumineen_Database_06Apr21 SET vajebaat_done = 1, vajebaat_form_time = now() WHERE ITS_ID = $itsid");
//         mysqli_close($connect);
//     }
// }

$data = (object)$_POST;
// $token = $rd->token_id;
// $token_data = $rd->token_data;
// $decoded_string = mb_convert_encoding($token_data, 'ISO-8859-1', 'UTF-8');
// $data = json_decode($decoded_string);

$sno = '';

//$sno = $data->token;
// $sno = $token;
$itsid = $data->itsid;
$name = $data->name;
$address = $data->address;
$address2 = '';
$mobile = $data->mobile;
$jamaat = $data->jamaat ?? 'Mufaddal Mohalla, Poona';


$vajebaat = $data->last_vajebaat2;
//$fmb = $data->last_fmb;


$mardoCount = $data->form_mardo_count;
$bairaoCount = $data->form_bairo_count;
$kidsCount = $data->form_kids_count;
$hamalCount = $data->form_hamal_count;
$amwatCount = $data->form_amwat_count;

update_vjb_form_data($itsid, $mardoCount, $bairaoCount, $kidsCount, $amwatCount, $hamalCount);

$sila = 254;

$mardoValue = $mardoCount * $sila * 2;
$bairaoValue = $bairaoCount * $sila * 2;
$kidsValue = $kidsCount * $sila ;
$hamalValue = $hamalCount * $sila;
$amwatValue = $amwatCount * $sila;

$totalSF = $mardoValue + $bairaoValue + $kidsValue + $hamalValue + $amwatValue;

$font = __DIR__ . '/../../fpdf/font/ARLRDBD.TTF';
//$font = __DIR__ . '/../fpdf/font/ARLRDBD.TTF';
$fontSize = 18;


/* Vajebaat form */
$img = imagecreatefromjpeg('views/vjb/BlankVajebaatImage.jpg');
//$img = imagecreatefromjpeg('views/vjb/BlankVajebaatImage.jpg');
$white = imagecolorallocate($img, 0, 0, 100);

$fontSize = 30;

$x = 1300;//1325
$y = 130;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font,' Form No. ' . $sno);
$fontSize = 18;

$x = 1325;
$y = 155;
imagettftext($img, $fontSize, 0, $x, $y, $white, $font,' Date. ' . date('d-M-Y'));


$x = 1100;
$y = 410;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $jamaat);

$fontSize = 22;

$x = 1100;
$y = 860;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $mardoCount);

$x -= 400;
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $mardoValue);

$x += 400;
$y += 90;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $bairaoCount);

$x -= 400;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $bairaoValue);

$x += 400;
$y += 80;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $kidsCount);

$x -= 400;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $kidsValue);

$x += 400;
$y += 80;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $hamalCount);

$x -= 400;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $hamalValue);

$x += 400;
$y += 80;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $amwatCount);

$x -= 400;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $amwatValue);


$y += 80;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $totalSF);



$fontSize = 20;

$x = 150;//140
$y = 1920;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $name);

$x += 103;//100
$y += 55;//50
//Add ITS ID
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $itsid);
$x += 480;//370
//Add contact
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $mobile);
$x -= 370;
$y += 50;


// $addLines = $address;//breakString($address, 70);
// if( count($addLines) > 1) {
//     $address = $addLines[0];
//     $address2 = $addLines[1];
// } else {
//     $address = $addLines[0];
//     $address2 = '';
// }
$address2 = '';
$x -= 100;
//Add address
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $address);
//$x -= 370;
$y += 30;
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $address2);


$y = 2082;
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, 'Last Year: ' . $vajebaat);




//imagejpeg($img, '/home2/olivezrt/public_html/mm53.in/admin/views/output/1.jpg', 100);
imagejpeg($img, '1.jpg', 100);

/*
$fontSize = 14;

$img = imagecreatefromjpeg('fmb.jpg');

$x = 470;
$y = 190;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $name);

//$fontSize = 18;
$x += 100;
$y += 95;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $itsid);

$x += 20;
$y += 75;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $mobile);

$x -= 500;
//$y += 75;
//Add name
imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $fmb);


imagejpeg($img, 'output/2.jpg', 100);
*/

/* FMB form */



//require("./../../libs/fpdf.php");
include_once __DIR__ . '/../../fpdf/fpdf.php';
// include_once '/home2/olivezrt/public_html/mm53.in/fpdf/fpdf.php';

// usage:
$pdf = new FPDF();
$pdf->AddPage();
//$pdf->Image('output/1.jpg', 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
$pdf->Image('1.jpg', 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
/*
$pdf->AddPage();
$pdf->Image('output/2.jpg', 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
*/

$pdf->Output();


unlink("1.jpg");
//unlink("output/2.jpg");
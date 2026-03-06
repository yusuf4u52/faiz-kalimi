<?php

if (!function_exists('vjb_print_to_int')) {
    function vjb_print_to_int($value)
    {
        return (int)($value ?? 0);
    }
}

if (!function_exists('vjb_print_display_number')) {
    function vjb_print_display_number($value)
    {
        $intValue = (int)$value;
        return $intValue === 0 ? '' : (string)$intValue;
    }
}

if (!function_exists('vjb_print_build_payload')) {
    function vjb_print_build_payload(array $data)
    {
        // Get current Hijri year
        $hijri_year = get_current_hijri_year();
        
        return [
            'hijri_year' => $hijri_year,
            'itsid' => trim((string)($data['itsid'] ?? '')),
            'name' => trim((string)($data['name'] ?? '')),
            'mobile' => trim((string)($data['mobile'] ?? '')),
            'address' => trim((string)($data['address'] ?? '')),
            'jamaat' => trim((string)($data['jamaat'] ?? 'Kalimi Mohalla, Poona')),
            'last_vajebaat2' => trim((string)($data['last_vajebaat2'] ?? '')),
            'form_mardo_count' => vjb_print_to_int($data['form_mardo_count'] ?? 0),
            'form_bairo_count' => vjb_print_to_int($data['form_bairo_count'] ?? 0),
            'form_kids_count' => vjb_print_to_int($data['form_kids_count'] ?? 0),
            'form_hamal_count' => vjb_print_to_int($data['form_hamal_count'] ?? 0),
            'form_amwat_count' => vjb_print_to_int($data['form_amwat_count'] ?? 0),
        ];
    }
}

if (!function_exists('vjb_print_render_image_resource')) {
    function vjb_print_render_image_resource(array $payload)
    {
        if (!function_exists('imagecreatefromjpeg') || !function_exists('imagejpeg')) {
            throw new RuntimeException('PHP GD extension is not available.');
        }
        if (!function_exists('imagettftext')) {
            throw new RuntimeException('PHP FreeType support (imagettftext) is not available.');
        }

        $imgPath = __DIR__ . '/BlankVajebaatImage.jpg';
        $fontPath = __DIR__ . '/../../fpdf/font/ARLRDBD.TTF';

        if (!is_file($imgPath)) {
            throw new RuntimeException('Blank vajebaat image not found.');
        }
        if (!is_file($fontPath)) {
            throw new RuntimeException('Arabic font not found.');
        }

        $img = imagecreatefromjpeg($imgPath);
        if (!$img) {
            throw new RuntimeException('Unable to load vajebaat base image.');
        }

        $ink = imagecolorallocate($img, 0, 0, 100);
        $sila = 254;

        $mardoCount = $payload['form_mardo_count'];
        $bairaoCount = $payload['form_bairo_count'];
        $kidsCount = $payload['form_kids_count'];
        $hamalCount = $payload['form_hamal_count'];
        $amwatCount = $payload['form_amwat_count'];

        $mardoValue = $mardoCount * $sila * 2;
        $bairaoValue = $bairaoCount * $sila * 2;
        $kidsValue = $kidsCount * $sila;
        $hamalValue = $hamalCount * $sila;
        $amwatValue = $amwatCount * $sila;
        $totalSF = $mardoValue + $bairaoValue + $kidsValue + $hamalValue + $amwatValue;

        $fontSize = 18;
        imagettftext($img, $fontSize, 0, 280, 280, $ink, $fontPath, $payload['hijri_year']);
        imagettftext($img, $fontSize, 0, 1100, 410, $ink, $fontPath, $payload['jamaat']);

        $fontSize = 22;
        $x = 1100;
        $y = 860;
        imagettftext($img, $fontSize, 0, $x, $y, $ink, $fontPath, vjb_print_display_number($mardoCount));
        imagettftext($img, $fontSize, 0, $x - 400, $y, $ink, $fontPath, vjb_print_display_number($mardoValue));

        $y += 90;
        imagettftext($img, $fontSize, 0, $x, $y, $ink, $fontPath, vjb_print_display_number($bairaoCount));
        imagettftext($img, $fontSize, 0, $x - 400, $y, $ink, $fontPath, vjb_print_display_number($bairaoValue));

        $y += 80;
        imagettftext($img, $fontSize, 0, $x, $y, $ink, $fontPath, vjb_print_display_number($kidsCount));
        imagettftext($img, $fontSize, 0, $x - 400, $y, $ink, $fontPath, vjb_print_display_number($kidsValue));

        $y += 80;
        imagettftext($img, $fontSize, 0, $x, $y, $ink, $fontPath, vjb_print_display_number($hamalCount));
        imagettftext($img, $fontSize, 0, $x - 400, $y, $ink, $fontPath, vjb_print_display_number($hamalValue));

        $y += 80;
        imagettftext($img, $fontSize, 0, $x, $y, $ink, $fontPath, vjb_print_display_number($amwatCount));
        imagettftext($img, $fontSize, 0, $x - 400, $y, $ink, $fontPath, vjb_print_display_number($amwatValue));

        $y += 80;
        imagettftext($img, $fontSize, 0, $x - 400, $y, $ink, $fontPath, vjb_print_display_number($totalSF));

        $fontSize = 20;
        $x = 150;
        $y = 1920;
        imagettftext($img, $fontSize, 0, $x, $y, $ink, $fontPath, $payload['name']);
        imagettftext($img, $fontSize, 0, $x + 103, $y + 55, $ink, $fontPath, $payload['itsid']);
        imagettftext($img, $fontSize, 0, $x + 583, $y + 55, $ink, $fontPath, $payload['mobile']);
        imagettftext($img, $fontSize, 0, $x + 3, $y + 105, $ink, $fontPath, $payload['address']);
        imagettftext($img, $fontSize, 0, $x + 3, 2082, $ink, $fontPath, 'Last Year: ' . $payload['last_vajebaat2']);

        return $img;
    }
}

if (!function_exists('vjb_generate_form_jpeg')) {
    function vjb_generate_form_jpeg(array $data)
    {
        $payload = vjb_print_build_payload($data);
        $img = vjb_print_render_image_resource($payload);

        ob_start();
        imagejpeg($img, null, 100);
        $jpeg = ob_get_clean();
        imagedestroy($img);

        return is_string($jpeg) ? $jpeg : '';
    }
}

if (!function_exists('vjb_generate_form_jpeg_base64')) {
    function vjb_generate_form_jpeg_base64(array $data)
    {
        $jpeg = vjb_generate_form_jpeg($data);
        return $jpeg !== '' ? base64_encode($jpeg) : '';
    }
}

if (!function_exists('vjb_output_pdf_from_jpeg')) {
    function vjb_output_pdf_from_jpeg($jpeg)
    {
        if ($jpeg === '') {
            throw new RuntimeException('Unable to create vajebaat form image.');
        }

        include_once __DIR__ . '/../../fpdf/fpdf.php';

        $tmpImage = tempnam(sys_get_temp_dir(), 'vjb_');
        if ($tmpImage === false) {
            throw new RuntimeException('Unable to allocate temporary file for PDF rendering.');
        }

        file_put_contents($tmpImage, $jpeg);

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->Image($tmpImage, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        $pdf->Output();

        @unlink($tmpImage);
    }
}

if (!defined('VJB_PRINT_LIB_ONLY')) {
    if_not_post_redirect('/input-sabeel');

    $data = vjb_print_build_payload((array)$_POST);
    update_vjb_form_data(
        $data['itsid'],
        $data['form_mardo_count'],
        $data['form_bairo_count'],
        $data['form_kids_count'],
        $data['form_amwat_count'],
        $data['form_hamal_count']
    );

    $jpeg = vjb_generate_form_jpeg($data);
    vjb_output_pdf_from_jpeg($jpeg);
}

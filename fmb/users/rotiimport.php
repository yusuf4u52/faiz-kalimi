<?php
include('connection.php');
include('_authCheck.php');
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['import']) && isset($_FILES['roti_import'])) {
    $filePath = $_FILES['roti_import']['tmp_name'];

    // Load the XLSX file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    $dateHeaders = array_slice($rows[0], 1); // Get date headers from the first row
    // Skip header row and loop through the data
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        $name = trim($row[0]);
        for( $j = 1; $j < count($row); $j++) {
            $roti = is_numeric($row[$j]) ? intval($row[$j]) : 0;
            if($roti > 0 && !empty($dateHeaders[$j-1])) {
                $dateRaw = trim($dateHeaders[$j-1]);
                $date = date('Y-m-d', strtotime($dateRaw));
                $roti_maker = mysqli_query($link, "SELECT `id` FROM fmb_roti_maker WHERE `code` = '" . $name . "' LIMIT 1");
                if ($roti_maker->num_rows > 0) {
                    $maker = mysqli_fetch_assoc($roti_maker);
                }
                $arecieved = mysqli_query($link, "SELECT * FROM fmb_roti_recieved WHERE `recieved_date` = '".$date."' AND `maker_id` = '" . $maker['id'] . "' order by `recieved_date` DESC LIMIT 1") or die(mysqli_error($link));
                if ($arecieved->num_rows > 0) {
                    $row_arecieved = $arecieved->fetch_assoc();
                    $sql = "UPDATE  fmb_roti_recieved SET `roti_recieved` = '" . $roti . "', `roti_status` = 'pending', `recieved_by` = '" . $_POST['recieved_by'] . "' WHERE `id` = '".$row_arecieved['id']."'";
                    mysqli_query($link,$sql) or die(mysqli_error($link));
                } else {
                    $sql = "INSERT INTO  fmb_roti_recieved (`maker_id`, `recieved_date`, `roti_recieved`, `roti_status`, `recieved_by`) VALUES ('" . $maker['id'] . "', '" . $date . "', '" . $roti . "', 'pending', '" . $_POST['recieved_by'] . "')";
                    mysqli_query($link,$sql) or die(mysqli_error($link));
                }
            }
        }
        
    }
    header("Location: /fmb/users/rotirecieved.php?action=upload");
} else {
    echo "No file uploaded.";
}
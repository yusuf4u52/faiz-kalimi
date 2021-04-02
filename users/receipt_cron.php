<?php
include('connection.php');

date_default_timezone_set('Asia/Kolkata');
$counter = 1;
$file = fopen("fmbreceipts.csv", "r");
while (($column = fgetcsv($file)) !== FALSE) {

    if ($counter != 1) {
        $receiptno = "";
        if (isset($column[0])) {
            $receiptno = mysqli_real_escape_string($link, $column[0]);
        }
        $thalino = "";
        if (isset($column[1])) {
            $thalino = mysqli_real_escape_string($link, $column[1]);
        }
        $amount = "";
        if (isset($column[4])) {
            $amount = mysqli_real_escape_string($link, $column[4]);
            $amount = str_replace('₹', '', $amount);
            $amount = str_replace(',', '', $amount);
            $amount = intval($amount);
        }
        $date = "";
        if (isset($column[3])) {
            $datefromcsv = mysqli_real_escape_string($link, $column[3]);
            $dateObject = date_create($datefromcsv);
            $datestring = date_format($dateObject, "Y-m-d");
        }
        $receivedby = "";
        if (isset($column[10])) {
            $receivedby = mysqli_real_escape_string($link, $column[10]);
        }

        $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
        $result = mysqli_query($link, $sql) or die(mysqli_error($link));
        $name = mysqli_fetch_assoc($result);

        $sqlInsert = "replace into receipts (`Receipt_No`,`Thali_No`,`userid`, `name`, `Date`,`Amount`,`received_by`)
                   values ('$receiptno', '$thalino', '" . $name['id'] . "', '" . $name['NAME'] . "', '$datestring', '$amount', '$receivedby')";
        mysqli_query($link, $sqlInsert) or die(mysqli_error($link));
        
    }
    $counter++;
}
echo "Success\n";
?>

<?php
include('connection.php');
include('getHijriDate.php');

$months = array(
    '09' => 'Ramazan',
    '10' => 'Shawwal',
    '11' => 'Zilqad',
    '12' => 'Zilhaj',
    '01' => 'Moharram',
    '02' => 'Safar',
    '03' => 'RabiulAwwal',
    '04' => 'RabiulAkhar',
    '05' => 'JamadalAwwal',
    '06' => 'JamadalAkhar',
    '07' => 'Rajab',
    '08' => 'Shaban'
);

$today = getTodayDateHijri();

// reset paid to 0 before updating receipts
$sql = "UPDATE thalilist set Paid = 0";
mysqli_query($link, $sql) or die(mysqli_error($link));
mysqli_query($link, "truncate receipts") or die(mysqli_error($link));

date_default_timezone_set('Asia/Kolkata');
$counter = 1;
$file = fopen("fmbreceipts.csv", "r");
while (($column = fgetcsv($file)) !== FALSE) {
    if ($column[13] != "Cancelled") {
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
                $datestring = getHijriDate($datefromcsv);
            }
            $receivedby = "";
            if (isset($column[10])) {
                $receivedby = mysqli_real_escape_string($link, $column[10]);
            }

            $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
            $result = mysqli_query($link, $sql) or die(mysqli_error($link));
            $name = mysqli_fetch_assoc($result);

            $sqlInsert = "insert into receipts (`Receipt_No`,`Thali_No`,`userid`, `name`, `Date`,`Amount`,`received_by`)
                   values ('$receiptno', '$thalino', '" . $name['id'] . "', '" . $name['NAME'] . "', '$datestring', '$amount', '$receivedby')";
            mysqli_query($link, $sqlInsert) or die(mysqli_error($link));

            $sql = "UPDATE thalilist set Paid = Paid + '$amount' WHERE thali = '$thalino'";
            mysqli_query($link, $sql) or die(mysqli_error($link));
        }
        $counter++;
    }
}

//update expenses
$counter = 1;
$file = fopen("fmbpayments.csv", "r");
while (($column = fgetcsv($file)) !== FALSE) {
    if ($column[14] != "Cancelled") {
        if ($counter != 1) {
            $voucherno = "";
            if (isset($column[0])) {
                $voucherno = mysqli_real_escape_string($link, $column[0]);
            }
            $vendor = "";
            if (isset($column[4])) {
                $vendor = mysqli_real_escape_string($link, $column[4]);
            }
            $amount = "";
            if (isset($column[5])) {
                $amount = mysqli_real_escape_string($link, $column[5]);
                $amount = str_replace('₹', '', $amount);
                $amount = str_replace(',', '', $amount);
                $amount = intval($amount);
            }
            $date = "";
            if (isset($column[3])) {
                $datefromcsv = mysqli_real_escape_string($link, $column[3]);
                $datestring = date_format(date_create($datefromcsv), "Y-m-d");
                $datemonth = getHijriMonth($datefromcsv);
                $datemonth = $months["$datemonth"];
            }
            $description = "";
            if (isset($column[11])) {
                $description = mysqli_real_escape_string($link, $column[11]);
            }

            $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
            $result = mysqli_query($link, $sql) or die(mysqli_error($link));
            $name = mysqli_fetch_assoc($result);

            $sqlInsert = "replace into account (`id`,`Date`,`Type`, `Amount`, `Month`,`Remarks`)
                   values ('$voucherno', '$datestring', '$vendor', '$amount', '$datemonth', '$description')";
            mysqli_query($link, $sqlInsert) or die(mysqli_error($link));
        }
        $counter++;
    }
}

//update new sabil
$counter = 1;
$file = fopen("fmbdetails.csv", "r");
while (($column = fgetcsv($file)) !== FALSE) {
    if ($counter != 1) {
        $thalino = "";
        if (isset($column[0])) {
            $thalino = mysqli_real_escape_string($link, $column[0]);
        }
        $name = mysqli_real_escape_string($link, $column[1] . " " . $column[2] . " " . $column[3]);
        $mobile = "";
        if (isset($column[4])) {
            $mobile = mysqli_real_escape_string($link, $column[4]);
        }
        $amount = "";
        if (isset($column[6])) {
            $amount = mysqli_real_escape_string($link, $column[6]);
            $amount = str_replace('₹', '', $amount);
            $amount = str_replace(',', '', $amount);
            $amount = intval($amount);
        }
        $pendingamount = "";
        if (isset($column[7])) {
            $pendingamount = mysqli_real_escape_string($link, $column[7]);
            $pendingamount = str_replace('₹', '', $pendingamount);
            $pendingamount = str_replace(',', '', $pendingamount);
            $pendingamount = intval($pendingamount);
        }
        $size = "";
        if (isset($column[9])) {
            $size = mysqli_real_escape_string($link, $column[9]);
        }

        $row = mysqli_fetch_assoc(mysqli_query($link, "select * from thalilist WHERE thali = '$thalino'"));
        if (!empty($row)) {
            // if there is change in size update the change table so that email can have it
            if ($row['thalisize'] != $size) {
                $changeinsert = "INSERT INTO change_table (`Thali`, `userid`, `Operation`, `Date`) VALUES ('$thalino','$row[id]', 'Change Size','$today')";
                mysqli_query($link, $changeinsert) or die(mysqli_error($link));
            }
            $sqlupdate = "update thalilist set NAME = '$name', yearly_hub = '$amount', Previous_Due = '$pendingamount', thalisize = '$size' WHERE thali = '$thalino'";
            mysqli_query($link, $sqlupdate) or die(mysqli_error($link));
        } else {
            $sqlinsert = "insert into thalilist (`Thali`, `NAME`, `CONTACT`,`Active`, `yearly_hub`, `Previous_Due`, `thalisize`)
                values ('$thalino', '$name', NULLIF('$mobile', ''), 0, '$amount', '$pendingamount', '$size')";
            mysqli_query($link, $sqlinsert) or die(mysqli_error($link));
        }
    }
    $counter++;
}

echo "Success\n";

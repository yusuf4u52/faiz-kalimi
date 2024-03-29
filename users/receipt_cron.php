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
            $payment_type = "";
            if (isset($column[5])) {
                $payment_type = mysqli_real_escape_string($link, $column[5]);
            }

            $transaction_id = "";
            if (isset($column[6])) {
                $transaction_id = mysqli_real_escape_string($link, $column[6]);
            }

            $date = "";
            if (isset($column[3])) {
                $datefromcsv = mysqli_real_escape_string($link, $column[3]);
                $datestring = getHijriDate($datefromcsv);
            } else {
                echo "Date cannot be empty for " . $receiptno . " and date " . $column[3];
            }

            $receivedby = "";
            if (isset($column[10])) {
                $receivedby = mysqli_real_escape_string($link, $column[10]);
            }

            $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
            $result = mysqli_query($link, $sql) or die(mysqli_error($link));
            $name = mysqli_fetch_assoc($result);

            $sqlInsert = "insert into receipts (`Receipt_No`,`Thali_No`,`userid`, `name`, `payment_type`, `Date`,`Amount`, `received_by`, `transaction_id`)
                   values ('$receiptno', '$thalino', '" . $name['id'] . "', '" . $name['NAME'] . "', '$payment_type', '$datestring', '$amount', '$receivedby', NULLIF('$transaction_id', ''))";
            mysqli_query($link, $sqlInsert) or die(mysqli_error($link));

            $sql = "UPDATE thalilist set Paid = Paid + '$amount' WHERE thali = '$thalino'";
            mysqli_query($link, $sql) or die(mysqli_error($link));
        }
        $counter++;
    }
}

// update annual niyaz
mysqli_query($link, "truncate niyaz") or die(mysqli_error($link));
$counter = 1;
$file = fopen("fmbanualniyaz.csv", "r");
while (($column = fgetcsv($file)) !== FALSE) {
    if ($column[13] != "Cancelled" && $column[17] == "Faizul Mawaidil Burhaniyah") {
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
        } else {
            echo "Date cannot be empty for " . $receiptno;
        }

        $receivedby = "";
        if (isset($column[10])) {
            $receivedby = mysqli_real_escape_string($link, $column[10]);
        }

        $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
        $result = mysqli_query($link, $sql) or die(mysqli_error($link));
        $name = mysqli_fetch_assoc($result);

        $sqlInsert = "insert into niyaz (`Receipt_No`,`Thali_No`,`userid`, `name`, `Date`,`Amount`,`received_by`)
                   values ('$receiptno', '$thalino', '" . $name['id'] . "', '" . $name['NAME'] . "', '$datestring', '$amount', '$receivedby')";
        mysqli_query($link, $sqlInsert) or die(mysqli_error($link));
    }
    $counter++;
}

// update sherullah niyaz
mysqli_query($link, "truncate sherullah") or die(mysqli_error($link));
$counter = 1;
$file = fopen("fmbsherullah.csv", "r");
while (($column = fgetcsv($file)) !== FALSE) {
    if ($column[13] != "Cancelled" && $column[17] == "Faizul Mawaidil Burhaniyah") {
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
        } else {
            echo "Date cannot be empty for " . $receiptno;
        }

        $receivedby = "";
        if (isset($column[10])) {
            $receivedby = mysqli_real_escape_string($link, $column[10]);
        }

        $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
        $result = mysqli_query($link, $sql) or die(mysqli_error($link));
        $name = mysqli_fetch_assoc($result);

        $sqlInsert = "insert into sherullah (`Receipt_No`,`Thali_No`,`userid`, `name`, `Date`,`Amount`,`received_by`)
                   values ('$receiptno', '$thalino', '" . $name['id'] . "', '" . $name['NAME'] . "', '$datestring', '$amount', '$receivedby')";
        mysqli_query($link, $sqlInsert) or die(mysqli_error($link));
    }
    $counter++;
}

// update zabihat
mysqli_query($link, "truncate zabihat") or die(mysqli_error($link));
$file = fopen("fmbzabihat.csv", "r");
while (($column = fgetcsv($file)) !== FALSE) {
    if ($column[13] != "Cancelled" && $column[17] == "Faizul Mawaidil Burhaniyah") {
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
        } else {
            echo "Date cannot be empty for " . $receiptno;
        }

        $receivedby = "";
        if (isset($column[10])) {
            $receivedby = mysqli_real_escape_string($link, $column[10]);
        }

        $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
        $result = mysqli_query($link, $sql) or die(mysqli_error($link));
        $name = mysqli_fetch_assoc($result);

        $sqlInsert = "insert into zabihat (`Receipt_No`,`Thali_No`,`userid`, `name`, `Date`,`Amount`,`received_by`)
                   values ('$receiptno', '$thalino', '" . $name['id'] . "', '" . $name['NAME'] . "', '$datestring', '$amount', '$receivedby')";
        mysqli_query($link, $sqlInsert) or die(mysqli_error($link));
    }
}

// update Ashara
mysqli_query($link, "truncate ashara") or die(mysqli_error($link));
$file = fopen("fmbashara.csv", "r");
while (($column = fgetcsv($file)) !== FALSE) {
    if ($column[13] != "Cancelled" && $column[17] == "Faizul Mawaidil Burhaniyah") {
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
        } else {
            echo "Date cannot be empty for " . $receiptno;
        }

        $receivedby = "";
        if (isset($column[10])) {
            $receivedby = mysqli_real_escape_string($link, $column[10]);
        }

        $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
        $result = mysqli_query($link, $sql) or die(mysqli_error($link));
        $name = mysqli_fetch_assoc($result);

        $sqlInsert = "insert into ashara (`Receipt_No`,`Thali_No`,`userid`, `name`, `Date`,`Amount`,`received_by`)
                   values ('$receiptno', '$thalino', '" . $name['id'] . "', '" . $name['NAME'] . "', '$datestring', '$amount', '$receivedby')";
        mysqli_query($link, $sqlInsert) or die(mysqli_error($link));
    }
}

// update voluntary contribution
mysqli_query($link, "truncate voluntary") or die(mysqli_error($link));
$file = fopen("fmbvoluntary.csv", "r");
while (($column = fgetcsv($file)) !== FALSE) {
    if ($column[13] != "Cancelled" && $column[17] == "Faizul Mawaidil Burhaniyah") {
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
        } else {
            echo "Date cannot be empty for " . $receiptno;
        }

        $receivedby = "";
        if (isset($column[10])) {
            $receivedby = mysqli_real_escape_string($link, $column[10]);
        }

        $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
        $result = mysqli_query($link, $sql) or die(mysqli_error($link));
        $name = mysqli_fetch_assoc($result);

        $sqlInsert = "insert into voluntary (`Receipt_No`,`Thali_No`,`userid`, `name`, `Date`,`Amount`,`received_by`)
                   values ('$receiptno', '$thalino', '" . $name['id'] . "', '" . $name['NAME'] . "', '$datestring', '$amount', '$receivedby')";
        mysqli_query($link, $sqlInsert) or die(mysqli_error($link));
    }
}

//update expenses
mysqli_query($link, "truncate account") or die(mysqli_error($link));
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

            $type = "";
            if (isset($column[6])) {
                $type = mysqli_real_escape_string($link, $column[6]);
            }

            $date = "";
            if (isset($column[3])) {
                $datefromcsv = mysqli_real_escape_string($link, $column[3]);
                $datestring = date_format(date_create($datefromcsv), "Y-m-d");
                $datemonth = date("m", strtotime(getHijriDate($datefromcsv)));
                $datemonth = $months[$datemonth];
            } else {
                echo "Date cannot be empty for " . $voucherno;
            }

            $description = "";
            if (isset($column[11])) {
                $description = mysqli_real_escape_string($link, $column[11]);
            }

            $paid_by = "";
            if (isset($column[13])) {
                $paid_by = mysqli_real_escape_string($link, $column[13]);
            }

            $sql = "select NAME,id from thalilist WHERE thali = '$thalino'";
            $result = mysqli_query($link, $sql) or die(mysqli_error($link));
            $name = mysqli_fetch_assoc($result);

            $sqlInsert = "insert into account (`id`,`Date`,`Type`,`vendor`, `Amount`, `Month`,`Remarks`,`paid_by`)
                   values ('$voucherno', '$datestring', '$type','$vendor', '$amount', '$datemonth', '$description', '$paid_by')";
            mysqli_query($link, $sqlInsert) or die(mysqli_error($link));
        }
        $counter++;
    }
}

//update new sabil
$counter = 1;
$file = fopen("fmbdetails.csv", "r");
$allThali = array();
while (($column = fgetcsv($file)) !== FALSE) {
    if ($counter != 1) {
        $thalino = "";
        if (isset($column[0])) {
            $thalino = mysqli_real_escape_string($link, $column[0]);
            $allThali[] = $thalino;
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

        $row = mysqli_fetch_assoc(mysqli_query($link, "select * from thalilist WHERE Thali = '$thalino'"));
        if (!empty($row)) {
            // if there is change in size update the change table so that email can have it
            if ($row['thalisize'] != $size && $row['Active'] == 1) {
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

// if active thalis are less than 10 then something is wrong
// I had to put in this check because suddenly all thalis were getting deactivated
// may be due to issues in getting data from sheet (dont know for sure)
// if (count($allThali) > 10) {
//     $allthalistring = "'" . implode("','", $allThali) . "'";
//     // deactivate sabil
//     $row = mysqli_query($link, "select * from thalilist WHERE Thali not in ($allthalistring) and Thali not like 'temp%'");
//     while ($value = mysqli_fetch_assoc($row)) {
//         if ($value['Active'] == 1) {
//             stoppermenant($value['Thali'], false);
//         } else {
//             mysqli_query($link, "update thalilist set Active=2 where Thali='" . $value['Thali'] . "'") or die(mysqli_error($link));
//         }
//     }
// }
echo "Success\n";

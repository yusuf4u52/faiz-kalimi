<?php

include('connection.php');

$query = "SELECT * FROM thalilist";
$result = mysqli_query($link, $query) or die(mysqli_error($link));

while ($row = mysqli_fetch_assoc($result)) {
  if (!empty($row['yearly_hub'])) {
    // fetch miqaats from db
    $sql = mysqli_query($link, "select miqat_date,miqat_description from sms_date");
    $miqaatslist = mysqli_fetch_all($sql);

    // calculate installment based on yearly hub and number of miqaats
    $installment = (int)($row['yearly_hub']) / count($miqaatslist);

    // add installment to the miqaat array by individually adding installment
    // to each row and than pushing that row into new array.
    $miqaatslistwithinstallement = array();
    foreach ($miqaatslist as $miqaat) {
      array_push($miqaat, $installment);
      array_push($miqaatslistwithinstallement, $miqaat);
    }

    // add any previous year pending to first installment
    $miqaatslistwithinstallement[0][2] += $row['Previous_Due'];

    // adjust installments if hub is paid
    if (!empty($row['Paid'])) {
      $paid = $row['Paid'];
      for ($i = 0; $i < sizeof($miqaatslistwithinstallement); $i++) {
        if ($miqaatslistwithinstallement[$i][2] - $paid  == 0) {
          $miqaatslistwithinstallement[$i][2] = 0;
          break;
        } else if ($miqaatslistwithinstallement[$i][2] - $paid > 0) {
          $miqaatslistwithinstallement[$i][2] = $miqaatslistwithinstallement[$i][2] - $paid;
          break;
        } else if ($miqaatslistwithinstallement[$i][2] - $paid < 0) {
          $paid = $paid - $miqaatslistwithinstallement[$i][2];
          $miqaatslistwithinstallement[$i][2] = 0;
        }
      }
    }

    // check if miqaat has passed, if so than move that passed miqaat amount to next
    $todays_date = date("Y-m-d");
    $previousInstall = 0;
    for ($i = 0; $i < sizeof($miqaatslistwithinstallement); $i++) {
      if ($miqaatslistwithinstallement[$i][0] < $todays_date) {
        $previousInstall += $miqaatslistwithinstallement[$i][2];
        $miqaatslistwithinstallement[$i + 1][2] += $miqaatslistwithinstallement[$i][2];
        $miqaatslistwithinstallement[$i][2] = "Miqaat Done";
      } else {
        $next_install = $miqaatslistwithinstallement[$i][2];
        mysqli_query($link, "UPDATE thalilist set next_install ='$next_install' WHERE Thali = '" . $row['Thali'] . "'") or die(mysqli_error($link));
        break;
      }
    }
    mysqli_query($link, "UPDATE thalilist set prev_install_pending ='$previousInstall' WHERE Thali = '" . $row['Thali'] . "'") or die(mysqli_error($link));
  }
}

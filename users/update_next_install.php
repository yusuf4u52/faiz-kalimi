<?php

include('connection.php');

$query = "SELECT * FROM thalilist";
$result = mysqli_query($link,$query) or die(mysqli_error($link));

while($row = mysqli_fetch_assoc($result)){   

  if(!empty($row['yearly_hub'])) {
  $reciepts_query_result_total = mysqli_fetch_assoc(mysqli_query($link,"SELECT sum(`Amount`) as total FROM `receipts` where Thali_No = '".$row['Thali']."'"));
  $total_amount_paid = $reciepts_query_result_total['total'];
  $thaliactivedate_query = mysqli_fetch_assoc(mysqli_query($link,"SELECT Date(datetime) as datetime FROM `change_table` where Thali = '".$row['Thali']."' AND operation = 'Start Thali' ORDER BY id limit 1"));
  $thaliactivedate = $thaliactivedate_query['datetime'];

  $sql = mysqli_query($link,"select miqat_date,miqat_description from sms_date");

  while($record = mysqli_fetch_assoc($sql))
    {
      $_miqaats[$record['miqat_date']] = $record['miqat_description'];
    }

  $row['Total_Pending'] = $row['Previous_Due'] + $row['yearly_hub'] + $row['Zabihat'] - $row['Paid'];

  $installment = (int)($row['Total_Pending'] + $row['Paid'])/count($_miqaats);;
  $todays_date = date("Y-m-d");
  $miqaat_gone = 0;

  $miqaats = array();
  $miqaats_past = array();
  foreach ($_miqaats as $mdate => $miqaat) {

    if($mdate < $todays_date)
    {
      $miqaats_past[$mdate] = $miqaat;
    }
    else
    {

      $month_installment = $installment;
      $miqaats[] = array(
                        $mdate,$miqaat,ceil($month_installment)
                        );
    }
  }

 
 $hub_baki = ((count($miqaats_past) - $miqaat_gone) * $installment) - $total_amount_paid;

 $miqaats[0][2] += $hub_baki;

 if ($miqaats[0][2] > 0) {
 $miqaats[0][2] = round($miqaats[0][2],-2);
 }
 $next_install = $miqaats[0][2];


 mysqli_query($link,"UPDATE thalilist set next_install ='$next_install' WHERE Thali = '".$row['Thali']."'") or die(mysqli_error($link));
 mysqli_query($link,"UPDATE thalilist set prev_install_pending ='$hub_baki' WHERE Thali = '".$row['Thali']."'") or die(mysqli_error($link));

 } 
 }
 ?>

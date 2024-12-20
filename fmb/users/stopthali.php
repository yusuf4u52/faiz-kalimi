<?php
include ('connection.php');
include ('_authCheck.php');

function getAllDates($startingDate, $endingDate){
    $datesArray = [];

    $startingDate = strtotime($startingDate);
    $endingDate = strtotime($endingDate);
            
    for ($currentDate = $startingDate; $currentDate <= $endingDate; $currentDate += (86400)) {
        $date = date('Y-m-d', $currentDate);
        $datesArray[] = $date;
    }

    return $datesArray;
}

if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
    
    date_default_timezone_set('Asia/Kolkata');
    if (isset($_POST['action']) && ( $_POST['action'] == 'stop_thali' || $_POST['action'] == 'stop_date_thali' || $_POST['action'] == 'start_thali' )) {
        $GivenDate = new DateTime($_POST['start_date'] . '20:00:00');
        $GivenDate->modify('-1 day');
        $GivenDate = $GivenDate->format('Y-m-d H:i:s');
        $CurrentDate = date('Y-m-d H:i:s');
    }

    if (isset($_POST['action']) && $_POST['action'] == 'admin_stop_thali') {
        $GivenDate = new DateTime($_POST['start_date'] . '00:00:00');
        $CurrentDate = date('Y-m-d H:i:s');
    }

    if ($CurrentDate < $GivenDate) { 

        $dates = getAllDates($_POST['start_date'], $_POST['end_date']);

        foreach($dates as $date) {
            $stop_thali = mysqli_query($link, "SELECT * FROM stop_thali WHERE `stop_date` = '" . $date . "' AND `thali` = '" . $_POST['thali'] . "'") or die(mysqli_error($link));
            if ($stop_thali->num_rows > 0) {
                $update_stop = "UPDATE INTO `stop_thali` SET `stop_date` = '".$date."' WHERE `stop_date` = '" . $date . "' AND `thali` = '" . $_POST['thali'] . "'";
                mysqli_query($link, $update_stop) or die(mysqli_error($link));
            } else {
                $insert_stop = "INSERT INTO `stop_thali` (`thali`,`stop_date`) VALUES ('" . $_POST['thali'] . "', '" . $date . "')";
                mysqli_query($link, $insert_stop) or die(mysqli_error($link));
            }
        }
        $action = 'srange';
        $sdate = $_POST['start_date'];
        $edate = $_POST['end_date'];
    } else {
        $action = 'srsvp';
        $sdate = $_POST['start_date'];
        $edate = $_POST['end_date'];
    }
    if (isset($_POST['action']) && $_POST['action'] == 'stop_thali') {
        header("Location: /fmb/users/index.php?action=" . $action . "&sdate=" . $sdate . "&edate=" . $edate);
    }
    if (isset($_POST['action']) && $_POST['action'] == 'admin_stop_thali') {
        header("Location: /fmb/users/thalisearch.php?thalino=" . $_POST['thalino'] . "&general=" . $_POST['general'] . "&year=" . $_POST['year'] . "&action=" . $action . "&sdate=" . $sdate . "&edate=" . $edate);
    }
    if (isset($_POST['action']) && $_POST['action'] == 'stop_date_thali') {
        header("Location: /fmb/users/stop_dates.php?action=" . $action . "&sdate=" . $sdate . "&edate=" . $edate);
    }
}

if( isset($_POST['action']) && $_POST['action'] == 'start_thali' ) {
    date_default_timezone_set('Asia/Kolkata');
    $GivenDate = new DateTime($_POST['start_date'] . '20:00:00');
    $GivenDate->modify('-1 day');
    $GivenDate = $GivenDate->format('Y-m-d H:i:s');
    $CurrentDate = date('Y-m-d H:i:s');

    if ($CurrentDate < $GivenDate) { 
        $sql = "DELETE FROM stop_thali WHERE `thali` = '".$_POST['thali']."' AND `stop_date` BETWEEN '".$_POST['start_date']."' AND '".$_POST['end_date']."'";
        mysqli_query($link,$sql) or die(mysqli_error($link));
        $action = 'start';
        $sdate = $_POST['start_date'];
    } else {
        $action = 'srsvp';
        $sdate = $_POST['start_date'];
    }
    header("Location: /fmb/users/stop_dates.php?action=start&sdate=".$sdate);
}
?>

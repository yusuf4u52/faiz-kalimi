<?php 
include('_authCheck.php'); 
include('_common.php');

$curr_page = basename($_SERVER['PHP_SELF']);
//$query = mysqli_query($link , "SELECT * FROM thalilist as th LEFT JOIN transporters as tr  on th.Transporter = tr.Name where th.Email_ID = '" . $_SESSION['email'] . "' OR th.SEmail_ID = '" . $_SESSION['email'] . "'") or die(mysqli_error($link));

$query = mysqli_query($link , "SELECT * FROM thalilist  where Email_ID = '" . $_SESSION['email'] . "' OR SEmail_ID = '" . $_SESSION['email'] . "'") or die(mysqli_error($link));
$values = $query->fetch_assoc();

$musaid_details = mysqli_fetch_assoc(mysqli_query($link, "SELECT username, mobile FROM users where email = '" . $values['musaid'] . "'"));

$_SESSION['thaliid'] = $values['id'];
$_SESSION['thali'] = $values['Thali'];

// Check if users gmail id is registered with us and he is a transporter against it
$transporter = mysqli_query($link , "SELECT * FROM transporters  where Email = '" . $_SESSION['email'] . "'") or die(mysqli_error($link));
if ($transporter->num_rows > 0 ) {
    header("Location: /fmb/transporter/home.php");
    exit;
}

// Check if users gmail id is registered with us and has got a thali number against it 
if (is_null($values['Active']) || $values['Active'] == 2) {
  $some_email = $_SESSION['email'];
  session_unset();
  session_destroy();
  $status = "Sorry! Either $some_email is not registered with us OR your thali is not active. Send an email to kalimimohallapoona@gmail.com";
  header("Location: /fmb/index.php?status=$status");
  exit;
}

// Redirect users to update details page if any details are missing
if($curr_page != 'update_details.php') {
	if (!empty($values['Thali']) && (empty($values['ITS_No']) || empty($values['CONTACT']) || empty($values['WhatsApp']) || empty($values['wingflat']) || empty($values['society']) || empty($values['Full_Address']))) {
	  header("Location: update_details.php?update_pending_info");
	  exit;
	}
}

// Check if there is any enabled event that needs users response
if($curr_page != 'events.php') {
	$query = "SELECT * FROM thalilist where Transporter is not null and Active in (0,1) and Email_ID = '" . $_SESSION['email'] . "'";
	$takesFmb = mysqli_num_rows(mysqli_query($link, $query));
	$result = mysqli_query($link, "SELECT * FROM events where showonpage='1' order by id");
	while ($values1 = mysqli_fetch_assoc($result)) {
	  // $showToNonFmbOnly = $values1['showtononfmb'];
	  // skip redirects to events for fmb holder if the database flag is set to do so
	  // if ($showToNonFmbOnly == 1) {
		if (!isResponseReceived($values1['id'])) {
		  header("Location: events.php");
		  exit;
		}
	 //  } else if (!isResponseReceived($values['id'])) {
		// header("Location: events.php");
		// exit;
	//   }
	}
}
?>
<header class="header">
    <div class="container-fluid py-2">
        <div class="row align-items-center">
            <div class="col-4">
                <a href="/fmb/users/index.php"><img class="img-fluid" src="/fmb/styles/img/logo.avif" alt="Faiz ul Mawaidil Burhaniyah (Kalimi Mohalla)" width="121" height="121" /></a>
            </div>
            <div class="col-8 text-end">
                <p class="text-capitalize m-0 fw-bold fst-italic">Salaam, <?php echo strtolower($values['NAME']); ?></p>
                <?php if (!empty($values['yearly_hub'])) {
                    if ($values['Active'] == 1) { 
                        echo '<p class="m-0">Thali No: <strong>' . $values['tiffinno'] . '</strong> | Thali Status: <strong class="text-success">Start</strong></p> ';
                    } else {
                        echo '<p class="m-0">Thali No: <strong>' . $values['tiffinno'] . '</strong> | Thali Status: <strong class="text-danger">Stop</strong></p> ';
                    }
                } ?>
            </div>
        </div>
    </div>
    <nav class="navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="/fmb/users/index.php">FMB (Kalimi Mohalla)</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#headernavbar"
                aria-controls="headernavbar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="headernavbar">
                <ul class="navbar-nav me-auto mx-xl-auto">
                    <?php if (isset($_SESSION['role'])) { ?>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/musaid.php">Musaid</a></li>
                    <?php } ?>
                    <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizagasiyawala@gmail.com', 'tinwalaabizer@gmail.com', 'saminabarnagarwala2812@gmail.com'))) { ?>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/thalisearch.php">Thaali Search</a></li>
                    <?php } ?>
                    <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizagasiyawala@gmail.com', 'ahmedi.murtaza@gmail.com'))) { ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Speacial Thalis</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/special/friday.php">Friday Thalis</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/special/barnamaj.php">Barnamaj Thalis</a></li>
                            </ul>
                        </li>
                    <?php } ?>
                    <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizagasiyawala@gmail.com', 'aliasgaraurangabadwala@gmail.com'))) {
                        ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Menu Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/menu/food.php">Food Items</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/menu/list.php">Menu List</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/menu/edited.php">Edited Menu</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/menu/feedback.php">Menu Feedback</a></li>
                            </ul>
                        </li>
                    <?php } ?>
                    <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizagasiyawala@gmail.com', 'hussainbarnagarwala14@gmail.com', 'abbas.saifee5@gmail.com', 'saminabarnagarwala2812@gmail.com', 'gheewalamf@gmail.com', 'zahradhorajiwala0@gmail.com'))) { ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Roti Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/roti/maker.php">Roti Maker</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/roti/distribute.php">Distribution</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/roti/recieved.php">Recieved</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/roti/report.php">Report</a></li>
                            </ul>
                        </li>
                    <?php } ?>
                    <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizagasiyawala@gmail.com', 'taherhafiji@gmail.com', 'saminabarnagarwala2812@gmail.com'))) { ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Transporter Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/transporter/list.php">Transporter</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporter/activethali.php">Active Thali</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporter/inactivethali.php">Inactive Thali</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporter/thalicount.php">Thali Count</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporter/report.php">Report</a></li>
                            </ul>
                        </li>
                    <?php } ?>
                    <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizagasiyawala@gmail.com', 'tinwalaabizer@gmail.com'))) { ?>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/pendingactions.php">Pending Actions</a></li>
                        <!--<li class="nav-item"><a class="nav-link" href="/fmb/users/_daily_hisab_entry.php">Daily Hisab</a></li>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/requestarchive.txt">CR NR</a></li>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/stopMultipleThaalis.php">Stop Thali</a></li>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/expenses_new.php">Expenses</a></li>-->
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Backend</a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/faiz">Admin</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/transporter_count">Transporter Thali Count</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/receipts">Receipts</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/payments">Payments</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/change">CR NR</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/event_response">Event Response</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/daily_hisab_items">Daily Items</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/daily_menu_count">Menu-Count</a>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/sf_hisab">SF Purchases</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/admin_scripts.php">Scripts</a></li>
                            </ul>
                        </li>
                        <!--<li class="nav-item"><a class="nav-link" href="/fmb/users/notpickedup.php">NotPickedUp</a></li>-->
                        <li class="nav-item"><a class="nav-link" target="_blank" href="/fmb/sms/index.php">SMS</a></li>
                    <?php } ?>
                    <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com'))) { ?>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/amount_received_by.php">Received</a></li>
                    <?php } ?>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/hub_details.php">Hub details</a></li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/stop_dates.php">Stop Dates</a></li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/events.php">Event Registration</a></li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/thali_details.php">Thali details</a></li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/update_details.php">Update details</a></li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/hoobHistory.php">My Receipts</a></li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<?php if($values['Total_Pending'] != 0) { ?>
    <div class="payment-reminder mt-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="alert <?php echo( ($values['yearly_hub'] > $values['Total_Pending']) ? 'alert-info' : 'alert-danger'); ?> mb-0" role="alert">
                        <div class="row align-items-center">
                            <div class="col-9">
                                <h6 class="mb-0">Request you to pay your FMB pending hub amount <strong>₹<?php echo $values['Total_Pending']; ?></strong>. Please share screenshot on <a href="https://wa.me/+917499860950 "><strong>+91 74998 60950</strong></a> for reciept.<h6>
                            </div>
                            <div class="col-3 text-end">
                                <a class="btn btn-light btn-sm mb-0" href="upi://pay?pa=dbjt-fmb-kalimi@ybl&pn=D B J T TRUST K M POONA - FMB&cu=INR" id="__UPI_BUTTON__">Pay Now</a>
                            </div>
                        </div>  
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>

<div class="content mt-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

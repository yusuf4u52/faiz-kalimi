<?php 
include('_authCheck.php'); 
include('_common.php');

$curr_page = basename($_SERVER['PHP_SELF']);
$query = mysqli_query($link , "SELECT * FROM thalilist as th LEFT JOIN transporters as tr  on th.Transporter = tr.Name where th.Email_ID = '" . $_SESSION['email'] . "' OR th.SEmail_ID = '" . $_SESSION['email'] . "'") or die(mysqli_error($link));
$values = $query->fetch_assoc();

$musaid_details = mysqli_fetch_assoc(mysqli_query($link, "SELECT NAME, CONTACT FROM thalilist where Email_ID = '" . $values['musaid'] . "'"));

$_SESSION['thaliid'] = $values['id'];
$_SESSION['thali'] = $values['Thali'];

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
// if($curr_page != 'events.php') {
	$query = "SELECT * FROM thalilist where Transporter is not null and Active in (0,1) and Email_ID = '" . $_SESSION['email'] . "'";
	$takesFmb = mysqli_num_rows(mysqli_query($link, $query));
	$result = mysqli_query($link, "SELECT * FROM events where showonpage='1' order by id");
	while ($values1 = mysqli_fetch_assoc($result)) {
	  // $showToNonFmbOnly = $values1['showtononfmb'];
	  // skip redirects to events for fmb holder if the database flag is set to do so
	  // if ($showToNonFmbOnly == 1) {
		// if ($takesFmb == 0 && !isResponseReceived($values1['id'])) {
		  header("Location: events.php");
		  exit;
		// }
	 //  } else if (!isResponseReceived($values['id'])) {
		// header("Location: events.php");
		// exit;
	//   }
	}
// }
?>
<header class="fmb-header">
    <!--<a href="/fmb/users/index.php"><img class="img-fluid mx-auto d-block my-3" src="assets/img/logo.png" alt="Faiz ul Mawaidil Burhaniyah (Kalimi Mohalla)" width="390" height="157" /></a>-->
    <div class="container-fluid py-2 text-center">
        <div class="row">
            <div class="col-12">
                <p class="text-capitalize m-0 fw-bold fst-italic">Salaam, <?php echo strtolower($values['NAME']); ?></p>
                <?php if (!empty($values['yearly_hub'])) {
                    echo '<p class="m-0">Sabeel No : <strong>' . $values['Thali'] . '</strong> | Thali No : <strong>' . $values['tiffinno'] . '</strong></p> ';
                } else {
                    echo '<p class="m-0">Sabeel No : <strong>' . $values['Thali'] . '</strong></p>';
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
                        <li class="nav-item">
                            <a class="nav-link" href="/fmb/users/musaid.php">Musaid</a>
                        </li>
                    <?php } ?>
                    <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com', 'tinwalaabizer@gmail.com', 'saminabarnagarwala2812@gmail.com'))) { ?>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/thalisearch.php">Thaali Search</a>
                        </li>
                    <?php } ?>
                    <?php
                    if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizlife@gmail.com'))) {
                        ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Start/Stop Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/userstart.php">User Start</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/userstop.php">User Stop</a></li>
                            </ul>
                        </li>
                        <?php
                    }
                    ?>
                    <?php
                    if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizlife@gmail.com'))) {
                        ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Menu Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/foodlist.php">Food Items</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/menulist.php">Menu List</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/usermenu.php">User Menu</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/userfeedmenu.php">User Feedback</a></li>
                            </ul>
                        </li>
                        <?php
                    }
                    ?>
                    <?php
                    if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizlife@gmail.com', 'hussainbarnagarwala14@gmail.com', 'saminabarnagarwala2812@gmail.com'))) {
                        ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Roti Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/rotimaker.php">Roti Maker</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/rotidistribute.php">Distribution</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/rotirecieved.php">Recieved</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/rotipayment.php">Payment</a></li>
                            </ul>
                        </li>
                        <?php
                    }
                    ?>
                    <?php
                    if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizlife@gmail.com', 'taherhafiji@gmail.com'))) {
                        ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Transporter Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/transporter.php">Transporter</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporterthalicount.php">Thali Count</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporterpayment.php">Payment</a></li>
                            </ul>
                        </li>
                        <?php
                    }
                    ?>
                    <?php
                    if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com', 'tinwalaabizer@gmail.com'))) {
                        ?>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/pendingactions.php">Pending Actions</a></li>
                        <!--<li class="nav-item"><a class="nav-link" href="/fmb/users/_daily_hisab_entry.php">Daily Hisab</a></li>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/requestarchive.txt">CR NR</a></li>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/stopMultipleThaalis.php">Stop Thali</a></li>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/expenses_new.php">Expenses</a></li>-->
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Backend</a>
                            <ul class="dropdown-menu" role="menu">
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/faiz">Admin</a></li>
                                <li><a class="dropdown-item"
                                        href="/fmb/admin/index.php/examples/transporter_count">Transporter Thali
                                        Count</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/receipts">Receipts</a>
                                </li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/payments">Payments</a>
                                </li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/change">CR NR</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/event_response">Event
                                        Response</a></li>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/daily_hisab_items">Daily
                                        Items</a></li>
                                <li><a class="dropdown-item"
                                        href="/fmb/admin/index.php/examples/daily_menu_count">Menu-Count</a>
                                <li><a class="dropdown-item" href="/fmb/admin/index.php/examples/sf_hisab">SF
                                        Purchases</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/admin_scripts.php">Scripts</a></li>
                            </ul>
                        </li>
                        <!--<li class="nav-item"><a class="nav-link" href="/fmb/users/notpickedup.php">NotPickedUp</a></li>-->
                        <li class="nav-item"><a class="nav-link" target="_blank" href="/fmb/sms/index.php">SMS</a></li>
                        <?php
                    }
                    ?>
                    <?php
                    if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com'))) {
                        ?>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/amount_received_by.php">Received</a>
                        </li>
                        <?php
                    }
                    ?>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/hoob_details.php">Hoob details</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/stop_dates.php">Stop Dates</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/events.php">Event Registration</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/thali_details.php">Thali details</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/update_details.php">Update details</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/hoobHistory.php">My Receipts</a></li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/users/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<?php
include('_authCheck.php');
include('_common.php');
require_once('helpers.php');

$curr_page = basename($_SERVER['PHP_SELF']);

$query = db_query(
    $link,
    "SELECT * FROM thalilist WHERE (Email_ID = ? OR SEmail_ID = ?) AND Active IS NOT NULL AND hardstop != 1",
    "ss",
    [$_SESSION['email'], $_SESSION['email']]
);

if ($query->num_rows > 0) {
    $values = $query->fetch_assoc();

    $musaid_result = db_query($link, "SELECT username, mobile FROM users WHERE email = ?", "s", [$values['musaid'] ?? '']);
    $musaid_details = mysqli_fetch_assoc($musaid_result);

    $_SESSION['thaliid'] = $values['id'];
    $_SESSION['thali'] = $values['Thali'];
} else {
    // Check if user's gmail id is registered with us and they are a transporter against it
    $transporter = db_query($link, "SELECT id FROM transporters WHERE Email = ?", "s", [$_SESSION['email']]);
    if ($transporter->num_rows > 0) {
        header("Location: /fmb/transporter/home.php");
        exit;
    } else {
        $some_email = $_SESSION['email'];
        session_unset();
        session_destroy();
        $status = "Sorry! Either $some_email is not registered with us or you are not taking barakat from Kalimi Mohallah. Please contact on below helpline numbers.";
        header("Location: /fmb/index.php?status=" . urlencode($status));
        exit;
    }
}

// Redirect users to update details page if any details are missing
if ($curr_page !== 'update_details.php') {
    if (!empty($values['Thali']) && (empty($values['ITS_No']) || empty($values['CONTACT']) || empty($values['WhatsApp']) || empty($values['wingflat']) || empty($values['society']))) {
        header("Location: update_details.php?update_pending_info");
        exit;
    }
}

// Check if there is any enabled event that needs the user's response
if ($curr_page !== 'events.php') {
    $takesFmbResult = db_query(
        $link,
        "SELECT id FROM thalilist WHERE Transporter IS NOT NULL AND Active IN (0, 1) AND Email_ID = ?",
        "s",
        [$_SESSION['email']]
    );
    $takesFmb = mysqli_num_rows($takesFmbResult);

    $result = db_query($link, "SELECT id FROM events WHERE showonpage = 1 ORDER BY id");
    while ($values1 = mysqli_fetch_assoc($result)) {
        if (!isResponseReceived($values1['id'])) {
            header("Location: events.php");
            exit;
        }
    }
}
?>
<header class="header">
    <div class="container-fluid py-2">
        <div class="row align-items-center">
            <div class="col-4">
                <a href="/fmb/users/index.php"><img class="img-fluid" src="/fmb/assets/img/logo.avif" alt="Faiz ul Mawaidil Burhaniyah (Kalimi Mohalla)" width="121" height="121" /></a>
            </div>
            <div class="col-8 text-end">
                <p class="text-capitalize m-0 fw-bold fst-italic">Salaam, <?php echo e(strtolower($values['NAME'] ?? '')); ?></p>
                <?php if (!empty($values['yearly_hub'])) {
                    if ($values['Active'] == 1) {
                        echo '<p class="m-0">Thali No: <strong>' . e($values['tiffinno']) . '</strong> | Thali Status: <strong class="text-success">Start</strong></p> ';
                    } else {
                        echo '<p class="m-0">Thali No: <strong>' . e($values['tiffinno']) . '</strong> | Thali Status: <strong class="text-danger">Stop</strong></p> ';

                        $stop_dates = db_query(
                            $link,
                            "WITH ranked_dates AS (
                                SELECT `id`, `thali`, DATE(`stop_date`) AS stop_date, ROW_NUMBER() OVER (PARTITION BY `thali` ORDER BY DATE(`stop_date`)) AS row_num
                                FROM `stop_thali` WHERE `thali` = ? AND DATE(`stop_date`) >= CURDATE()
                             ),
                             grouped_dates AS (
                                SELECT `id`, `thali`, `stop_date`, DATE_SUB(`stop_date`, INTERVAL row_num DAY) AS group_key FROM ranked_dates
                             )
                             SELECT `thali`, MIN(`stop_date`) AS start_date, MAX(`stop_date`) AS end_date, DATE_ADD(MAX(`stop_date`), INTERVAL 1 DAY) AS next_active_date
                             FROM grouped_dates GROUP BY `thali`, group_key ORDER BY start_date ASC LIMIT 1",
                            "s",
                            [$_SESSION['thali']]
                        );
                        if ($stop_dates->num_rows > 0) {
                            $row = mysqli_fetch_assoc($stop_dates);
                            echo '<p class="m-0">Thali Start Date: <strong class="text-success">' . e(date("d M Y", strtotime($row['next_active_date']))) . '</strong></p>';
                        }
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
                    <?php if (user_email_in(FOLLOW_UP_ACCESS_EMAILS)) { ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Follow Up</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/follow-up/local.php">Local Pending</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/follow-up/non-local.php">Non-Local Pending</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/follow-up/previous.php">Previous Year Pending</a></li>
                            </ul>
                        </li>
                    <?php } ?>
                    <?php if (user_email_in(THALISEARCH_ACCESS_EMAILS)) { ?>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/thalisearch.php">Thaali Search</a></li>
                    <?php } ?>
                    <?php if (user_email_in(SPECIAL_THALI_ACCESS_EMAILS)) { ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Special Thalis</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/special/friday.php">Friday Thalis</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/special/barnamaj.php">Barnamaj Thalis</a></li>
                            </ul>
                        </li>
                    <?php } ?>
                    <?php if (user_email_in(MENU_MANAGEMENT_ACCESS_EMAILS)) { ?>
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
                    <?php if (user_email_in(ROTI_MANAGEMENT_ACCESS_EMAILS)) { ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Roti Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/roti/maker.php">Roti Maker</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/roti/sheet.php">Roti Sheet</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/roti/payment.php">Roti Payment</a></li>
                            </ul>
                        </li>
                    <?php } ?>
                    <?php if (user_email_in(TRANSPORTER_MANAGEMENT_ACCESS_EMAILS)) { ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false">Transporter Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/transporter/list.php">Transporter</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporter/activethali.php">Active Thali</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporter/inactivethali.php">Inactive Thali</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporter/hardstopthali.php">Hardstop Thali</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporter/report.php">Report</a></li>
                            </ul>
                        </li>
                    <?php } ?>
                    <?php if (user_email_in(BACKEND_ACCESS_EMAILS)) { ?>
                        <li class="nav-item"><a class="nav-link" href="/fmb/users/pendingactions.php">Pending Actions</a></li>
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
                        <li class="nav-item"><a class="nav-link" target="_blank" rel="noopener noreferrer" href="/fmb/sms/index.php">SMS</a></li>
                    <?php } ?>
                    <?php $transporterNav = db_query($link, "SELECT id FROM transporters WHERE Email = ?", "s", [$_SESSION['email']]);
                    if ($transporterNav->num_rows > 0) {
                        echo '<li class="nav-item"><a class="nav-link" href="/fmb/transporter/home.php">Transporter Panel</a></li>';
                    } ?>
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
<?php if (($values['Total_Pending'] ?? 0) >= ($values['yearly_hub'] ?? 0) && ($values['Total_Pending'] ?? 0) > 4) { ?>
    <div class="payment-reminder mt-3">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-danger mb-0" role="alert">
                        <div class="row align-items-center">
                            <div class="col-9">
                                <h6 class="mb-0">
									Your FMB dues of <strong>₹<?php echo e(number_format((float) $values['Total_Pending'])); ?></strong> are still pending. As the 7th Miqaat, <strong>Urus: Syedna Mohammed Burhanuddin (RA)</strong>, has now passed, we humbly request you to kindly clear your outstanding FMB dues at the earliest. Timely payment helps us continue the smooth operation of Faiz and related services Once the payment is made, please  share the payment screenshot or receipt on <a href="https://wa.me/917499860950"><strong>+91 74998 60950</strong></a>  with your Sabeel Number so that we may update our records.
								</h6>
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

<main id="main-content" class="content mt-3" tabindex="-1">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

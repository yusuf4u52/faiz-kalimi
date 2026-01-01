<header class="header">
    <div class="container-fluid py-2">
        <div class="row align-items-center">
            <div class="col-4">
                <a href="/fmb/users/index.php"><img class="img-fluid" src="/fmb/styles/img/logo.avif" alt="Faiz ul Mawaidil Burhaniyah (Kalimi Mohalla)" width="121" height="121" /></a>
            </div>
            <div class="col-8 text-end">
                <h3>Admin Panel</h3>
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
                    <?php if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizagasiyawala@gmail.com'))) { ?>
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

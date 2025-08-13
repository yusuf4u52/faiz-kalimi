<header class="fmb-header">
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
                    if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizlife@gmail.com', 'hussainbarnagarwala14@gmail.com', 'abbas.saifee5@gmail.com', 'saminabarnagarwala2812@gmail.com', 'gheewalamf@gmail.com', 'zahradhorajiwala0@gmail.com'))) {
                        ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Roti Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/rotimaker.php">Roti Maker</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/rotidistribute.php">Distribution</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/rotirecieved.php">Recieved</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/rotireport.php">Report</a></li>
                            </ul>
                        </li>
                        <?php
                    }
                    ?>
                    <?php
                    if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'tinwalaabizer@gmail.com', 'moizlife@gmail.com', 'taherhafiji@gmail.com', 'saminabarnagarwala2812@gmail.com'))) {
                        ?>
                        <li class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">Transporter Management</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/fmb/users/transporter.php">Transporter</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporteractive.php">Active Thali</a></li>
                                <li><a class="dropdown-item" href="/fmb/users/transporterinactive.php">Inactive Thali</a></li>
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
                    if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com', 'moizlife@gmail.com',))) {
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
<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand font-bold" href="/">Faizul Mawaidil Burhaniya (Kalimi Mohalla)</a>
    </div>
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav navbar-right">
        <li><a href="/fmb/users/index.php">Home</a></li>
        <?php if ($_SESSION['role']) { ?>
          <li><a href="/fmb/users/musaid.php">Musaid</a></li>
        <?php } ?>

        <?php if ($_SESSION['email'] == 'saminabarnagarwala2812@gmail.com') { ?>
          <li><a href="/fmb/users/thalisearch.php">Thaali Search</a></li>
        <?php } ?>

        <?php
        if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com'))) {
        ?>
          <li><a href="/fmb/users/pendingactions.php">Pending Actions</a></li>
          <li><a href="/fmb/users/_daily_hisab_entry.php">Daily Hisab</a></li>
          <li><a href="/fmb/users/thalisearch.php">Thaali Search</a></li>
          <li><a href="/fmb/users/requestarchive.txt">CR NR</a></li>
          <li><a href="/fmb/users/stopMultipleThaalis.php">Stop Thali</a></li>
          <li><a href="/fmb/users/expenses_new.php">Expenses</a></li>
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Backend <span class="caret"></span></a>
            <ul class="dropdown-menu" role="menu">
              <li><a href="/fmb/admin/index.php/examples/faiz">Admin</a></li>
              <li><a href="/fmb/admin/index.php/examples/receipts">Receipts</a></li>
              <li><a href="/fmb/admin/index.php/examples/payments">Payments</a></li>
              <li><a href="/fmb/admin/index.php/examples/change">CR NR</a></li>
              <li><a href="/fmb/admin/index.php/examples/event_response">Event Response</a></li>
              <li><a href="/fmb/admin/index.php/examples/daily_hisab_items">Daily Items</a></li>
              <li><a href="/fmb/admin/index.php/examples/daily_menu_count">Menu-Count</a>
              <li><a href="/fmb/admin/index.php/examples/sf_hisab">SF Purchases</a></li>
              <li><a href="/fmb/users/admin_scripts.php">Scripts</a></li>
            </ul>
          </li>
          <li><a href="/fmb/users/notpickedup.php">NotPickedUp</a></li>
          <li><a target="_blank" href="/fmb/sms/index.php">SMS</a></li>
        <?php
        }
        ?>
        <?php
        if (in_array($_SESSION['email'], array('mulla.moiz@gmail.com', 'yusuf4u52@gmail.com'))) {
        ?>
          <li><a href="/fmb/users/amount_received_by.php">Received</a></li>
        <?php
        }
        ?>
        <li><a href="/fmb/users/hoobHistory.php">My Receipts</a></li>
        <li><a href="/fmb/users/update_details.php">Update details</a></li>
        <li><a href="/fmb/users/events.php">Event Registration</a></li>
        <li><a href="/fmb/users/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
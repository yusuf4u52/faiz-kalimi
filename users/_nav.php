<nav class="navbar navbar-default">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand font-bold" href="index.php">Faizul Mawaidil Burhaniya (Kalimi Mohalla)</a>
    </div>

    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav navbar-right">
        <?php if ($_SESSION['role']) { ?>
          <li><a href="musaid.php">Musaid</a></li>
        <?php } ?>
        <?php
        if (in_array($_SESSION['email'], array('nationalminerals52@gmail.com', 'mesaifee52@gmail.com', 'murtaza52@gmail.com', 'murtaza.sh@gmail.com', 'yusuf4u52@gmail.com', 'tzabuawala@gmail.com', 'mustafamnr@gmail.com', 'ismailsidhpuri@gmail.com'))) {
        ?>
          <li><a href="pendingactions.php">Pending Actions</a></li>
          <li><a href="_daily_hisab_entry.php">Daily Hisab</a></li>
          <li><a href="thalisearch.php">Thaali Search</a></li>
          <li><a href="requestarchive.txt">CR NR</a></li>
          <li><a href="stopMultipleThaalis.php">Stop Thali</a></li>
          <li><a href="expenses_new.php">Expenses</a></li>
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Backend <span class="caret"></span></a>
            <ul class="dropdown-menu" role="menu">
              <li><a href="../admin/index.php/examples/faiz">Admin</a></li>
              <li><a href="../admin/index.php/examples/receipts">Receipts</a></li>
              <li><a href="../admin/index.php/examples/change">CR NR</a></li>
              <li><a href="../admin/index.php/examples/event_response">Event Response</a></li>
              <li><a href="../admin/index.php/examples/daily_hisab_items">Daily Items</a></li>
              <li><a href="../admin/index.php/examples/daily_menu_count">Menu-Count</a>
              <li><a href="../admin/index.php/examples/sf_hisab">SF Purchases</a></li>
              <li><a href="admin_scripts.php">Scripts</a></li>
            </ul>
          </li>
          <li><a href="notpickedup.php">NotPickedUp</a></li>
          <li><a target="_blank" href="../sms/index.php">SMS</a></li>
        <?php
        }
        ?>
        <?php
        if (in_array($_SESSION['email'], array('yusuf4u52@gmail.com', 'mustafamnr@gmail.com'))) {
        ?>
          <li><a href="amount_received_by.php">Received</a></li>
        <?php
        }
        ?>
        <li><a href="hoobHistory.php">My Receipts</a></li>
        <li><a href="update_details.php">Update details</a></li>
        <li><a href="events.php">Event Registration</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </div>
  </div>
</nav>
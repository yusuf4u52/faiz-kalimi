<?php
require_once('../users/connection.php');
require_once('../users/helpers.php');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['email'])) {
    header('Location: /testfmb/index.php');
    exit;
}

try {
    $transporterResult = db_query($link, "SELECT * FROM `transporters` WHERE `Email` = ?", "s", [$_SESSION['email']]);
} catch (RuntimeException $e) {
    error_log('[navbar.php] ' . $e->getMessage());
    http_response_code(500);
    exit('Sorry, something went wrong loading this page. Please try again in a moment.');
}

if ($transporterResult->num_rows > 0) {
    $values = $transporterResult->fetch_assoc();
    $_SESSION['transporterid'] = $values['id'];
    $_SESSION['transporter'] = $values['Name'];
} else {
    session_unset();
    session_destroy();
    $status = 'Sorry! Either that email is not registered with us OR you are not a transporter of Kalimi Mohallah. Send an email to kalimimohallapoona@gmail.com';
    header('Location: index.php?status=' . urlencode($status));
    exit;
}
?>
<header class="header">
    <div class="container-fluid py-2">
        <div class="row align-items-center">
            <div class="col-4">
                <a href="/testfmb/users/index.php"><img class="img-fluid" src="/testfmb/assets/img/logo.avif" alt="Faiz ul Mawaidil Burhaniyah (Kalimi Mohalla)" width="121" height="121" /></a>
            </div>
            <div class="col-8 text-end">
                <p class="text-capitalize m-0 fw-bold fst-italic">Salaam, <?php echo e(strtolower($_SESSION['transporter'])); ?> Bhai</p>
            </div>
        </div>
    </div>
    <nav class="navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="/testfmb/transporter/home.php">FMB (Kalimi Mohalla)</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#headernavbar"
                aria-controls="headernavbar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="headernavbar">
                <ul class="navbar-nav me-auto mx-xl-auto">
                    <?php
                    try {
                        $userPanelResult = db_query(
                            $link,
                            "SELECT id FROM `thalilist` WHERE (`Email_ID` = ? OR `SEmail_ID` = ?) AND `Active` IS NOT NULL AND `hardstop` != 1",
                            "ss",
                            [$_SESSION['email'], $_SESSION['email']]
                        );
                        if ($userPanelResult->num_rows > 0) {
                            echo '<li class="nav-item"><a class="nav-link" href="/testfmb/users/index.php">User Panel</a></li>';
                        }
                    } catch (RuntimeException $e) {
                        error_log('[navbar.php] ' . $e->getMessage());
                        // Non-fatal: simply skip showing the "User Panel" link if this lookup fails.
                    }
                    ?>
                    <li class="nav-item"><a class="nav-link" href="/testfmb/transporter/start_thali.php">Active Thali</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/testfmb/transporter/stop_thali.php">Inactive Thali</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/testfmb/transporter/report.php">Report</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/testfmb/users/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<div class="content mt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
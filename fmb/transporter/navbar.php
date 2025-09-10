<?php 
require_once('../users/connection.php');
if (!isset($_SESSION)) {
	session_start();
}
$query = mysqli_query($link , "SELECT * FROM transporters  where Email = '" . $_SESSION['email'] . "'") or die(mysqli_error($link));
if ($query->num_rows > 0 ) {
    $values = $query->fetch_assoc();
    $_SESSION['transporterid'] = $values['id'];
    $_SESSION['transporter'] = $values['Name'];
} else {
  $some_email = $_SESSION['email'];
  session_unset();
  session_destroy();
  $status = "Sorry! Either $some_email is not registered with us OR your are not a transporter of kalimi mohallah. Send an email to kalimimohallapoona@gmail.com";
  header("Location: index.php?status=$status");
  exit;
}
?>
<header class="header">
    <div class="container-fluid py-2">
        <div class="row align-items-center">
            <div class="col-4">
                <a href="/fmb/users/index.php"><img class="img-fluid" src="/fmb/styles/img/logo.png" alt="Faiz ul Mawaidil Burhaniyah (Kalimi Mohalla)" width="121" height="121" /></a>
            </div>
            <div class="col-8 text-end">
                <p class="text-capitalize m-0 fw-bold fst-italic">Salaam, <?php echo strtolower($_SESSION['transporter']); ?> Bhai</p>
            </div>
        </div>
    </div>
    <nav class="navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="/fmb/transporter/home.php">FMB (Kalimi Mohalla)</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#headernavbar"
                aria-controls="headernavbar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse navbar-collapse" id="headernavbar">
                <ul class="navbar-nav me-auto mx-xl-auto">
                    <li class="nav-item"><a class="nav-link" href="/fmb/transporter/start_thali.php">Start Thali</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/transporter/stop_thali.php">Stop Thali</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/fmb/transporter/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>
<div class="content mt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

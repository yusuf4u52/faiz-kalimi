<?php
$message = getSessionData('transit_data');
removeSessionData('transit_data');


$miqaat_name = 'No Miqaat';
$result = get_current_miqaat();
if( is_record_found($result) ) {
  $miqaat_name = $result->data[0]['name'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>FMB - Roti Khidmat</title>
  <meta charset="utf-8">
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script> -->

</head>

<body>

  <nav class="navbar navbar-expand-sm bg-success navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="<?= getAppData('BASE_URI') ?>">FMB</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- <div class="collapse navbar-collapse" id="collapsibleNavbar">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" href="#">Link</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Link</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#">Link</a>
          </li>
        </ul>
      </div> -->
    </div>
  </nav>

  <!-- <div class="container drop-shadow"> -->
  <div class="container mt-3">
  <!-- <h2>Example of Jumbotron</h2> -->
  <!-- <div class="mt-4 p-5 bg-primary text-white rounded"> -->

    <div class="row">
      <div class="col-4">
        <img class='img-fluid' width="100" height="100" src="assets/imgs/RotiAmal.JPG" style="opacity: .8"><br />
      </div>
      <div class="col-8">
        <div class="header" style="text-align: center; vertical-align: middle; font-weight:20px">
          <h4 class="color-brown"><strong>Faiz ul Mawaid il Burhaniyah</strong></h4>
          <h5 class="color-brown">(Kalimi Mohalla)</h5>
          <h5 class="color-brown">Miqaat : <?= $miqaat_name ?></h5>          
        </div>
      </div>
    </div>
    
    <hr/><hr/>
    
  <!-- <h2>Example of Jumbotron</h2> -->
   <div class="mt-4 p-5 bg-primary text-white rounded">
    <?php if (isset($message)) { ?>
      <div class="card">
        <div class="card-header">
          <?= $message ?>
        </div>
      </div>
    <?php } ?>

    <?php

    if (function_exists('content_display')) {
      content_display();
    }

    ?>
    </div>
  </div>

</body>

</html>
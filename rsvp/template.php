<?php
$message = getSessionData('transit_data');
removeSessionData('transit_data');


$miqaat_name = 'No Miqaat';
$end_datetime = null;
$result = get_current_miqaat();
if (is_record_found($result)) {
    $miqaat = $result->data[0];
    $miqaat_name = $miqaat['name'];
    $end_datetime = date_create($miqaat['end_datetime']);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>RSVP - Kalimi Mohalla</title>
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
<!-- <body style="height:1500px"> -->

    <nav class="navbar fixed-top navbar-expand-sm bg-primary navbar-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
            <img class='img-fluid' width="30" height="30" src="assets/imgs/Logo.png">            
            <a class="navbar-brand" href="<?= getAppData('BASE_URI') ?>">Miqaat RSVP (Kalimi Mohalla)</a>
            </div>
            <!-- <a class="navbar-brand" href="<?= getAppData('BASE_URI') ?>">FMB</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#collapsibleNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>             -->
        </div>
    </nav>

    <!-- <div class="container drop-shadow"> -->
    <!-- <div class="container mt-3"> -->
    <div class="container-fluid mt-3" style="margin-top:80px">  
    <div class="mt-4 p-5 rounded">
        <h5 class="color-brown">Miqaat : <?= $miqaat_name ?></h5>
        <?php
        if( isset($end_datetime) ) {
            $miqaat_ends = date_format($end_datetime, 'd/m/Y H:i:s');
            echo "<h6>Fill Survey before : $miqaat_ends</h6>";
        }
        ?>
        <hr/>
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

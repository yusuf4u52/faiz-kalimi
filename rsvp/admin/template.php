<?php
$message = getSessionData('transit_data');
removeSessionData('transit_data');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>RSVP - Kalimi Mohalla</title>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
 
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script> -->

</head>

<body>
    <!-- <body style="height:1500px"> -->

    <nav class="navbar fixed-top navbar-expand-sm bg-primary navbar-dark">
        <div class="container-fluid">
            <div class="navbar-brand">
                <img class='img-fluid' width="30" height="30" src="<?= getAppData('BASE_URI') ?>/assets/imgs/Logo.png">
                <a class="navbar-brand" href="<?= getAppData('BASE_URI') ?>">Miqaat RSVP (Kalimi Mohalla)</a>
            </div>
        </div>
    </nav>

    <!-- <div class="container drop-shadow"> -->
    <!-- <div class="container mt-3"> -->
    <div class="container-fluid mt-3" style="margin-top:80px">
        <div class="mt-4 p-5 rounded">
            <hr />
            <?php if (isset($message)) { ?>                
                <div class="alert alert-primary alert-dismissible fade show" role="alert">
                    <strong><?= $message ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
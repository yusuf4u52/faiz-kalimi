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
    <title>Miqaat RSVP (Kalimi Mohalla - Poona)</title>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/rsvp/assets/imgs/logo.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alice&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="/fmb/users/assets/css/main.css" />
</head>
<body>
    <header class="header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-6">
                    <a href="<?= getAppData('BASE_URI') ?>"><img class="img-fluid" src="/rsvp/assets/imgs/logo.png" alt="Miqaat RSVP (Kalimi Mohalla - Poona)" width="153" height="153" /></a>
                </div>
                <div class="col-6 text-end">
                    <h5><a href="<?= getAppData('BASE_URI') ?>">Miqaat RSVP <br/> (Kalimi Mohalla - Poona)</a></h5>
                </div>
            </div>
        </div>
    </header>
    <div class="content mt-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                             <h2 clas="mb-3">Miqaat: <?= $miqaat_name ?></h2>
                            <?php
                            if (isset($end_datetime)) {
                                $miqaat_ends = date_format($end_datetime, 'd/m/Y H:i:s');
                                echo "<h6>Fill Survey before : $miqaat_ends</h6>";
                            }
                            ?>
                            <hr />
                            <?php if (isset($message)) { ?>                
                                <div class="alert alert-info alert-dismissible fade show" role="alert">
                                    <strong><?= $message ?></strong>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php } 
                            
                            if (function_exists('content_display')) {
                                content_display();
                            }

                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="rsvp-footer text-center my-2">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <p class="mb-1"><small><strong>Helpline:</strong> <a href="https://wa.me/+917767825353">+91 77678 25353</a></small></p>
                    <p><small>&copy; Copyright <?php echo date('Y'); ?> Kalimi Mohallah - Poona. All Rights Reserved.</small></p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
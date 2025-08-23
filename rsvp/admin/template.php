<?php
$message = getSessionData('transit_data');
removeSessionData('transit_data');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Miqaat RSVP (Kalimi Mohalla - Poona)</title>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?= getAppData('BASE_URI') ?>/assets/imgs/Logo.png" />
    <link href="https://fonts.googleapis.com/css?family=Lato:200,300,400,500,500i,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="/fmb/users/assets/css/main.css" />
</head>
<body>
    <header class="rsvp-header">
        <a href="<?= getAppData('BASE_URI') ?>"><img class="img-fluid img-fluid img-fluid mx-auto d-block my-3" src="/rsvp/assets/imgs/Logo.png" alt="Miqaat RSVP (Kalimi Mohalla - Poona)" width="153" height="153" /></a>
        <nav class="navbar">
            <div class="container-fluid">
                <div class="navbar-brand">
                    <a class="navbar-brand" href="<?= getAppData('BASE_URI') ?>">Miqaat RSVP (Kalimi Mohalla - Poona)</a>
                </div>
            </div>
        </nav>
    </header>
    <div class="rsvp-body mt-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
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
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <p><small>&copy; Copyright <?php echo date('Y'); ?> Kalimi Mohallah - Poona. All Rights Reserved.</small></p>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
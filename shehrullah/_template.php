<?php
// Include UI helper functions
include_once __DIR__ . '/_ui.php';

$header = getAppData('HEADER') ?? true;
$footer = getAppData('FOOTER') ?? true;
$heading = getAppData('HEADING') ?? true;

$message = getSessionData(TRANSIT_DATA);
$suppress_message = getAppData('SUPPRESS_TRANSIT_MESSAGE');
if (!$suppress_message) {
    removeSessionData(TRANSIT_DATA);
}

//$url = getAppData('APP_BASE_URI');
$url = getAppData('APP_BASE_URI');
$homepage = getAppData('BASE_URI'); 

$is_auth = is_authenticated();
$udata = getSessionData(THE_SESSION_ID);

$hijri = get_current_hijri_year() ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sherullah <?=$hijri?>H (Kalimi Mohalla - Poona)</title>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="<?= $homepage ?>/assets/img/logo.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="<?= $homepage ?>/assets/css/main.css" />
</head>
<body>
    <?php if ($header) { ?>
        <header class="header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-4">
                        <a href="<?= $homepage ?>"><img class="img-fluid" src="<?= $homepage ?>/assets/img/logo.png" alt="Shehrullah <?=$hijri?>H (Kalimi Mohalla - Poona)" width="153" height="153" /></a>
                    </div>
                    <div class="col-8 text-end">
                        <h4><a href="<?= $homepage ?>">Shehrullah <?=$hijri?>H <br/> (Kalimi Mohalla - Poona)</a></h4>
                    </div>
                </div>
            </div>
        </header>
    <?php } ?>
    <?php if($is_auth) { ?>
        <nav class="navbar">
            <div class="container-fluid">
                <a class="navbar-brand" href="/fmb/users/index.php">FMB (Kalimi Mohalla)</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#headernavbar"
                    aria-controls="headernavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div class="collapse navbar-collapse" id="headernavbar">
                    <ul class="navbar-nav me-auto mx-xl-auto">
                        <li class="nav-item"><a class="nav-link" href="#"><?=$udata->name??''?></li>
                        <li class="nav-item"><a class="nav-link" href="<?= $homepage ?>/login">Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php } ?>
    <div class="content mt-4">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <?php if (isset($message) && !$suppress_message) { ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <strong><?= $message ?></strong>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php } ?>
                    <?php
                    $func = VIEW_FUNC;
                    if (function_exists($func)) {
                        $func();
                    } else {
                        echo 'Nothing...';
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php if ($footer) { ?>
            <footer class="footer text-center my-2">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <p><small>&copy; Copyright <?php echo date('Y'); ?> Kalimi Mohallah - Poona. All Rights Reserved.</small></p>
                        </div>
                    </div>
                </div>
            </footer>
        <?php } ?>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            the_script();
        });
    </script>

</body>

</html>
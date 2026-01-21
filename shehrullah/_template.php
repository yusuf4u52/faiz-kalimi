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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Shehrullah(AeK)</title>
    <link rel="stylesheet" href="<?= $url ?>/_assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="<?= $url ?>/_assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="<?= $url ?>/_assets/css/style.css">
    <link rel="stylesheet" href="<?= $url ?>/_assets/css/ui-theme.css">
    <link rel="shortcut icon" href="<?= $url ?>/_assets/images/favicon.ico" />
</head>

<body>
    <div class="container-scroller">
        <?php if ($header) { ?>
            <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
                <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                    <a class="navbar-brand brand-logo" href="<?= $homepage ?>">
                        <img
                            src="<?= $url ?>/_assets/images/anjuman_e_kalimi_logo.png" alt="logo" /> 
                    </a>
                    <a class="navbar-brand brand-logo-mini" href="<?= $homepage ?>"><img
                            src="<?= $url ?>/_assets/images/anjuman_e_kalimi_small.png" alt="logo" /></a>
                </div>

                <div class="navbar-menu-wrapper d-flex align-items-stretch">
                    <!-- <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                        <span class="mdi mdi-menu"></span>
                    </button> -->
                    <?php if($is_auth) {?>
                    <ul class="navbar-nav navbar-nav-right">
                        <li class="nav-item nav-profile dropdown">
                            <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <!-- <div class="nav-profile-img">
                                    <img src="<?= $url ?>/_assets/images/faces/face1.jpg" alt="image">
                                    <span class="availability-status online"></span>
                                </div> -->
                                <div class="nav-profile-text">
                                    <p class="mb-1 text-black"><?=$udata->name??''?></p>
                                </div>
                            </a>
                            <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
                                <!-- <a class="dropdown-item" href="#">
                                    <i class="mdi mdi-cached me-2 text-success"></i> Activity Log </a> -->
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?= $homepage ?>/login">
                                    <i class="mdi mdi-logout me-2 text-primary"></i> Signout </a>
                            </div>
                        </li>
                    </ul>
                    <?php } ?>
                </div>
            </nav>
        <?php } ?>

        <div class="container-fluid page-body-wrapper">
            <!-- <div class="main-panel"> -->
            <div class="content-wrapper">
                <?php if ($heading) { ?>
                    <div class="page-header">
                        <h3 class="page-title">Shehrullah <?=$hijri?>H</h3>
                    </div>
                <?php } ?>

                <?php if (isset($message) && !$suppress_message) { ?>
                    <div class="alert alert-primary alert-dismissible fade show" role="alert">
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
        <?php if ($footer) { ?>
            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <span class="text-muted d-block text-center text-sm-start d-sm-inline-block">Copyright ©
                        Anjuman-e-Kalimi</span>
                    <span class="float-none float-sm-end mt-1 mt-sm-0 text-end">Shehrullah Forms</span>
                </div>
            </footer>
        <?php } ?>

    </div>

    <script src="<?= $url ?>/_assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="<?= $url ?>/_assets/js/misc.js"></script>

    <script>
        $(document).ready(function () {
            the_script();
        });
    </script>

</body>

</html>
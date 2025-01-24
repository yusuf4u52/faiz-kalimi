<?php
$message = getSessionData('transit_data');
removeSessionData('transit_data');

$url = getAppData('BASE_URI');
$hijri = get_current_hijri_year();

$homepage = $url . (getAppData('arg0') === SECURE_DIR ? '/admin' : ''); 

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Shehrullah(AeK)</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="<?= $url ?>/assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="<?= $url ?>/assets/vendors/css/vendor.bundle.base.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <!-- endinject -->
    <!-- Layout styles -->
    <link rel="stylesheet" href="<?= $url ?>/assets/css/style.css">
    <!-- End layout styles -->
    <link rel="shortcut icon" href="<?= $url ?>/assets/images/favicon.ico" />
</head>

<body>
    <div class="container-scroller">
        <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                <a class="navbar-brand brand-logo" href="<?= $homepage ?>"><img
                src="<?= $url ?>/assets/images/anjuman_e_kalimi_logo.png" alt="logo" /></a>
                <a class="navbar-brand brand-logo-mini" href="<?= $homepage ?>"><img
                        src="<?= $url ?>/assets/images/anjuman_e_kalimi_small.png" alt="logo" /></a>
            </div>
        </nav>
        <div class="container-fluid page-body-wrapper">
            <!-- <div class="main-panel"> -->
            <div class="content-wrapper">
                <div class="page-header">
                    <h3 class="page-title">Shehrullah <?=$hijri?>H</h3>                    
                </div>
                <div class="row">
                    <div class="col">
                        <?php if (isset($message)) { ?>
                            <div class="alert alert-primary alert-dismissible fade show" role="alert">
                                <strong><?= $message ?></strong>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php } ?>

                        <?php

                        if (function_exists('content_display')) {
                            content_display();
                        } else {
                            echo 'Nothing...';
                        }
                        ?>
                    </div>
                </div>
            </div>            
        </div>

        <footer class="footer">
            <div class="container-fluid d-flex justify-content-between">
                <span class="text-muted d-block text-center text-sm-start d-sm-inline-block">Copyright ©
                    Anjuman-e-Kalimi</span>
                <span class="float-none float-sm-end mt-1 mt-sm-0 text-end">Shehrullah Forms</span>
            </div>
        </footer>
    </div>

    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script> -->

    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="<?= $url ?>/assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <!-- <script src="<?= $url ?>/assets/js/off-canvas.js"></script>
    <script src="<?= $url ?>/assets/js/hoverable-collapse.js"></script> -->
    <script src="<?= $url ?>/assets/js/misc.js"></script> 
    <!-- endinject -->
    <!-- Custom js for this page -->
    <!-- <script src="<?= $url ?>/assets/js/file-upload.js"></script> -->
    <!-- End custom js for this page -->

    <script>
        $(document).ready(function () {
            the_script();
        });
    </script>

</body>

</html>
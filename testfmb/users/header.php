<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $pageSlug = pathinfo($scriptPath, PATHINFO_FILENAME);
    $pageTitles = [
        '/index.php' => 'Login',
        '/users/index.php' => 'Dashboard',
    ];
    $pageTitle = $pageTitle ?? ($pageTitles[$scriptPath] ?? ucwords(str_replace(['_', '-'], ' ', $pageSlug)));
    ?>
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> - FMB Kalimi</title>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/testfmb/assets/img/logo.avif" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alice&family=Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap" rel="stylesheet">
    <!-- Manifest added for Progressive Web Apps -->
    <link rel="manifest" href="/testfmb/manifest.json">
    <link rel="prefetch" href="/testfmb/manifest.json">
    <meta name="theme-color" content="#c36d29">
    <link rel="apple-touch-icon" href="/testfmb/assets/img/logo-192x192.png">
    <!-- / PWA -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.14.1/themes/base/jquery-ui.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/css/bootstrap-datepicker.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.bootstrap5.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.4/css/buttons.bootstrap5.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.5/main.min.css" />
    <link rel="stylesheet" href="/testfmb/assets/css/main.css" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const heading = document.querySelector('h1, h2');
            const headingText = heading ? heading.textContent.replace(/\s+/g, ' ').trim() : '';

            if (headingText) {
                document.title = headingText + ' - FMB Kalimi';
            }
        });
    </script>
</head>

<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

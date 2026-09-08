<?php

declare(strict_types=1);

// Testing page: lets an allow-listed admin generate a single Sabeel's
// statement PDF on demand (nothing is emailed) to sanity-check report output.
//
// Not a login page of its own — reads the SAME PHP session fmb/index.php's
// Google Sign-In already sets (shared cookie, path '/'), and just checks the
// logged-in email against config.php's 'access.allowed_emails'. Every other
// file under reporting/ stays blocked from the web by reporting/.htaccess;
// this is the only entry point.

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require __DIR__ . '/../fmb/vendor/autoload.php';

use JamaatReport\JamaatOnlineClient;
use JamaatReport\SabeelReportBuilder;
use JamaatReport\SabeelReportRenderer;

$configPath = __DIR__ . '/config/config.php';
if (!is_file($configPath)) {
    http_response_code(500);
    echo 'Missing reporting/config/config.php — copy config.example.php and fill in real values.';
    exit;
}
$config = require $configPath;
$allowedEmails = $config['access']['allowed_emails'] ?? [];

$loggedInEmail = $_SESSION['email'] ?? null;
$isLoggedIn = ($_SESSION['fromLogin'] ?? null) === 'true' && $loggedInEmail !== null;

if (!$isLoggedIn || !in_array($loggedInEmail, $allowedEmails, true)) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"><title>Reporting — Access restricted</title></head>
    <body style="font-family: sans-serif; max-width: 480px; margin: 80px auto; text-align: center;">
        <h1>Access restricted</h1>
        <p>Sign in with an allow-listed Google account on the main site first.</p>
        <p><a href="/fmb/index.php">Go to sign-in</a></p>
    </body>
    </html>
    <?php
    exit;
}

$_SESSION['reporting_csrf_token'] ??= bin2hex(random_bytes(32));
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $csrfOk = isset($_POST['csrf_token']) && hash_equals($_SESSION['reporting_csrf_token'], (string) $_POST['csrf_token']);
    $sabeelNo = trim((string) ($_POST['sabeel_no'] ?? ''));

    if (!$csrfOk) {
        $errorMessage = 'Your session expired, please try again.';
    } elseif ($sabeelNo === '') {
        $errorMessage = 'Enter a Sabeel number.';
    } else {
        try {
            set_time_limit(120);

            $jo = $config['jamaatonline'];
            $reportConfig = $config['report'] ?? [];

            $client = new JamaatOnlineClient($jo['base_url'], $jo['username'], $jo['password']);
            $client->login();

            $years = $client->getFiscalYears();
            $currentYearId = array_key_last($years);
            $client->selectFiscalYear($currentYearId);
            $fiscalYearLabel = $years[$currentYearId];

            $dueRows = $client->getSabeelDueReport($sabeelNo, '');
            $roster = $client->getSabeelMemberRoster('', $sabeelNo);

            $currentAcademicYearId = array_key_last($client->getMadresaAcademicYears());
            $madresaStudentRoster = $client->getMadresaStudentRoster();
            $madresaFeeRows = $client->getMadresaFeeDueReport($currentAcademicYearId);

            $itsNoToMemberId = [];
            foreach ($roster as $memberId => $member) {
                $itsNoToMemberId[$member['itsNo']] = $memberId;
            }

            $builder = new SabeelReportBuilder($client);
            $reports = $builder->buildFromDueRows($dueRows, $itsNoToMemberId, $madresaFeeRows, $madresaStudentRoster);
            $report = $reports[$sabeelNo] ?? null;

            if ($report === null) {
                $errorMessage = "No Sabeel found with number \"$sabeelNo\".";
            } else {
                $renderer = new SabeelReportRenderer(
                    $reportConfig['org_name'] ?? 'Jamaat',
                    $reportConfig['currency_symbol'] ?? 'Rs. '
                );
                $pdfHtml = $renderer->render($report, $fiscalYearLabel, date('d-M-Y'));

                $options = new \Dompdf\Options();
                $options->set('isRemoteEnabled', false);
                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($pdfHtml);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $pdfContent = $dompdf->output();

                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="sabeel-' . $sabeelNo . '-statement-test.pdf"');
                header('Content-Length: ' . (string) strlen($pdfContent));
                echo $pdfContent;
                exit;
            }
        } catch (\Throwable $e) {
            error_log('[reporting/index.php] Report generation failed: ' . $e->getMessage());
            // Only allow-listed admins reach this branch, so the raw message
            // (which fields failed to scrape, HTTP status, ...) is useful for
            // debugging rather than something to hide.
            $errorMessage = 'Report generation failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Reporting — Sabeel Statement Test</title></head>
<body style="font-family: sans-serif; max-width: 480px; margin: 60px auto;">
    <p>Signed in as <?= htmlspecialchars($loggedInEmail, ENT_QUOTES, 'UTF-8') ?></p>
    <h1>Generate a Sabeel Statement (test)</h1>
    <p>Builds one Sabeel's statement PDF and opens it directly — nothing is emailed.</p>
    <?php if ($errorMessage !== null): ?>
        <p style="color: #b00020;"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="generate">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['reporting_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <label>Sabeel No. <input type="text" name="sabeel_no" required autofocus></label>
        <button type="submit">Generate PDF</button>
    </form>
</body>
</html>

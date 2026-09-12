<?php

declare(strict_types=1);

// Cron entrypoint: for every Sabeel account, builds a Sabeel statement (current
// outstanding per Sabeel type, scraped from the office's own SabeelDueReport.aspx)
// and emails it to the Sabeel's Head of Family.
//
// Requires config/config.php's 'jamaatonline' login to have Sabeel-report viewing
// permission (a plain member/cronuser login gets redirected to Unauthorized.aspx
// on SabeelDueReport.aspx).
//
// Usage:
//   php scripts/generate_and_email_sabeel_reports.php [--dry-run] [--limit=N]
//       [--only-sabeel-no=304] [--only-its-no=30376437]
//
//   --dry-run          Build and log every report, but don't send any email.
//   --limit=N          Stop after processing N Sabeel holders (for testing).
//   --only-sabeel-no   Process only the given Sabeel number.
//   --only-its-no      Process only the member with the given ITS number.

require __DIR__ . '/../../fmb/vendor/autoload.php';

use JamaatReport\JamaatOnlineClient;
use JamaatReport\SabeelReportBuilder;
use JamaatReport\SabeelReportMailer;
use JamaatReport\SabeelReportRenderer;

$config = require __DIR__ . '/../config/config.php';
$jo = $config['jamaatonline'];
$smtp = $config['smtp'];
$reportConfig = $config['report'] ?? [];

$options = getopt('', ['dry-run', 'limit:', 'only-sabeel-no:', 'only-its-no:']);
$dryRun = array_key_exists('dry-run', $options);
$limit = isset($options['limit']) ? (int) $options['limit'] : null;
$onlySabeelNo = (string) ($options['only-sabeel-no'] ?? '');
$onlyItsNo = (string) ($options['only-its-no'] ?? '');

$client = new JamaatOnlineClient($jo['base_url'], $jo['username'], $jo['password']);
$client->login();

$years = $client->getFiscalYears();
$currentYearId = array_key_last($years);
$client->selectFiscalYear($currentYearId);
$fiscalYearLabel = $years[$currentYearId];
echo "Logged in. Fiscal year: $fiscalYearLabel\n";

// A filter narrows both the due-report scrape and the roster search server-side
// (much cheaper than fetching the whole jamaat's ~1200 members / ~1180 Sabeel
// lines just to throw most of it away when testing against one Sabeel).
$dueRows = $client->getSabeelDueReport($onlySabeelNo, $onlyItsNo);
$roster = $client->getSabeelMemberRoster($onlyItsNo, $onlySabeelNo);
echo 'Due report lines: ' . count($dueRows) . ', roster size: ' . count($roster) . "\n";

// Madresa has no per-member filter — every run fetches the whole (small, ~60
// student) jamaat-wide roster and fee list, and the builder below picks out
// just the lines belonging to each Sabeel's HOF.
$academicYears = $client->getMadresaAcademicYears();
$currentAcademicYearId = array_key_last($academicYears);
$madresaStudentRoster = $client->getMadresaStudentRoster();
$madresaFeeRows = $client->getMadresaFeeDueReport($currentAcademicYearId);
echo "Madresa academic year: {$academicYears[$currentAcademicYearId]}, students: " . count($madresaStudentRoster) . ", fee rows: " . count($madresaFeeRows) . "\n\n";

$itsNoToMemberId = [];
foreach ($roster as $memberId => $member) {
    $itsNoToMemberId[$member['itsNo']] = $memberId;
}

$builder = new SabeelReportBuilder($client);
$reports = $builder->buildFromDueRows($dueRows, $itsNoToMemberId, $madresaFeeRows, $madresaStudentRoster, $fiscalYearLabel);

$renderer = new SabeelReportRenderer(
    $reportConfig['org_name'] ?? 'Jamaat',
    $reportConfig['currency_symbol'] ?? 'Rs. '
);
$mailer = new SabeelReportMailer($smtp);

$sent = 0;
$skippedNoEmail = 0;
$failed = 0;
$processed = 0;

foreach ($reports as $report) {
    $processed++;
    $name = ($report['hof']['fullname'] ?? '') !== '' ? $report['hof']['fullname'] : $report['itsNo'];
    $email = trim($report['hof']['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "SKIP sabeel {$report['sabeelNo']} ($name, ITS {$report['itsNo']}): no valid email on file.\n";
        $skippedNoEmail++;
        continue;
    }

    if ($dryRun) {
        echo "DRY-RUN sabeel {$report['sabeelNo']} -> $name <$email>, grand total {$report['grandTotal']}\n";
    } else {
        $pdfHtml = $renderer->render($report, $fiscalYearLabel, date('d-M-Y'));
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $bodyHtml = "<p>Dear {$safeName},</p>"
            . "<p>Please find attached your statement of dues for Sabeel No. {$report['sabeelNo']} ($fiscalYearLabel).</p>"
            . '<p>This is a system-generated message; please contact the jamaat office for any queries.</p>';

        try {
            $mailer->send(
                $email,
                $name,
                "Your Statement of Dues — {$fiscalYearLabel}",
                $bodyHtml,
                $pdfHtml,
                "sabeel-{$report['sabeelNo']}-statement.pdf"
            );
            echo "SENT sabeel {$report['sabeelNo']} -> $name <$email>\n";
            $sent++;
        } catch (\Throwable $e) {
            echo "FAILED sabeel {$report['sabeelNo']} -> $name <$email>: {$e->getMessage()}\n";
            $failed++;
        }
    }

    if ($limit !== null && $processed >= $limit) {
        echo "\n(--limit=$limit reached, stopping early)\n";
        break;
    }
}

echo "\nDone. sent=$sent failed=$failed skipped_no_email=$skippedNoEmail\n";

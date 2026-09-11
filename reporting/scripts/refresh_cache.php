<?php

declare(strict_types=1);

// Cron entrypoint: snapshots the jamaat-wide (not Sabeel-specific) JamaatOnline
// lookups reporting/index.php needs for every report to a local JSON file, so
// a "Generate PDF" click doesn't have to re-scrape ~1180 Sabeel due lines, the
// full member roster, and the whole Madresa roster/fee report from scratch on
// every single request. Only the four bulk, already-whole-jamaat-in-one-call
// reports are cached here — the per-Sabeel calls (HOF contact details, Faiz
// due, Hoob due) stay live in index.php, since caching those for every member
// would mean looping the scrape over the whole jamaat here instead of just the
// handful of already-bulk endpoints below.
//
// Suggested crontab (every 5 minutes):
//   */5 * * * * php /path/to/reporting/scripts/refresh_cache.php >> /path/to/reporting/cache/refresh.log 2>&1
//
// Usage: php scripts/refresh_cache.php

require __DIR__ . '/../../fmb/vendor/autoload.php';

use JamaatReport\JamaatDataCache;
use JamaatReport\JamaatOnlineClient;

$config = require __DIR__ . '/../config/config.php';
$jo = $config['jamaatonline'];

$cache = new JamaatDataCache(__DIR__ . '/../cache/jamaat_snapshot.json');

try {
    $client = new JamaatOnlineClient($jo['base_url'], $jo['username'], $jo['password']);
    $client->login();

    $years = $client->getFiscalYears();
    $fiscalYearId = array_key_last($years);
    $client->selectFiscalYear($fiscalYearId);
    $fiscalYearLabel = $years[$fiscalYearId];

    // Blank filters return every Sabeel due line / roster member jamaat-wide in
    // one call each (see JamaatOnlineClient) — the same calls index.php used to
    // repeat, narrowed, on every click; here they run once and are shared.
    $dueRows = $client->getSabeelDueReport('', '');
    $roster = $client->getSabeelMemberRoster('', '');

    $academicYears = $client->getMadresaAcademicYears();
    $academicYearId = array_key_last($academicYears);
    $academicYearLabel = $academicYears[$academicYearId];
    $madresaStudentRoster = $client->getMadresaStudentRoster();
    $madresaFeeRows = $client->getMadresaFeeDueReport($academicYearId);

    $cache->write([
        'fiscalYearId' => $fiscalYearId,
        'fiscalYearLabel' => $fiscalYearLabel,
        'dueRows' => $dueRows,
        'roster' => $roster,
        'academicYearId' => $academicYearId,
        'academicYearLabel' => $academicYearLabel,
        'madresaStudentRoster' => $madresaStudentRoster,
        'madresaFeeRows' => $madresaFeeRows,
    ]);

    echo 'Cache refreshed: ' . count($dueRows) . ' due lines, ' . count($roster) . ' roster members, '
        . count($madresaStudentRoster) . ' madresa students, ' . count($madresaFeeRows) . " madresa fee rows.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, '[refresh_cache] Failed: ' . $e->getMessage() . "\n");
    exit(1);
}

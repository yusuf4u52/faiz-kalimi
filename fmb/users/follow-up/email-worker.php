<?php
/**
 * Optional cron worker — makes sending independent of any open browser tab.
 *
 *   php email-worker.php                  continue every unfinished campaign
 *   php email-worker.php enqueue local    (optional) create today's list, then send
 *   php email-worker.php enqueue all      same for local, non-local and previous
 *
 * Run it every minute from cron. Each run sends for ~50 seconds and exits, so
 * runs never pile up (they share a lock with the browser button, so the two can
 * never send at the same time).
 *
 * Hostinger hPanel > Advanced > Cron Jobs, custom, every minute:
 *   * * * * * /usr/bin/php /home/<user>/domains/<domain>/public_html/fmb/reports/email-worker.php >/dev/null 2>&1
 * (use the PHP path and folder shown in your hPanel).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

ini_set('display_errors', '1');
set_time_limit(0);

require_once __DIR__ . '/email-queue.php';

const WORKER_BUDGET_SECONDS = 50;

$args = array_slice($argv, 1);
$reports = queueReports();

try {
    if (($args[0] ?? '') === 'enqueue') {
        $names = ($args[1] ?? '') === 'all' ? array_keys($reports) : array_slice($args, 1);
        foreach ($names as $name) {
            if (!isset($reports[$name])) {
                fwrite(STDERR, "Unknown report: $name\n");
                continue;
            }
            [$campaign, $created] = queueStart($name, $link);
            echo ($created ? 'Created' : 'Already exists') . " $name: " . json_encode(queueSummary($campaign)) . "\n";
        }
    }

    $deadline = microtime(true) + WORKER_BUDGET_SECONDS;

    foreach (array_keys($reports) as $name) {
        $remaining = $deadline - microtime(true);
        if ($remaining < 3) {
            break;
        }
        $campaign = queueLoad(queueActivePath($name));
        if ($campaign === null || $campaign['status'] === 'done') {
            continue;
        }
        $result = queueProcess($name, $remaining);
        echo date('c') . " $name: sent_now=" . ($result['sent_now'] ?? 0)
            . ' sent=' . ($result['sent'] ?? '?')
            . ' pending=' . ($result['pending'] ?? '?')
            . ' failed=' . ($result['failed'] ?? '?')
            . (!empty($result['busy']) ? ' (busy: another sender is running)' : '')
            . "\n";
    }
} catch (Throwable $e) {
    error_log('[email-worker] ' . $e->getMessage());
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

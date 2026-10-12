<?php
/**
 * Browser endpoint for the "Email Members" buttons.
 *
 * Every request here is SHORT (well under the gateway timeout). Nothing is
 * sent in the background: the browser calls `work` repeatedly, and each call
 * sends for ~20 seconds and returns. If the tab is closed, nothing is lost —
 * the queue file remembers who has been emailed, and clicking again (or the
 * cron worker, see email-worker.php) carries on.
 *
 * Actions (POST): start | work | status      (+ report=local|non-local|previous)
 */

ob_start(); // swallow any stray output (warnings, BOMs) so the JSON stays valid
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/email-queue.php';

function emailJsonResponse(array $payload, int $status = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!user_email_in(FOLLOW_UP_ACCESS_EMAILS)) {
    emailJsonResponse(['ok' => false, 'message' => 'Forbidden.'], 403);
}

// Release the session lock NOW. Without this, a long-running request holds the
// session file and every other request from the same browser (e.g. the next
// progress call) blocks behind it until it times out.
session_write_close();

const WEB_WORK_BUDGET_SECONDS = 20; // keep well below the 60s gateway timeout

$reports = queueReports();
$action = (string) ($_POST['action'] ?? 'status');
$report = (string) ($_POST['report'] ?? '');

if (!isset($reports[$report])) {
    emailJsonResponse(['ok' => false, 'message' => 'Invalid email report.'], 400);
}

try {
    if ($action === 'start') {
        [$campaign, $created] = queueStart($report, $link);
        emailJsonResponse(['ok' => true, 'created' => $created, 'exists' => true] + queueSummary($campaign));
    }

    if ($action === 'work') {
        emailJsonResponse(['ok' => true] + queueProcess($report, WEB_WORK_BUDGET_SECONDS));
    }

    if ($action === 'status') {
        $campaign = queueLoad(queueActivePath($report));
        emailJsonResponse(['ok' => true, 'exists' => $campaign !== null] + ($campaign !== null ? queueSummary($campaign) : []));
    }

    emailJsonResponse(['ok' => false, 'message' => 'Unknown action.'], 400);
} catch (Throwable $e) {
    error_log('[email.php] ' . $e->getMessage());
    emailJsonResponse(['ok' => false, 'message' => $e->getMessage()], 500);
}

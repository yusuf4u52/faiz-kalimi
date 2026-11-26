<?php
/**
 * Save endpoint for "Bulk Assign FMB Musaid" (POST: musaid=<users.id>, ids[]=<thalilist.id>...).
 * Returns JSON. Same access list as the pending-hoob pages.
 */
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/musaid-bulk.php';

function musaidJson(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!musaid_can_assign()) {
    musaidJson(['ok' => false, 'message' => 'Forbidden.'], 403);
}
// A cross-site form cannot add this header, so forged requests are refused.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    musaidJson(['ok' => false, 'message' => 'Invalid request.'], 400);
}

$ids = array_values(array_unique(array_filter(
    array_map('intval', (array) ($_POST['ids'] ?? [])),
    static fn ($v) => $v > 0
)));
if (!$ids || count($ids) > 2000) {
    musaidJson(['ok' => false, 'message' => 'Select between 1 and 2000 members.'], 400);
}

$musaidId = (int) ($_POST['musaid'] ?? 0);
$users = musaid_users();
if (!isset($users[$musaidId])) {
    musaidJson(['ok' => false, 'message' => 'The selected user does not have the FMB Musaid role.'], 400);
}

try {
    $in = build_in_clause($ids, 'i');
    db_query(
        $link,
        "UPDATE thalilist SET fmb_musaid = ? WHERE id IN " . $in['sql'],
        'i' . $in['types'],
        array_merge([$musaidId], $in['params'])
    );
    musaidJson(['ok' => true, 'assigned' => count($ids), 'musaid' => $musaidId, 'label' => $users[$musaidId]]);
} catch (Throwable $e) {
    error_log('[musaid-assign.php] ' . $e->getMessage());
    musaidJson(['ok' => false, 'message' => 'Could not save the assignment. Nothing was changed.'], 500);
}

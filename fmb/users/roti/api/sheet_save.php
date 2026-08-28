<?php
/**
 * POST /roti/api/sheet_save.php
 * Body (JSON): { "edits": [ { "type": "distribution"|"received", ... }, ... ] }
 *
 * Batch autosave endpoint for the weekly sheet (roti/sheet.php). Accepts one
 * or more cell edits per request and persists each one via
 * upsert_week_distribution() / upsert_roti_received() in helpers.php.
 *
 * distribution edit shape (one per maker per week — covers Opening + Given
 * Atta/Oil together, since they all live on the same fmb_roti_distribution
 * row):
 *   { "type": "distribution", "maker_id": 12, "week_start": "2026-08-24",
 *     "opening_atta": 4.5, "given_atta": 20, "opening_oil": 0.4, "given_oil": 2 }
 * received edit shape (one per maker per day):
 *   { "type": "received", "maker_id": 12, "date": "2026-08-24", "roti_recieved": 120 }
 *
 * Responds with { "ok": true, "rows": [...] } — the freshly recomputed week
 * (same shape as sheet_week.php's "rows") so the front end can refresh every
 * read-only column after a save without a page reload.
 */
include('../../connection.php');
include('../../_authCheck.php');
require_once('../helpers.php');
include('../../getHijriDate.php');

header('Content-Type: application/json');

$by = current_user_email();
if ($by === null) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Your session has expired. Please log in again.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$edits = is_array($body['edits'] ?? null) ? $body['edits'] : [];
if (empty($edits)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No edits supplied.']);
    exit;
}

$weekStartForRefresh = null;
$affectedWeeksByMaker = [];
$errors = [];

mysqli_begin_transaction($link);
try {
    foreach ($edits as $edit) {
        $type = $edit['type'] ?? '';
        $makerId = (int) ($edit['maker_id'] ?? 0);
        if ($makerId <= 0) {
            $errors[] = 'Invalid maker in edit: ' . json_encode($edit);
            continue;
        }

        if ($type === 'distribution') {
            $weekStart = (string) ($edit['week_start'] ?? '');
            if (!DateTime::createFromFormat('Y-m-d', $weekStart)) {
                $errors[] = "Invalid week_start for maker $makerId.";
                continue;
            }
            $openingAtta = is_numeric($edit['opening_atta'] ?? null) ? (float) $edit['opening_atta'] : 0.0;
            $givenAtta = is_numeric($edit['given_atta'] ?? null) ? (float) $edit['given_atta'] : 0.0;
            $openingOil = is_numeric($edit['opening_oil'] ?? null) ? (float) $edit['opening_oil'] : 0.0;
            $givenOil = is_numeric($edit['given_oil'] ?? null) ? (float) $edit['given_oil'] : 0.0;
            if ($givenAtta < 0 || $givenOil < 0) {
                $errors[] = "Given Atta/Oil can't be negative (maker $makerId).";
                continue;
            }
            $weekStartForRefresh = $weekStartForRefresh ?? $weekStart;
            $affectedWeeksByMaker[$makerId] = isset($affectedWeeksByMaker[$makerId])
                ? min($affectedWeeksByMaker[$makerId], $weekStart)
                : $weekStart;
            upsert_week_distribution($link, $makerId, $weekStart, $openingAtta, $givenAtta, $openingOil, $givenOil, $by);
        } elseif ($type === 'received') {
            $date = (string) ($edit['date'] ?? '');
            if (!DateTime::createFromFormat('Y-m-d', $date)) {
                $errors[] = "Invalid date for maker $makerId.";
                continue;
            }
            if ((int) (new DateTime($date))->format('N') === 7) {
                $errors[] = "Sunday has no thaali entry (maker $makerId).";
                continue;
            }
            $roti = is_numeric($edit['roti_recieved'] ?? null) ? (int) $edit['roti_recieved'] : null;
            if ($roti === null || $roti < 0) {
                $errors[] = "Roti received can't be negative (maker $makerId, $date).";
                continue;
            }
            $weekStartForRefresh = $weekStartForRefresh ?? week_start_monday($date);
            $affectedWeek = week_start_monday($date);
            $affectedWeeksByMaker[$makerId] = isset($affectedWeeksByMaker[$makerId])
                ? min($affectedWeeksByMaker[$makerId], $affectedWeek)
                : $affectedWeek;
            upsert_roti_received($link, $makerId, $date, $roti, 'recieved', $by);
        } else {
            $errors[] = 'Unknown edit type: ' . e($type);
        }
    }

    if (!empty($errors)) {
        throw new RuntimeException(implode(' ', $errors));
    }

    foreach ($affectedWeeksByMaker as $makerId => $fromWeekStart) {
        propagate_week_openings($link, (int) $makerId, $fromWeekStart);
    }

    mysqli_commit($link);
} catch (Throwable $e) {
    mysqli_rollback($link);
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

$matrix = build_week_matrix($link, $weekStartForRefresh ?? week_start_monday(date('Y-m-d')));
echo json_encode(['ok' => true, 'rows' => $matrix['rows']], JSON_UNESCAPED_UNICODE);

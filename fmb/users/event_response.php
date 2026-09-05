<?php
include('connection.php');
require_once('helpers.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['fromLogin'])) {
    http_response_code(401);
    exit;
}

$thaliId = $_POST['Thaliid'] ?? null;
$eventId = $_POST['Eventid'] ?? null;
$response = $_POST['Response'] ?? null;
$comments = (string) ($_POST['Comments'] ?? '');

// Ownership check: without this, any authenticated member could POST an
// arbitrary Thaliid and submit an event response on someone else's behalf.
if ($thaliId === null || (string) $thaliId !== (string) ($_SESSION['thaliid'] ?? '')
    || !ctype_digit((string) $eventId) || !in_array($response, ['yes', 'no'], true)) {
    http_response_code(400);
    exit;
}

$result = db_query($link, "SELECT `Thali`, `ITS_No`, `NAME`, `CONTACT` FROM thalilist WHERE id = ?", "s", [$thaliId]);
$values = mysqli_fetch_assoc($result);

if (!$values) {
    http_response_code(404);
    exit;
}

// ON DUPLICATE KEY UPDATE instead of REPLACE INTO — REPLACE silently does a
// DELETE+INSERT, churning the id on every re-submission. Requires the
// UNIQUE KEY (thaliid, eventid) that REPLACE INTO already relied on to
// know which row to overwrite — see schema-modernization.sql.
db_query(
    $link,
    "INSERT INTO event_response (`thaliid`, `thalino`, `eventid`, `response`, `its`, `name`, `mobile`, `comments`)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        `response` = VALUES(`response`),
        `comments` = VALUES(`comments`)",
    "ssisssss",
    [$thaliId, (string) $values['Thali'], (int) $eventId, $response, (string) $values['ITS_No'], (string) $values['NAME'], (string) $values['CONTACT'], $comments]
);

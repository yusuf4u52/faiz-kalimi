<?php

/**
 * These functions used to include('connection.php') on every call, which
 * opens a brand-new mysqli connection each time. Since isResponseReceived()
 * is called once per event row in navbar.php's loop, a page with several
 * active events was silently opening several extra DB connections on every
 * single page load. They now reuse the connection already established by
 * whichever page included connection.php before this file.
 */

function isResponseReceived($eventid): bool
{
    global $link;
    $result = db_query(
        $link,
        "SELECT id FROM event_response WHERE eventid = ? AND thaliid = ? LIMIT 1",
        "ss",
        [$eventid, $_SESSION['thaliid'] ?? '']
    );
    return mysqli_num_rows($result) > 0;
}

function getResponse($eventid): ?array
{
    global $link;
    $result = db_query(
        $link,
        "SELECT * FROM event_response WHERE eventid = ? AND thaliid = ? LIMIT 1",
        "ss",
        [$eventid, $_SESSION['thaliid'] ?? '']
    );
    $row = mysqli_fetch_assoc($result);
    return $row ?: null;
}

<?php
/**
 * Shared helpers for the whole app. Every module's own helpers.php
 * (menu/, roti/, transporter/) requires this file before adding module-specific code.
 * The generic pieces (escaping,
 * queries, menu JSON codec, validators, permission lists) live here once.
 *
 * Load this directly from users/ root pages: require_once('helpers.php');
 * Module pages get it transitively through their own helpers.php.
 */

/** Escape a value for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Run a parameterised query and return the mysqli_result.
 * $types is the mysqli bind_param type string, e.g. "ssi".
 */
function db_query(mysqli $link, string $sql, string $types = '', array $params = []): mysqli_result|bool
{
    $stmt = mysqli_prepare($link, $sql);
    if ($stmt === false) {
        throw new RuntimeException('Query prepare failed: ' . mysqli_error($link));
    }
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('Query execute failed: ' . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Decode a menu_item / menu_feed column value. New rows are stored as
 * JSON (via encode_menu_item()); older rows may still be PHP-serialized,
 * so we fall back to unserialize() for those.
 */
function decode_menu_item(?string $raw): array
{
    if ($raw === null || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded;
    }

    $legacy = @unserialize($raw);
    return is_array($legacy) ? $legacy : [];
}

/** Encode a menu_item array for storage. Always writes JSON going forward. */
function encode_menu_item(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * Build a `column IN (?, ?, ...)` fragment plus matching bind types/params
 * for a list of scalar values.
 *
 * @return array{sql: string, types: string, params: array}
 */
function build_in_clause(array $values, string $type = 's'): array
{
    $placeholders = implode(',', array_fill(0, count($values), '?'));
    return [
        'sql' => "($placeholders)",
        'types' => str_repeat($type, count($values)),
        'params' => array_values($values),
    ];
}

/** Is the current session's email in the given allowlist? */
function user_email_in(array $allowlist): bool
{
    $email = $_SESSION['email'] ?? null;
    return $email !== null && in_array($email, $allowlist, true);
}

/** The current user's email from the session, or null if not logged in. */
function current_user_email(): ?string
{
    return $_SESSION['email'] ?? null;
}

/**
 * Guard for endpoints that need to be reachable BOTH by an unattended cron
 * trigger (email2.php, emailmenu.php, emailroti.php) AND by an admin
 * clicking a link in the browser — e.g. a scheduled HTTP hit from a
 * shared-hosting "cron" service that can't hold a login session, but also
 * a manual re-send by an admin.
 *
 * Accepts either:
 *   - a shared-secret token (?token=... or an X-Cron-Token header) matching
 *     the FMB_CRON_SECRET environment variable, for the automated trigger, or
 *   - an active session belonging to an admin/superadmin user.
 *
 * Fails closed: if FMB_CRON_SECRET isn't set, the token path can never
 * succeed, so only an admin session will work until it's configured.
 *
 * IMPORTANT: set FMB_CRON_SECRET on the server and add
 * &token=<that value> to whatever URL your scheduled task/cron hits, or
 * the automated emails will stop firing once this guard is deployed.
 */
function require_cron_or_admin_access(mysqli $link): void
{
    $secret = getenv('FMB_CRON_SECRET');
    $provided = $_GET['token'] ?? ($_SERVER['HTTP_X_CRON_TOKEN'] ?? null);

    if ($secret !== false && $secret !== '' && $provided !== null && hash_equals($secret, (string) $provided)) {
        return;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!empty($_SESSION['fromLogin']) && !empty($_SESSION['email'])) {
        $result = db_query($link, "SELECT role FROM users WHERE email = ?", "s", [$_SESSION['email']]);
        $row = mysqli_fetch_assoc($result);
        if ($row && in_array($row['role'], ['admin', 'superadmin'], true)) {
            return;
        }
    }

    http_response_code(403);
    echo "Forbidden.";
    exit;
}

/**
 * Gmail-only address check. Used by update_details.php, transporter
 * maker forms, etc. — matches the client-side pattern="..." on those forms.
 */
function is_valid_gmail_address(string $email): bool
{
    return (bool) preg_match('/^[a-z0-9._%+\-]+@gmail\.com$/i', $email);
}

/** 10-digit mobile number check. */
function is_valid_mobile(string $mobile): bool
{
    return (bool) preg_match('/^[0-9]{10}$/', $mobile);
}

// --- Centralised nav/feature permission lists ---------------------------
// These were previously copy-pasted as literal in_array(...) arrays
// throughout navbar.php (and would drift out of sync with each other).
// Edit the list here once; every page that checks access uses the same copy.

const THALISEARCH_ACCESS_EMAILS = [
    'mulla.moiz@gmail.com',
    'yusuf4u52@gmail.com',
    'moizagasiyawala@gmail.com',
    'tinwalaabizer@gmail.com',
    'saminabarnagarwala2812@gmail.com',
    'itsammara@gmail.com',
];

const SPECIAL_THALI_ACCESS_EMAILS = [
    'mulla.moiz@gmail.com',
    'yusuf4u52@gmail.com',
    'tinwalaabizer@gmail.com',
    'moizagasiyawala@gmail.com',
    'ahmedi.murtaza@gmail.com',
];

const MENU_MANAGEMENT_ACCESS_EMAILS = [
    'mulla.moiz@gmail.com',
    'yusuf4u52@gmail.com',
    'tinwalaabizer@gmail.com',
    'moizagasiyawala@gmail.com',
    'aliasgaraurangabadwala@gmail.com',
];

const ROTI_MANAGEMENT_ACCESS_EMAILS = [
    'mulla.moiz@gmail.com',
    'yusuf4u52@gmail.com',
    'tinwalaabizer@gmail.com',
    'moizagasiyawala@gmail.com',
    'hussainbarnagarwala14@gmail.com',
    'abbas.saifee5@gmail.com',
    'saminabarnagarwala2812@gmail.com',
    'gheewalamf@gmail.com',
    'zahradhorajiwala0@gmail.com',
];

const TRANSPORTER_MANAGEMENT_ACCESS_EMAILS = [
    'mulla.moiz@gmail.com',
    'yusuf4u52@gmail.com',
    'tinwalaabizer@gmail.com',
    'moizagasiyawala@gmail.com',
    'taherhafiji@gmail.com',
    'saminabarnagarwala2812@gmail.com',
    'itsammara@gmail.com',
];

const EVENT_NOT_REGISTERED_VIEWER_EMAILS = [
    'mulla.moiz@gmail.com',
    'yusuf4u52@gmail.com',
    'moizlife@gmail.com',
];

const DATA_IMPORT_EMAILS = [
    'tinwalaabizer@gmail.com',
    'moizagasiyawala@gmail.com',
];

const SECTOR_IMPORT_EMAILS = [
    'mulla.moiz@gmail.com',
    'moizagasiyawala@gmail.com',
];

const BACKEND_ACCESS_EMAILS = [
    'mulla.moiz@gmail.com',
    'yusuf4u52@gmail.com',
    'moizagasiyawala@gmail.com',
    'tinwalaabizer@gmail.com',
    'itsammara@gmail.com',
];

// --- Email-script (email2.php / emailmenu.php / emailroti.php) recipient lists ---

const DAILY_UPDATE_EMAILS = [
    "kalimimohallapoona@gmail.com",
    "yusuf4u52@gmail.com",
    "mulla.moiz@gmail.com",
    "moizlife@gmail.com",
    "abbas.saifee5@gmail.com",
    "tinwalaabizer@gmail.com",
    "gheewalamf@gmail.com",
    "itsammara@gmail.com",
    "hussainbarnagarwala14@gmail.com",
    "kanchwalaabizer@gmail.com",
    "moula1981sk@gmail.com",
];

const SKIP_NOTICE_EMAILS = [
    "kalimimohallapoona@gmail.com",
    "yusuf4u52@gmail.com",
    "mulla.moiz@gmail.com",
    "moizlife@gmail.com",
    "tinwalaabizer@gmail.com",
];

const MENU_UPDATE_EMAILS = [
    "kalimimohallapoona@gmail.com",
    "yusuf4u52@gmail.com",
    "mulla.moiz@gmail.com",
    "moizlife@gmail.com",
    "tinwalaabizer@gmail.com",
    "itsammara@gmail.com",
    "kanchwalaabizer@gmail.com",
    "moula1981sk@gmail.com",
];

const ROTI_UPDATE_EMAILS = [
    "kalimimohallapoona@gmail.com",
    "yusuf4u52@gmail.com",
    "mulla.moiz@gmail.com",
    "moizlife@gmail.com",
    "abbas.saifee5@gmail.com",
    "tinwalaabizer@gmail.com",
    "gheewalamf@gmail.com",
    "hussainbarnagarwala14@gmail.com",
    "kanchwalaabizer@gmail.com",
];

<?php

/**
 * migrate_menu_json.php
 * ----------------------------------------------------------------------
 * One-time data migration: converts every menu_list.menu_item,
 * user_menu.menu_item, and user_feedmenu.menu_feed value from PHP's
 * serialize() format to JSON, IN PLACE, while the column is still
 * LONGTEXT.
 *
 * Why this is needed: MySQL's native JSON column type validates every
 * value on write. `ALTER TABLE ... MODIFY COLUMN ... JSON` has to rewrite
 * every existing row into the new type, and a PHP-serialized string like
 *   a:5:{s:5:"sabji";a:2:{s:4:"item";s:12:"Gosht Packet";...}}
 * is not valid JSON, so that ALTER TABLE fails outright on any table that
 * still has old rows. This script converts the data first; only after
 * every row is valid JSON does the ALTER TABLE in schema-modernization.sql
 * succeed.
 *
 * Nothing is lost: each value is unserialize()'d back into the exact same
 * PHP array it always represented, then json_encode()'d. The data itself
 * doesn't change shape at all — only its on-disk text format does.
 *
 * SAFE TO RE-RUN: rows that are already valid JSON are left untouched, so
 * you can run this as many times as you like.
 *
 * USAGE
 * -----
 * Dry run (default — reports what WOULD change, writes nothing):
 *   php migrate_menu_json.php
 *   https://yoursite.com/testfmb/users/migrate_menu_json.php   (requires admin login)
 *
 * Actually apply the changes:
 *   php migrate_menu_json.php --apply
 *   https://yoursite.com/testfmb/users/migrate_menu_json.php?apply=1
 *
 * RECOMMENDED ORDER
 * ------------------
 * 1. Take a database backup.
 * 2. Deploy the upgraded PHP code (so helpers.php's decode_menu_item()
 *    exists — this script reuses it).
 * 3. Run this script in dry-run mode first, review the report.
 * 4. Run this script with --apply.
 * 5. Only then run the `MODIFY COLUMN ... JSON` statements in
 *    schema-modernization.sql.
 * ----------------------------------------------------------------------
 */

$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    $apply = in_array('--apply', $argv, true);
} else {
    include __DIR__ . '/connection.php';
    include __DIR__ . '/_authCheck.php'; // web access requires an admin-tier login
    $apply = isset($_GET['apply']) && $_GET['apply'] === '1';
    header('Content-Type: text/plain; charset=utf-8');
}

if (!isset($link)) {
    include __DIR__ . '/connection.php';
}
include_once __DIR__ . '/helpers.php';

/**
 * Migrate one (table, id_column, data_column) triple.
 * Processes in batches to avoid loading a huge table into memory at once.
 */
function migrate_column(mysqli $link, string $table, string $idColumn, string $dataColumn, bool $apply): array
{
    $stats = ['total' => 0, 'already_json' => 0, 'converted' => 0, 'empty' => 0, 'failed' => []];
    $batchSize = 500;
    $lastId = 0;

    while (true) {
        $stmt = mysqli_prepare(
            $link,
            "SELECT `$idColumn`, `$dataColumn` FROM `$table` WHERE `$idColumn` > ? ORDER BY `$idColumn` ASC LIMIT ?"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $lastId, $batchSize);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        if (count($rows) === 0) {
            break;
        }

        foreach ($rows as $row) {
            $id = (int) $row[$idColumn];
            $lastId = $id;
            $raw = $row[$dataColumn];
            $stats['total']++;

            if ($raw === null || $raw === '') {
                $stats['empty']++;
                continue;
            }

            // Already valid JSON (e.g. a row already migrated, or written
            // by the already-deployed upgraded code) — leave it alone.
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $stats['already_json']++;
                continue;
            }

            // Attempt to unserialize the legacy PHP format.
            $legacy = @unserialize($raw);
            if (!is_array($legacy)) {
                // Neither valid JSON nor valid PHP-serialized array —
                // flag for manual review rather than silently discarding it.
                $stats['failed'][] = $id;
                continue;
            }

            $json = json_encode($legacy, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                $stats['failed'][] = $id;
                continue;
            }

            $stats['converted']++;

            if ($apply) {
                $update = mysqli_prepare($link, "UPDATE `$table` SET `$dataColumn` = ? WHERE `$idColumn` = ?");
                mysqli_stmt_bind_param($update, 'si', $json, $id);
                mysqli_stmt_execute($update);
                mysqli_stmt_close($update);
            }
        }
    }

    return $stats;
}

$targets = [
    ['table' => 'menu_list', 'id' => 'id', 'column' => 'menu_item'],
    ['table' => 'user_menu', 'id' => 'id', 'column' => 'menu_item'],
    ['table' => 'user_feedmenu', 'id' => 'id', 'column' => 'menu_feed'],
];

echo "menu_item / menu_feed migration — " . ($apply ? "APPLYING CHANGES" : "DRY RUN (add --apply / ?apply=1 to write)") . "\n";
echo str_repeat('-', 70) . "\n";

$anyFailed = false;

foreach ($targets as $target) {
    $stats = migrate_column($link, $target['table'], $target['id'], $target['column'], $apply);

    echo "\n{$target['table']}.{$target['column']}\n";
    echo "  total rows:      {$stats['total']}\n";
    echo "  already JSON:    {$stats['already_json']}\n";
    echo "  converted:       {$stats['converted']}" . ($apply ? '' : ' (would convert)') . "\n";
    echo "  empty/null:      {$stats['empty']}\n";
    echo "  FAILED to parse: " . count($stats['failed']) . "\n";

    if (!empty($stats['failed'])) {
        $anyFailed = true;
        echo "    -> ids needing manual review: " . implode(', ', $stats['failed']) . "\n";
    }
}

echo "\n" . str_repeat('-', 70) . "\n";

if ($anyFailed) {
    echo "Some rows could not be parsed as either JSON or PHP-serialized data.\n";
    echo "Look at those rows manually before running the JSON column ALTER TABLE —\n";
    echo "any row that's neither valid JSON nor valid serialized data will make\n";
    echo "the ALTER TABLE fail on that row.\n";
} elseif (!$apply) {
    echo "Dry run complete, nothing was written. Re-run with --apply (CLI) or\n";
    echo "?apply=1 (browser) to actually convert the data.\n";
} else {
    echo "Done. All rows are now valid JSON. You can now run the\n";
    echo "MODIFY COLUMN ... JSON statements in schema-modernization.sql.\n";
}
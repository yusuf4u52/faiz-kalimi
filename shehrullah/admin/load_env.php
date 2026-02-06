<?php
/**
 * Load .env file into getenv() / $_ENV (simple parser, no dependency).
 * Call once at bootstrap with path to .env file.
 */
function load_env_file($path) {
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\"'");
        putenv("$key=$val");
        $_ENV[$key] = $val;
    }
}

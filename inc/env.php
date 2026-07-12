<?php
/*
================================================================================
FILENAME     : inc/env.php
DESCRIPTION  : Native PHP .env loader — no external dependencies required.
               Parses KEY=VALUE pairs and populates $_ENV, $_SERVER, and
               getenv() / putenv() for the current process.
AUTHOR       : CAHYA DSN
UPDATED DATE : 2026-07-12
================================================================================
*/

/**
 * Load a .env file and populate environment variables.
 *
 * Supported syntax:
 *   KEY=value
 *   KEY="value with spaces"
 *   KEY='value with spaces'
 *   # comment lines (ignored)
 *   blank lines (ignored)
 *
 * Already-set variables are NOT overwritten (safe for production
 * where real env vars take precedence over the file).
 *
 * @param  string $path  Full path to the .env file.
 * @throws RuntimeException  If the file cannot be read.
 */
function load_env(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException(".env file not found or not readable: {$path}");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and blank lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Must contain '='
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key   = trim($key);
        $value = trim($value);

        // Strip optional inline comments (e.g. VALUE=foo  # comment)
        if (str_contains($value, ' #')) {
            $value = trim(explode(' #', $value, 2)[0]);
        }

        // Strip surrounding quotes (single or double)
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Skip keys that are already defined in the environment
        if (getenv($key) !== false) {
            continue;
        }

        putenv("{$key}={$value}");
        $_ENV[$key]    = $value;
        $_SERVER[$key] = $value;
    }
}

/**
 * Retrieve an environment variable with an optional default fallback.
 *
 * Usage:
 *   env('DB_HOST')           // returns value or null
 *   env('DB_PORT', 3306)     // returns value or 3306
 *
 * @param  string $key
 * @param  mixed  $default
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    // Cast common boolean/null string representations
    return match (strtolower((string) $value)) {
        'true',  '(true)'  => true,
        'false', '(false)' => false,
        'null',  '(null)'  => null,
        'empty', '(empty)' => '',
        default            => $value,
    };
}

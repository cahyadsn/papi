<?php
/*
================================================================================
FILENAME     : tests/EnvTest.php
DESCRIPTION  : Unit tests for load_env() and env() in inc/env.php.
               All tests are self-contained — temp .env files are written to
               sys_get_temp_dir() and cleaned up after each group.
AUTHOR       : CAHYA DSN
UPDATED DATE : 2026-07-15
================================================================================
*/

require_once __DIR__ . '/TestRunner.php';
require_once __DIR__ . '/../inc/env.php';

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Write an ad-hoc .env file to a temp path and return the path.
 */
function make_temp_env(string $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'papi_env_test_');
    file_put_contents($path, $content);
    return $path;
}

/**
 * Remove a key from all three env stores so load_env() treats it as fresh.
 */
function unset_env(string ...$keys): void
{
    foreach ($keys as $key) {
        putenv($key);             // removes from getenv() store
        unset($_ENV[$key], $_SERVER[$key]);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
//  SUITE: load_env()
// ─────────────────────────────────────────────────────────────────────────────

$t = new TestRunner('load_env()');
echo PHP_EOL . "  load_env()" . PHP_EOL;

// ── 1. Throws on missing file ─────────────────────────────────────────────────
$t->run('throws RuntimeException when file does not exist', function () use ($t) {
    $t->assertThrows(
        RuntimeException::class,
        fn() => load_env('/nonexistent/path/.env')
    );
});

// ── 2. Basic KEY=value parsing ────────────────────────────────────────────────
$t->run('parses simple KEY=value pairs', function () use ($t) {
    unset_env('TEST_HOST', 'TEST_PORT');
    $path = make_temp_env("TEST_HOST=localhost\nTEST_PORT=3306\n");
    load_env($path);
    unlink($path);

    $t->assertEquals('localhost', getenv('TEST_HOST'), 'getenv()');
    $t->assertEquals('3306',      getenv('TEST_PORT'), 'getenv()');
    $t->assertEquals('localhost', $_ENV['TEST_HOST'],  '$_ENV');
    unset_env('TEST_HOST', 'TEST_PORT');
});

// ── 3. Double-quoted values ───────────────────────────────────────────────────
$t->run('strips surrounding double quotes from values', function () use ($t) {
    unset_env('TEST_Q_DOUBLE');
    $path = make_temp_env('TEST_Q_DOUBLE="hello world"' . "\n");
    load_env($path);
    unlink($path);

    $t->assertEquals('hello world', $_ENV['TEST_Q_DOUBLE']);
    unset_env('TEST_Q_DOUBLE');
});

// ── 4. Single-quoted values ───────────────────────────────────────────────────
$t->run('strips surrounding single quotes from values', function () use ($t) {
    unset_env('TEST_Q_SINGLE');
    $path = make_temp_env("TEST_Q_SINGLE='hello world'\n");
    load_env($path);
    unlink($path);

    $t->assertEquals('hello world', $_ENV['TEST_Q_SINGLE']);
    unset_env('TEST_Q_SINGLE');
});

// ── 5. Inline comments are stripped ──────────────────────────────────────────
$t->run('strips inline comments after value', function () use ($t) {
    unset_env('TEST_COMMENT');
    $path = make_temp_env("TEST_COMMENT=myvalue  # this is a comment\n");
    load_env($path);
    unlink($path);

    $t->assertEquals('myvalue', $_ENV['TEST_COMMENT']);
    unset_env('TEST_COMMENT');
});

// ── 6. Full-line comments are ignored ────────────────────────────────────────
$t->run('ignores full-line # comments', function () use ($t) {
    unset_env('TEST_AFTER_COMMENT');
    $path = make_temp_env("# this is a comment\nTEST_AFTER_COMMENT=yes\n");
    load_env($path);
    unlink($path);

    $t->assertEquals('yes', $_ENV['TEST_AFTER_COMMENT']);
    unset_env('TEST_AFTER_COMMENT');
});

// ── 7. Blank lines are skipped ────────────────────────────────────────────────
$t->run('ignores blank lines', function () use ($t) {
    unset_env('TEST_BLANK');
    $path = make_temp_env("\n\n\nTEST_BLANK=ok\n\n");
    load_env($path);
    unlink($path);

    $t->assertEquals('ok', $_ENV['TEST_BLANK']);
    unset_env('TEST_BLANK');
});

// ── 8. Lines without '=' are skipped ─────────────────────────────────────────
$t->run('ignores malformed lines without "="', function () use ($t) {
    unset_env('NOEQUALS');
    $path = make_temp_env("NOEQUALS\n");
    load_env($path);
    unlink($path);

    $t->assertFalse(isset($_ENV['NOEQUALS']));
});

// ── 9. Pre-set env vars are NOT overwritten ────────────────────────────────────
$t->run('does not overwrite already-set env vars', function () use ($t) {
    // Pre-set via putenv (simulates a real system env var)
    putenv('TEST_OVERWRITE=original');
    $_ENV['TEST_OVERWRITE'] = 'original';

    $path = make_temp_env("TEST_OVERWRITE=should_not_win\n");
    load_env($path);
    unlink($path);

    $t->assertEquals('original', getenv('TEST_OVERWRITE'), 'must keep original value');
    unset_env('TEST_OVERWRITE');
});

// ── 10. Value with '=' in it (explode limit 2) ────────────────────────────────
$t->run('handles values that contain "=" characters', function () use ($t) {
    unset_env('TEST_EQUALS_IN_VALUE');
    $path = make_temp_env("TEST_EQUALS_IN_VALUE=base64==\n");
    load_env($path);
    unlink($path);

    $t->assertEquals('base64==', $_ENV['TEST_EQUALS_IN_VALUE']);
    unset_env('TEST_EQUALS_IN_VALUE');
});

$failures = $t->summary();

// ─────────────────────────────────────────────────────────────────────────────
//  SUITE: env()
// ─────────────────────────────────────────────────────────────────────────────

$t2 = new TestRunner('env()');
echo "  env()" . PHP_EOL;

// ── 1. Returns value from $_ENV ───────────────────────────────────────────────
$t2->run('returns the correct value from $_ENV', function () use ($t2) {
    $_ENV['ENV_TEST_KEY'] = 'env_value';
    $t2->assertEquals('env_value', env('ENV_TEST_KEY'));
    unset($_ENV['ENV_TEST_KEY']);
});

// ── 2. Returns default when key is absent ─────────────────────────────────────
$t2->run('returns default when key is not set', function () use ($t2) {
    unset_env('ENV_MISSING_KEY');
    $t2->assertEquals('fallback', env('ENV_MISSING_KEY', 'fallback'));
});

// ── 3. Returns null by default when key is absent ─────────────────────────────
$t2->run('returns null by default for missing key', function () use ($t2) {
    unset_env('ENV_MISSING_NULL');
    $t2->assertEquals(null, env('ENV_MISSING_NULL'));
});

// ── 4. Casts "true" string → bool true ───────────────────────────────────────
$t2->run('casts string "true" to boolean true', function () use ($t2) {
    $_ENV['ENV_BOOL_TRUE'] = 'true';
    $t2->assertEquals(true, env('ENV_BOOL_TRUE'));
    $t2->assertTrue(is_bool(env('ENV_BOOL_TRUE')));
    unset($_ENV['ENV_BOOL_TRUE']);
});

// ── 5. Casts "(true)" string → bool true ─────────────────────────────────────
$t2->run('casts string "(true)" to boolean true', function () use ($t2) {
    $_ENV['ENV_BOOL_TRUE2'] = '(true)';
    $t2->assertEquals(true, env('ENV_BOOL_TRUE2'));
    unset($_ENV['ENV_BOOL_TRUE2']);
});

// ── 6. Casts "false" string → bool false ─────────────────────────────────────
$t2->run('casts string "false" to boolean false', function () use ($t2) {
    $_ENV['ENV_BOOL_FALSE'] = 'false';
    $t2->assertEquals(false, env('ENV_BOOL_FALSE'));
    $t2->assertTrue(is_bool(env('ENV_BOOL_FALSE')));
    unset($_ENV['ENV_BOOL_FALSE']);
});

// ── 7. Casts "(false)" string → bool false ───────────────────────────────────
$t2->run('casts string "(false)" to boolean false', function () use ($t2) {
    $_ENV['ENV_BOOL_FALSE2'] = '(false)';
    $t2->assertEquals(false, env('ENV_BOOL_FALSE2'));
    unset($_ENV['ENV_BOOL_FALSE2']);
});

// ── 8. Casts "null" string → null ────────────────────────────────────────────
$t2->run('casts string "null" to null', function () use ($t2) {
    $_ENV['ENV_NULL'] = 'null';
    $t2->assertEquals(null, env('ENV_NULL'));
    unset($_ENV['ENV_NULL']);
});

// ── 9. Casts "(null)" string → null ──────────────────────────────────────────
$t2->run('casts string "(null)" to null', function () use ($t2) {
    $_ENV['ENV_NULL2'] = '(null)';
    $t2->assertEquals(null, env('ENV_NULL2'));
    unset($_ENV['ENV_NULL2']);
});

// ── 10. Casts "empty" string → '' ────────────────────────────────────────────
$t2->run('casts string "empty" to empty string', function () use ($t2) {
    $_ENV['ENV_EMPTY'] = 'empty';
    $t2->assertEquals('', env('ENV_EMPTY'));
    unset($_ENV['ENV_EMPTY']);
});

// ── 11. Case-insensitive cast matching ───────────────────────────────────────
$t2->run('cast matching is case-insensitive (TRUE → bool true)', function () use ($t2) {
    $_ENV['ENV_CASE'] = 'TRUE';
    $t2->assertEquals(true, env('ENV_CASE'));
    unset($_ENV['ENV_CASE']);
});

// ── 12. Falls back to getenv() when not in $_ENV ─────────────────────────────
$t2->run('falls back to getenv() when key is absent from $_ENV', function () use ($t2) {
    unset($_ENV['ENV_GETENV_KEY']);
    putenv('ENV_GETENV_KEY=via_putenv');
    $t2->assertEquals('via_putenv', env('ENV_GETENV_KEY'));
    putenv('ENV_GETENV_KEY'); // unset
});

$failures += $t2->summary();

// ── Expose total failure count for run.php ────────────────────────────────────
return $failures;

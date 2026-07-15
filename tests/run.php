<?php
/*
================================================================================
FILENAME     : tests/run.php
DESCRIPTION  : Entry point for the PAPI Kostick native-PHP test suite.
               Requires every test file in order and reports a combined summary.

USAGE (from the project root):
    php tests/run.php

EXIT CODE:
    0  — all tests passed
    1  — one or more tests failed
================================================================================
*/

// ── Sanity: must be run from CLI ──────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Test suite must be run from the command line.');
}

// ── ANSI helper (colour-safe separator line) ──────────────────────────────────
$color = (PHP_OS_FAMILY !== 'Windows') && function_exists('posix_isatty') && posix_isatty(STDOUT);
$sep   = str_repeat('─', 60);
$bold  = fn(string $s) => $color ? "\033[1m{$s}\033[0m"  : $s;
$green = fn(string $s) => $color ? "\033[32m{$s}\033[0m" : $s;
$red   = fn(string $s) => $color ? "\033[31m{$s}\033[0m" : $s;

// ── Header ────────────────────────────────────────────────────────────────────
echo PHP_EOL;
echo $bold('PAPI Kostick — Native PHP Test Suite') . PHP_EOL;
echo $sep . PHP_EOL;
echo 'PHP ' . PHP_VERSION . ' · ' . date('Y-m-d H:i:s') . PHP_EOL;
echo $sep . PHP_EOL;

// ── Run each test file ────────────────────────────────────────────────────────
//
// Each file is expected to:
//   1. require TestRunner.php and its own dependencies
//   2. return an integer — the count of failures in that file
//
// If a file does not return a value, we treat it as 0 failures.
//

$testFiles = [
    __DIR__ . '/EnvTest.php',
    __DIR__ . '/ScoreTest.php',
];

$totalFailures = 0;

foreach ($testFiles as $file) {
    if (!is_file($file)) {
        echo $red('  MISSING: ' . $file) . PHP_EOL;
        $totalFailures++;
        continue;
    }

    // Each file returns the number of failures it recorded
    $result         = require $file;
    $totalFailures += (int) ($result ?? 0);
}

// ── Grand total ───────────────────────────────────────────────────────────────
echo $sep . PHP_EOL;

if ($totalFailures === 0) {
    echo $bold($green('  ALL TESTS PASSED')) . PHP_EOL;
} else {
    echo $bold($red("  {$totalFailures} TEST(S) FAILED")) . PHP_EOL;
}

echo $sep . PHP_EOL . PHP_EOL;

exit($totalFailures > 0 ? 1 : 0);

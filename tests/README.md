# PAPI Kostick — Unit Tests

Native PHP test suite for the PAPI Kostick application. No Composer, no external frameworks — only plain PHP 8.0+.

---

## Table of Contents

- [Requirements](#requirements)
- [Project Structure](#project-structure)
- [How to Run](#how-to-run)
- [Exit Codes](#exit-codes)
- [Test Files](#test-files)
  - [EnvTest.php](#envtestphp)
  - [ScoreTest.php](#scoretestphp)
- [TestRunner API](#testrunner-api)
- [How Tests Are Organised](#how-tests-are-organised)
- [Adding New Tests](#adding-new-tests)
- [Design Decisions](#design-decisions)

---

## Requirements

| Requirement | Minimum |
|---|---|
| PHP | 8.0 (uses `match`, `mixed` type, named args) |
| Extensions | none beyond standard CLI |
| Composer | **not required** |
| Database | **not required** — all data is mocked |

---

## Project Structure

```
tests/
├── README.md          ← you are here
├── run.php            ← entry point, runs all test files
├── TestRunner.php     ← minimal assertion + reporting framework
├── EnvTest.php        ← tests for inc/env.php  (load_env, env)
└── ScoreTest.php      ← tests for inc/score.php (aggregate_scores, find_rule, build_results)

inc/
└── score.php          ← pure scoring logic extracted from papi_process.php (tested by ScoreTest.php)
```

---

## How to Run

Run from the **project root** (the `papi/` folder):

```bash
php tests/run.php
```

Expected output when all tests pass:

```
PAPI Kostick — Native PHP Test Suite
────────────────────────────────────────────────────────────
PHP 8.3.x · 2026-07-15 09:05:45
────────────────────────────────────────────────────────────

  load_env()
  ✓ throws RuntimeException when file does not exist
  ✓ parses simple KEY=value pairs
  ...

  [load_env()] Results: 10 passed, 0 failed / 10 total

  ...

────────────────────────────────────────────────────────────
  ALL TESTS PASSED
────────────────────────────────────────────────────────────
```

To run only a single test file directly:

```bash
php tests/EnvTest.php
php tests/ScoreTest.php
```

> Note: ANSI colours are enabled on Linux/macOS terminals and disabled automatically on Windows and non-TTY outputs (e.g. CI log files).

---

## Exit Codes

| Code | Meaning |
|---|---|
| `0` | All tests passed |
| `1` | One or more tests failed |

This makes the suite compatible with CI/CD pipelines (GitHub Actions, GitLab CI, Jenkins, etc.).

---

## Test Files

### EnvTest.php

Tests the two public functions in `inc/env.php`.

#### Suite: `load_env()`

Covers file parsing behaviour. Each test writes a temporary `.env` file to `sys_get_temp_dir()`, calls `load_env()`, then cleans up. Keys are unset from all three PHP env stores (`putenv`, `$_ENV`, `$_SERVER`) before and after each test to prevent state leakage between cases.

| # | Test | What it verifies |
|---|---|---|
| 1 | throws RuntimeException when file does not exist | Missing file path raises `RuntimeException` |
| 2 | parses simple KEY=value pairs | `TEST_HOST=localhost` lands in `getenv()` and `$_ENV` |
| 3 | strips surrounding double quotes from values | `KEY="hello world"` → `hello world` |
| 4 | strips surrounding single quotes from values | `KEY='hello world'` → `hello world` |
| 5 | strips inline comments after value | `KEY=myvalue  # comment` → `myvalue` |
| 6 | ignores full-line # comments | Lines starting with `#` are skipped entirely |
| 7 | ignores blank lines | Empty lines do not crash or produce entries |
| 8 | ignores malformed lines without `=` | Lines like `NOEQUALS` are silently skipped |
| 9 | does not overwrite already-set env vars | A pre-existing `putenv` value is preserved |
| 10 | handles values that contain `=` characters | `KEY=base64==` parses correctly (uses `explode('=', $line, 2)`) |

#### Suite: `env()`

Covers the helper that reads and type-casts environment values.

| # | Test | What it verifies |
|---|---|---|
| 1 | returns the correct value from `$_ENV` | Basic read from `$_ENV` superglobal |
| 2 | returns default when key is not set | `env('MISSING', 'fallback')` → `'fallback'` |
| 3 | returns null by default for missing key | `env('MISSING')` → `null` |
| 4 | casts string `"true"` to boolean `true` | Type is `bool`, value is `true` |
| 5 | casts string `"(true)"` to boolean `true` | Parenthesised form is also supported |
| 6 | casts string `"false"` to boolean `false` | Type is `bool`, value is `false` |
| 7 | casts string `"(false)"` to boolean `false` | Parenthesised form is also supported |
| 8 | casts string `"null"` to `null` | Returns PHP `null`, not the string |
| 9 | casts string `"(null)"` to `null` | Parenthesised form is also supported |
| 10 | casts string `"empty"` to `''` | Returns empty string `''` |
| 11 | cast matching is case-insensitive (`TRUE` → `true`) | `strtolower()` normalisation works |
| 12 | falls back to `getenv()` when key is absent from `$_ENV` | Values set only via `putenv()` are still readable |

**Total: 22 tests**

---

### ScoreTest.php

Tests the three pure functions in `inc/score.php`. No database connection is required — rule objects are constructed inline using the `make_rule()` / `index_rules()` helpers defined at the top of the file.

#### Suite: `aggregate_scores()`

Verifies that raw POST answer arrays (role-id values) are tallied correctly into a score map.

| # | Test | What it verifies |
|---|---|---|
| 1 | returns empty array for empty input | `aggregate_scores([])` → `[]` |
| 2 | counts a single answer as score 1 | One answer for role 3 → `[3 => 1]` |
| 3 | accumulates multiple answers for the same role | Role 3 chosen 3 times → `[3 => 3]` |
| 4 | tracks multiple roles independently | Roles 1, 2, 3 each counted separately |
| 5 | output is sorted by role_id (`ksort`) | Keys are in ascending integer order |
| 6 | handles string role_id values (cast to int) | `'07'` and `'7'` are treated as the same role |
| 7 | handles 90 answers — total vote count equals 90 | Full-scale simulation: 20 roles, 90 total answers |

#### Suite: `find_rule()`

Verifies that the correct scoring band rule is returned for a given role + score pair.

| # | Test | What it verifies |
|---|---|---|
| 1 | returns null for an unknown role_id | Role 99 (not in rules) → `null` |
| 2 | matches lower band: score=0 | Floor of lower band (0–2) |
| 3 | matches lower band: score=2 | Ceiling of lower band |
| 4 | matches middle band: score=3 | Floor of middle band (3–5) |
| 5 | matches middle band: score=5 | Ceiling of middle band |
| 6 | matches higher band: score=6 | Floor of higher band (6–9) |
| 7 | matches higher band: score=9 | Ceiling of higher band |
| 8 | returns null when score exceeds all rule ranges | Score 100 → `null` |
| 9 | returns role-specific rule (role 2 middle band) | Checks `role`, `aspect`, and `interprestation` fields |
| 10 | boundary: score=2 is in lower band (not middle) | Confirms score 2 does not bleed into middle |
| 11 | boundary: score=3 is in middle band (not lower) | Confirms score 3 does not stay in lower |

#### Suite: `build_results()`

Verifies the final assembly of results from a score map and rule index.

| # | Test | What it verifies |
|---|---|---|
| 1 | returns empty array for empty scores | `build_results([], ...)` → `[]` |
| 2 | maps a single score to the correct interpretation | Checks all four result fields: `interprestation`, `role`, `aspect`, `score` |
| 3 | resolves multiple roles to correct bands | Two roles, two different bands |
| 4 | skips roles with no matching rule (score out of range) | Score 99 → result silently omitted |
| 5 | skips unknown role_ids gracefully | Role 999 → result silently omitted |
| 6 | preserves role_id order from sorted scores | Results are in `ksort` order of role ids |
| 7 | full pipeline: answers → scores → results | End-to-end: raw `$_POST`-style array → final interpretations |
| 8 | score of 0 resolves to lower band correctly | Zero is a valid score (not treated as empty/missing) |

**Total: 26 tests**

---

## TestRunner API

`TestRunner` is a minimal class in `tests/TestRunner.php`. It has no dependencies.

### Constructor

```php
$t = new TestRunner('Suite Name');
```

### Running a test

```php
$t->run('description of what is tested', function () use ($t) {
    // assertions here
});
```

- Pass: the description is printed with a `✓` prefix.
- Fail: the description is printed with a `✗` prefix and the failure message is shown immediately below.
- Unexpected exceptions are caught and treated as failures.

### Assertions

| Method | Description |
|---|---|
| `assertEquals($expected, $actual, $label?)` | Strict equality (`===`). Fails if values differ in type or value. |
| `assertNotEquals($unexpected, $actual, $label?)` | Strict inequality (`!==`). Fails if values are identical. |
| `assertTrue($expr, $label?)` | Fails if `$expr` is falsy. |
| `assertFalse($expr, $label?)` | Fails if `$expr` is truthy. |
| `assertThrows($class, callable $fn, $label?)` | Fails if `$fn` does not throw an instance of `$class`. |
| `assertContains($needle, $haystack, $label?)` | Works on both strings (`str_contains`) and arrays (`in_array`, strict). |

All assertions accept an optional `$label` string that is prepended to the failure message for easier diagnosis.

### Summary

```php
$failures = $t->summary();
```

Prints the pass/fail count for the suite and returns the number of failures as an integer. Pass this return value back up to `run.php` to contribute to the global exit code.

---

## How Tests Are Organised

Each test file follows this pattern:

```php
require_once __DIR__ . '/TestRunner.php';
require_once __DIR__ . '/../inc/some_file.php';

$t = new TestRunner('Function Name');
echo PHP_EOL . "  Function Name" . PHP_EOL;

$t->run('what it should do', function () use ($t) {
    $t->assertEquals('expected', some_function('input'));
});

// ... more tests ...

$failures = $t->summary();
return $failures; // ← consumed by run.php
```

`run.php` collects the `return` value from each file and uses `array_sum` of all failure counts as its exit code basis.

---

## Adding New Tests

### Add a test case to an existing file

Open the relevant test file (e.g. `ScoreTest.php`) and add a new `$t->run(...)` block inside the appropriate suite section.

### Add a new test file

1. Create `tests/MyFeatureTest.php` following the pattern above.
2. Add the full path to the `$testFiles` array in `tests/run.php`:

```php
$testFiles = [
    __DIR__ . '/EnvTest.php',
    __DIR__ . '/ScoreTest.php',
    __DIR__ . '/MyFeatureTest.php', // ← add here
];
```

3. Run `php tests/run.php` to confirm it is picked up.

### Add a new assertion method to TestRunner

Open `tests/TestRunner.php` and add a new `public function assertXxx(...)` method that throws `AssertionError` on failure. Follow the existing pattern — use `$this->export()` to format values in the error message.

---

## Design Decisions

**No Composer / no PHPUnit** — the project explicitly has no Composer dependency. The test runner is self-contained in a single ~200-line file.

**`inc/score.php` extraction** — `papi_process.php` mixes scoring logic with HTML output, making it untestable directly. The pure logic was extracted into `inc/score.php` with no side effects. `papi_process.php` can `require` this file to reuse the same functions.

**Temp files for `.env` tests** — `load_env()` reads from disk. Rather than mocking the filesystem, tests write real temp files via `tempnam()` and `file_put_contents()`, then delete them with `unlink()`. This keeps tests realistic while remaining fully isolated.

**Explicit env cleanup** — `putenv($key)` (no `=`) removes a key from PHP's env store. Tests call `unset_env()` before and after each case to prevent one test's side effects from influencing the next.

**Return value protocol** — each test file `return`s its failure count as an integer. `run.php` sums these values to determine the final exit code. Files that do not return a value are treated as `0` failures.

**No database** — `ScoreTest.php` constructs rule objects inline using plain `(object)[...]` casts, mirroring exactly the `stdClass` objects that `papi_process.php` receives from `fetch_object()`. This means the scoring logic is tested against realistic data without requiring a live MySQL connection.

<?php
/*
================================================================================
FILENAME     : tests/TestRunner.php
DESCRIPTION  : Minimal native-PHP assertion and reporting framework.
               No Composer, no external dependencies.
AUTHOR       : CAHYA DSN
UPDATED DATE : 2026-07-15
================================================================================

Usage:
  $t = new TestRunner('Suite Name');
  $t->run('test description', function() use ($t) {
      $t->assertEquals(expected, actual, 'optional label');
      $t->assertTrue(expr, 'optional label');
      ...
  });
  $t->summary();
*/

class TestRunner
{
    private string $suite;
    private int    $passed  = 0;
    private int    $failed  = 0;
    private array  $failures = [];

    // ANSI colour codes (disabled automatically when not a TTY / Windows)
    private bool $color;

    public function __construct(string $suite)
    {
        $this->suite = $suite;
        $this->color = (PHP_OS_FAMILY !== 'Windows') && function_exists('posix_isatty') && posix_isatty(STDOUT);
    }

    // ── Styling helpers ──────────────────────────────────────────────────────

    private function green(string $s): string { return $this->color ? "\033[32m{$s}\033[0m" : $s; }
    private function red(string $s):   string { return $this->color ? "\033[31m{$s}\033[0m" : $s; }
    private function yellow(string $s):string { return $this->color ? "\033[33m{$s}\033[0m" : $s; }
    private function bold(string $s):  string { return $this->color ? "\033[1m{$s}\033[0m"  : $s; }

    // ── Test runner ──────────────────────────────────────────────────────────

    /**
     * Register and immediately execute a single test case.
     *
     * @param string   $description Human-readable test name.
     * @param callable $fn          Test body — receives no arguments.
     */
    public function run(string $description, callable $fn): void
    {
        $before = $this->failed;
        try {
            $fn();
        } catch (AssertionError $e) {
            $this->recordFailure($description, $e->getMessage());
        } catch (Throwable $e) {
            $this->recordFailure($description, 'Unexpected ' . get_class($e) . ': ' . $e->getMessage());
        }

        if ($this->failed === $before) {
            $this->passed++;
            echo '  ' . $this->green('✓') . ' ' . $description . PHP_EOL;
        }
    }

    // ── Assertions ───────────────────────────────────────────────────────────

    /**
     * Assert strict equality (===).
     */
    public function assertEquals(mixed $expected, mixed $actual, string $label = ''): void
    {
        if ($expected !== $actual) {
            $tag = $label ? "[{$label}] " : '';
            throw new AssertionError(
                "{$tag}Expected " . $this->export($expected) .
                ', got '          . $this->export($actual)
            );
        }
    }

    /**
     * Assert that two values are NOT strictly equal.
     */
    public function assertNotEquals(mixed $unexpected, mixed $actual, string $label = ''): void
    {
        if ($unexpected === $actual) {
            $tag = $label ? "[{$label}] " : '';
            throw new AssertionError(
                "{$tag}Expected value to differ from " . $this->export($unexpected)
            );
        }
    }

    /**
     * Assert that an expression is truthy.
     */
    public function assertTrue(mixed $expr, string $label = ''): void
    {
        if (!$expr) {
            $tag = $label ? "[{$label}] " : '';
            throw new AssertionError("{$tag}Expected true, got " . $this->export($expr));
        }
    }

    /**
     * Assert that an expression is falsy.
     */
    public function assertFalse(mixed $expr, string $label = ''): void
    {
        if ($expr) {
            $tag = $label ? "[{$label}] " : '';
            throw new AssertionError("{$tag}Expected false, got " . $this->export($expr));
        }
    }

    /**
     * Assert that a callable throws an exception of the given class.
     *
     * @param class-string $exceptionClass
     */
    public function assertThrows(string $exceptionClass, callable $fn, string $label = ''): void
    {
        $tag = $label ? "[{$label}] " : '';
        try {
            $fn();
        } catch (Throwable $e) {
            if (!($e instanceof $exceptionClass)) {
                throw new AssertionError(
                    "{$tag}Expected {$exceptionClass}, got " . get_class($e) . ': ' . $e->getMessage()
                );
            }
            return; // ✓ expected exception was thrown
        }
        throw new AssertionError("{$tag}Expected {$exceptionClass} to be thrown, but nothing was thrown");
    }

    /**
     * Assert that $haystack contains $needle (works for strings and arrays).
     */
    public function assertContains(mixed $needle, mixed $haystack, string $label = ''): void
    {
        $tag = $label ? "[{$label}] " : '';
        if (is_string($haystack) && is_string($needle)) {
            if (!str_contains($haystack, $needle)) {
                throw new AssertionError("{$tag}" . $this->export($needle) . ' not found in string');
            }
        } elseif (is_array($haystack)) {
            if (!in_array($needle, $haystack, true)) {
                throw new AssertionError("{$tag}" . $this->export($needle) . ' not found in array');
            }
        } else {
            throw new AssertionError("{$tag}assertContains requires a string or array haystack");
        }
    }

    // ── Results ──────────────────────────────────────────────────────────────

    /**
     * Print a summary line.  Returns the number of failed tests (useful as exit code).
     */
    public function summary(): int
    {
        $total = $this->passed + $this->failed;
        echo PHP_EOL;
        echo $this->bold("  [{$this->suite}] Results: ") .
             $this->green("{$this->passed} passed") . ', ' .
             ($this->failed > 0 ? $this->red("{$this->failed} failed") : '0 failed') .
             " / {$total} total" . PHP_EOL;

        if ($this->failures) {
            echo PHP_EOL . $this->red('  Failures:') . PHP_EOL;
            foreach ($this->failures as $i => [$desc, $msg]) {
                echo '  ' . ($i + 1) . ". {$desc}" . PHP_EOL;
                echo '     ' . $this->yellow($msg) . PHP_EOL;
            }
        }

        echo PHP_EOL;
        return $this->failed;
    }

    // ── Internal helpers ─────────────────────────────────────────────────────

    private function recordFailure(string $description, string $message): void
    {
        $this->failed++;
        $this->failures[] = [$description, $message];
        echo '  ' . $this->red('✗') . ' ' . $description . PHP_EOL;
        echo '    ' . $this->yellow($message) . PHP_EOL;
    }

    private function export(mixed $value): string
    {
        if ($value === null)  return 'null';
        if ($value === true)  return 'true';
        if ($value === false) return 'false';
        if (is_string($value)) return '"' . addslashes($value) . '"';
        if (is_array($value))  return 'array(' . count($value) . ')';
        return (string) $value;
    }
}

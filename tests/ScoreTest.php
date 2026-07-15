<?php
/*
================================================================================
FILENAME     : tests/ScoreTest.php
DESCRIPTION  : Unit tests for scoring functions in inc/score.php.
               Tests cover aggregate_scores(), find_rule(), and build_results().
               No database connection is required — all rule data is mocked inline.
AUTHOR       : CAHYA DSN
UPDATED DATE : 2026-07-15
================================================================================
*/

require_once __DIR__ . '/TestRunner.php';
require_once __DIR__ . '/../inc/score.php';

// ── Test-data helpers ─────────────────────────────────────────────────────────

/**
 * Build a rule object matching the shape returned by papi_process.php's query.
 */
function make_rule(int $roleId, int $low, int $high, string $aspect, string $role, string $interp): object
{
    return (object) [
        'id'              => $roleId,
        'low_value'       => $low,
        'high_value'      => $high,
        'aspect'          => $aspect,
        'role'            => $role,
        'interprestation' => $interp,
    ];
}

/**
 * Build a $rulesByRole index identical to the one built in papi_process.php.
 *
 * @param  object[] $rules
 * @return array<int, object[]>
 */
function index_rules(array $rules): array
{
    $idx = [];
    foreach ($rules as $rule) {
        $idx[$rule->id][] = $rule;
    }
    return $idx;
}

// Standard 3-band rule set used by most tests
function standard_rules_by_role(): array
{
    $rules = [
        make_rule(1, 0, 2, 'Aspect #1', 'Need to A (A)', 'Lower A'),
        make_rule(1, 3, 5, 'Aspect #1', 'Need to A (A)', 'Middle A'),
        make_rule(1, 6, 9, 'Aspect #1', 'Need to A (A)', 'Higher A'),
        make_rule(2, 0, 2, 'Aspect #1', 'Role as B (B)', 'Lower B'),
        make_rule(2, 3, 5, 'Aspect #1', 'Role as B (B)', 'Middle B'),
        make_rule(2, 6, 9, 'Aspect #1', 'Role as B (B)', 'Higher B'),
    ];
    return index_rules($rules);
}

// ─────────────────────────────────────────────────────────────────────────────
//  SUITE: aggregate_scores()
// ─────────────────────────────────────────────────────────────────────────────

$t = new TestRunner('aggregate_scores()');
echo PHP_EOL . "  aggregate_scores()" . PHP_EOL;

// ── 1. Empty input returns empty array ───────────────────────────────────────
$t->run('returns empty array for empty input', function () use ($t) {
    $t->assertEquals([], aggregate_scores([]));
});

// ── 2. Single answer counted correctly ───────────────────────────────────────
$t->run('counts a single answer as score 1', function () use ($t) {
    $result = aggregate_scores([5 => '3']);
    $t->assertEquals([3 => 1], $result);
});

// ── 3. Multiple answers for the same role are summed ─────────────────────────
$t->run('accumulates multiple answers for the same role', function () use ($t) {
    $answers = ['3', '3', '3']; // role_id 3 chosen 3 times
    $result  = aggregate_scores($answers);
    $t->assertEquals(3, $result[3], 'role 3 score');
});

// ── 4. Answers for different roles are tracked separately ─────────────────────
$t->run('tracks multiple roles independently', function () use ($t) {
    $answers = ['1', '2', '1', '3', '2', '1'];
    $result  = aggregate_scores($answers);
    $t->assertEquals(3, $result[1], 'role 1 → 3 votes');
    $t->assertEquals(2, $result[2], 'role 2 → 2 votes');
    $t->assertEquals(1, $result[3], 'role 3 → 1 vote');
});

// ── 5. Result is sorted by role_id (ksort) ────────────────────────────────────
$t->run('output is sorted by role_id (ksort)', function () use ($t) {
    $answers = ['5', '2', '8', '2', '5'];
    $result  = aggregate_scores($answers);
    $keys    = array_keys($result);
    $t->assertEquals([2, 5, 8], $keys);
});

// ── 6. String role_id values are cast to int ──────────────────────────────────
$t->run('handles string role_id values (cast to int)', function () use ($t) {
    $answers = ['07', '7', '7'];
    $result  = aggregate_scores($answers);
    $t->assertEquals([7 => 3], $result);
});

// ── 7. Real-world simulation: 90 answers across roles 1-20 ───────────────────
$t->run('handles 90 answers — total vote count equals 90', function () use ($t) {
    $answers = [];
    // Distribute 90 questions: 4-5 votes per role for roles 1-20
    for ($i = 1; $i <= 20; $i++) {
        $count = ($i <= 10) ? 5 : 4;
        for ($j = 0; $j < $count; $j++) {
            $answers[] = (string) $i;
        }
    }
    $result = aggregate_scores($answers);
    $total  = array_sum($result);
    $t->assertEquals(90, $total, 'total votes');
    $t->assertEquals(20, count($result), 'number of distinct roles');
});

$failures = $t->summary();

// ─────────────────────────────────────────────────────────────────────────────
//  SUITE: find_rule()
// ─────────────────────────────────────────────────────────────────────────────

$t2 = new TestRunner('find_rule()');
echo "  find_rule()" . PHP_EOL;

// ── 1. Returns null for unknown role ─────────────────────────────────────────
$t2->run('returns null for an unknown role_id', function () use ($t2) {
    $t2->assertEquals(null, find_rule(99, 5, standard_rules_by_role()));
});

// ── 2. Matches lower band (0-2) exactly ──────────────────────────────────────
$t2->run('matches lower band: score=0', function () use ($t2) {
    $rule = find_rule(1, 0, standard_rules_by_role());
    $t2->assertEquals('Lower A', $rule->interprestation);
});

$t2->run('matches lower band: score=2', function () use ($t2) {
    $rule = find_rule(1, 2, standard_rules_by_role());
    $t2->assertEquals('Lower A', $rule->interprestation);
});

// ── 3. Matches middle band (3-5) ─────────────────────────────────────────────
$t2->run('matches middle band: score=3', function () use ($t2) {
    $rule = find_rule(1, 3, standard_rules_by_role());
    $t2->assertEquals('Middle A', $rule->interprestation);
});

$t2->run('matches middle band: score=5', function () use ($t2) {
    $rule = find_rule(1, 5, standard_rules_by_role());
    $t2->assertEquals('Middle A', $rule->interprestation);
});

// ── 4. Matches higher band (6-9) ─────────────────────────────────────────────
$t2->run('matches higher band: score=6', function () use ($t2) {
    $rule = find_rule(1, 6, standard_rules_by_role());
    $t2->assertEquals('Higher A', $rule->interprestation);
});

$t2->run('matches higher band: score=9', function () use ($t2) {
    $rule = find_rule(1, 9, standard_rules_by_role());
    $t2->assertEquals('Higher A', $rule->interprestation);
});

// ── 5. Returns null when score falls above all bands ─────────────────────────
$t2->run('returns null when score exceeds all rule ranges', function () use ($t2) {
    $t2->assertEquals(null, find_rule(1, 100, standard_rules_by_role()));
});

// ── 6. Correct role is matched when multiple roles share same band limits ─────
$t2->run('returns role-specific rule (role 2 middle band)', function () use ($t2) {
    $rule = find_rule(2, 4, standard_rules_by_role());
    $t2->assertEquals('Middle B', $rule->interprestation);
    $t2->assertEquals('Role as B (B)', $rule->role);
    $t2->assertEquals('Aspect #1', $rule->aspect);
});

// ── 7. Boundary values: score at band edges ───────────────────────────────────
$t2->run('boundary: score=2 is in lower band (not middle)', function () use ($t2) {
    $rule = find_rule(1, 2, standard_rules_by_role());
    $t2->assertNotEquals('Middle A', $rule->interprestation);
    $t2->assertEquals('Lower A', $rule->interprestation);
});

$t2->run('boundary: score=3 is in middle band (not lower)', function () use ($t2) {
    $rule = find_rule(1, 3, standard_rules_by_role());
    $t2->assertNotEquals('Lower A', $rule->interprestation);
    $t2->assertEquals('Middle A', $rule->interprestation);
});

$failures += $t2->summary();

// ─────────────────────────────────────────────────────────────────────────────
//  SUITE: build_results()
// ─────────────────────────────────────────────────────────────────────────────

$t3 = new TestRunner('build_results()');
echo "  build_results()" . PHP_EOL;

// ── 1. Empty scores → empty results ──────────────────────────────────────────
$t3->run('returns empty array for empty scores', function () use ($t3) {
    $t3->assertEquals([], build_results([], standard_rules_by_role()));
});

// ── 2. Single score matched to correct rule ───────────────────────────────────
$t3->run('maps a single score to the correct interpretation', function () use ($t3) {
    $scores  = [1 => 4]; // role 1, score 4 → Middle A
    $results = build_results($scores, standard_rules_by_role());

    $t3->assertEquals(1, count($results), 'one result row');
    $t3->assertEquals('Middle A',      $results[0]['interprestation']);
    $t3->assertEquals('Need to A (A)', $results[0]['role']);
    $t3->assertEquals('Aspect #1',     $results[0]['aspect']);
    $t3->assertEquals(4,               $results[0]['score']);
});

// ── 3. Multiple scores each resolved to correct band ─────────────────────────
$t3->run('resolves multiple roles to correct bands', function () use ($t3) {
    $scores  = [1 => 1, 2 => 7]; // Lower A, Higher B
    $results = build_results($scores, standard_rules_by_role());

    $t3->assertEquals(2, count($results));
    $t3->assertEquals('Lower A',  $results[0]['interprestation']);
    $t3->assertEquals('Higher B', $results[1]['interprestation']);
});

// ── 4. Score with no matching rule is silently skipped ────────────────────────
$t3->run('skips roles with no matching rule (score out of range)', function () use ($t3) {
    $scores  = [1 => 99]; // score 99 has no band
    $results = build_results($scores, standard_rules_by_role());

    $t3->assertEquals(0, count($results), 'should be empty');
});

// ── 5. Unknown role_id is silently skipped ────────────────────────────────────
$t3->run('skips unknown role_ids gracefully', function () use ($t3) {
    $scores  = [999 => 4]; // no rules for role 999
    $results = build_results($scores, standard_rules_by_role());

    $t3->assertEquals(0, count($results));
});

// ── 6. Results preserve role_id order (ksort from aggregate_scores) ──────────
$t3->run('preserves role_id order from sorted scores', function () use ($t3) {
    $scores  = aggregate_scores(['2', '1', '2', '1', '2', '1']); // roles 1 & 2, sorted
    $results = build_results($scores, standard_rules_by_role());

    $t3->assertEquals(2, count($results));
    $t3->assertEquals(1, $results[0]['role_id'], 'first result is role 1');
    $t3->assertEquals(2, $results[1]['role_id'], 'second result is role 2');
});

// ── 7. Full pipeline: raw answers → final result descriptions ─────────────────
$t3->run('full pipeline: answers → scores → results', function () use ($t3) {
    // Simulate 9 answers: role 1 chosen 7 times (Higher A), role 2 chosen 2 times (Lower B)
    $answers = array_merge(array_fill(0, 7, '1'), array_fill(0, 2, '2'));
    $scores  = aggregate_scores($answers);
    $results = build_results($scores, standard_rules_by_role());

    $t3->assertEquals(2, count($results));
    $t3->assertEquals('Higher A', $results[0]['interprestation'], 'role 1 → Higher A');
    $t3->assertEquals('Lower B',  $results[1]['interprestation'], 'role 2 → Lower B');
    $t3->assertEquals(7, $results[0]['score'], 'role 1 score');
    $t3->assertEquals(2, $results[1]['score'], 'role 2 score');
});

// ── 8. Score of 0 is handled (valid lower-band score) ────────────────────────
$t3->run('score of 0 resolves to lower band correctly', function () use ($t3) {
    $scores  = [1 => 0];
    $results = build_results($scores, standard_rules_by_role());

    $t3->assertEquals(1, count($results));
    $t3->assertEquals('Lower A', $results[0]['interprestation']);
});

$failures += $t3->summary();

return $failures;

<?php
/*
================================================================================
FILENAME     : inc/score.php
DESCRIPTION  : Pure scoring functions extracted from papi_process.php.
               No HTML output — safe to require from CLI tests or other pages.
AUTHOR       : CAHYA DSN
UPDATED DATE : 2026-07-15
================================================================================
*/

/**
 * Aggregate raw POST answers into a role-keyed score map.
 *
 * Each question answer is a role id (integer stored as a string).
 * Answers for the same role are counted, giving that role's total score.
 *
 * @param  array<int|string, int|string> $answers  Associative or indexed array
 *                                                  of role-id values (e.g. $_POST['s']).
 * @return array<int, int>  Map of role_id → count, sorted by role_id.
 */
function aggregate_scores(array $answers): array
{
    $data = [];
    foreach ($answers as $v) {
        $v = (int) $v;
        if (!isset($data[$v])) {
            $data[$v] = 0;
        }
        $data[$v]++;
    }
    ksort($data);
    return $data;
}

/**
 * Find the matching rule for a given role/score pair.
 *
 * Rules are a flat array of objects (or arrays) with:
 *   - low_value   (int|string)
 *   - high_value  (int|string)
 *   - interprestation (string)  [sic — matches DB column name]
 *   - role        (string)
 *   - aspect      (string)
 *   - id          (int)         role id
 *
 * @param  int    $roleId   Role identifier.
 * @param  int    $score    The aggregated score for that role.
 * @param  array  $rules    Pre-indexed rules: array<role_id, rule_object[]>.
 *                          Each value is an array of objects with low_value/high_value.
 * @return object|null  The matching rule object, or null if none found.
 */
function find_rule(int $roleId, int $score, array $rulesByRole): ?object
{
    if (!isset($rulesByRole[$roleId])) {
        return null;
    }
    foreach ($rulesByRole[$roleId] as $rule) {
        if ($score >= (int) $rule->low_value && $score <= (int) $rule->high_value) {
            return $rule;
        }
    }
    return null;
}

/**
 * Build a result set from aggregated scores and a pre-indexed rule map.
 *
 * Returns an array of result rows in role_id order, each containing:
 *   [ 'role_id', 'score', 'aspect', 'role', 'interprestation' ]
 *
 * Roles with no matching rule are silently skipped (same behaviour as papi_process.php).
 *
 * @param  array<int, int>        $scores      Output of aggregate_scores().
 * @param  array<int, object[]>   $rulesByRole Pre-indexed rules (keyed by role_id).
 * @return array<int, array>      Ordered list of result rows.
 */
function build_results(array $scores, array $rulesByRole): array
{
    $results = [];
    foreach ($scores as $roleId => $score) {
        $rule = find_rule($roleId, $score, $rulesByRole);
        if ($rule !== null) {
            $results[] = [
                'role_id'        => $roleId,
                'score'          => $score,
                'aspect'         => $rule->aspect,
                'role'           => $rule->role,
                'interprestation' => $rule->interprestation,
            ];
        }
    }
    return $results;
}

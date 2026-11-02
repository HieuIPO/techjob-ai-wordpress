<?php
/** Run using wp eval-file techjob-tests/integration.php on the local site. */
if (!defined('WP_CLI') || !WP_CLI) { exit; }
$checks = 0;
function tj_assert($condition, string $message): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
}
$candidate = get_user_by('login', 'candidate01');
$other = get_user_by('login', 'candidate02');
$admin = get_user_by('login', 'techjob_admin');
tj_assert($candidate && $other && $admin, 'Seed accounts exist');
wp_set_current_user($candidate->ID);
tj_assert(tj_candidate() && !tj_admin(), 'Candidate cannot administer');
tj_assert(is_wp_error(tj_apply(0, 0, '', true)), 'Invalid application must fail');
tj_assert(function_exists('tj_login_target') && tj_login_target('', $admin) !== '', 'Empty login redirect must go to dashboard');

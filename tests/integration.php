<?php
/** Run using wp eval-file techjob-tests/integration.php on the local site. */
if (!defined('WP_CLI') || !WP_CLI) { exit; }
$GLOBALS['tj_test_checks'] = 0;
function tj_assert($condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
    $GLOBALS['tj_test_checks']++;
}
$candidate = get_user_by('login', 'candidate01');
$other = get_user_by('login', 'candidate02');
$admin = get_user_by('login', 'techjob_admin');
tj_assert($candidate && $other && $admin, 'Seed accounts exist');
$seed_candidate = $candidate;
$test_users = [];
foreach (['one', 'two'] as $suffix) {
    $login = 'tj_test_' . $suffix . '_' . wp_generate_password(8, false, false);
    $uid = wp_insert_user(['user_login' => $login, 'user_pass' => wp_generate_password(24), 'user_email' => $login . '@techjob.test', 'display_name' => 'Integration ' . $suffix, 'role' => 'tj_candidate']);
    update_user_meta($uid, 'tj_phone', '0900000000');
    $test_users[] = $uid;
    $seed_file = $GLOBALS['wpdb']->get_row($GLOBALS['wpdb']->prepare('SELECT * FROM ' . tj_table('files') . ' WHERE user_id=%d LIMIT 1', $seed_candidate->ID), ARRAY_A);
    unset($seed_file['id']); $seed_file['user_id'] = $uid;
    $GLOBALS['wpdb']->insert(tj_table('files'), $seed_file);
}
$candidate = get_user_by('id', $test_users[0]); $other = get_user_by('id', $test_users[1]);
wp_set_current_user($candidate->ID);
tj_assert(tj_candidate() && !tj_admin(), 'Candidate cannot administer');
tj_assert(is_wp_error(tj_apply(0, 0, '', true)), 'Invalid application must fail');
tj_assert(function_exists('tj_login_target') && tj_login_target('', $admin) !== '', 'Empty login redirect must go to dashboard');
tj_assert(str_contains(tj_login_target('https://attacker.invalid/', $admin), '/quan-tri/'), 'Login cannot redirect to another host');
tj_assert(!tj_can_read_file(['user_id' => $other->ID]), 'Candidate cannot read another CV');
tj_assert(tj_can_read_file(['user_id' => $candidate->ID]), 'Owner can read CV');
tj_assert(is_wp_error(tj_save_job(['title' => 'Unauthorized'])), 'Candidate cannot create jobs');
$file_id = (int)$GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare('SELECT id FROM ' . tj_table('files') . ' WHERE user_id=%d LIMIT 1', $candidate->ID));
$other_file = (int)$GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare('SELECT id FROM ' . tj_table('files') . ' WHERE user_id=%d LIMIT 1', $other->ID));
wp_set_current_user($admin->ID);
$company = get_posts(['post_type' => 'tj_company', 'numberposts' => 1])[0]->ID;
$job = tj_save_job(['title' => 'Integration test job', 'company_id' => $company, 'status' => 'publish', 'salary_min' => 15000000, 'salary_max' => 25000000, 'description' => '<p>Test</p>', 'requirements' => '<p>Test requirement</p>', 'deadline' => wp_date('Y-m-d', time() + DAY_IN_SECONDS)]);
tj_assert(is_int($job), 'Admin can create a job');
$app_id = 0; $interview_ids = []; $session_id = 0;
try {
    global $wpdb;
    ob_start(); tj_render('job', ['route' => 'job', 'job' => get_post($job)]); $job_html = ob_get_clean();
    tj_assert(str_contains($job_html, '<h1>Integration test job</h1>'), 'Job detail template receives the selected job');
    wp_set_current_user($candidate->ID);
    tj_assert(is_wp_error(tj_apply($job, $other_file, '', true)), 'Cannot submit somebody else CV');
    tj_assert(is_wp_error(tj_apply($job, $file_id, '', false)), 'Consent is required');
    $app_id = tj_apply($job, $file_id, 'Integration test', true);
    tj_assert(is_int($app_id) && $app_id > 0, 'Candidate can apply with own CV');
    $previous_suppress = $wpdb->suppress_errors(true);
    tj_assert(is_wp_error(tj_apply($job, $file_id, '', true)), 'Duplicate application is rejected');
    $wpdb->suppress_errors($previous_suppress);
    tj_assert((int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . tj_table('applications') . ' WHERE job_id=%d', $job)) === 1, 'Exactly one application persisted');
    $app = tj_row('applications', $app_id);
    tj_assert($app['candidate_name'] === $candidate->display_name && (int)$app['file_id'] === $file_id, 'Application stores submitted profile and CV snapshot');
    wp_set_current_user($admin->ID);
    $base = ['application_id' => $app_id, 'scheduled_at' => wp_date('Y-m-d\TH:i', time() + DAY_IN_SECONDS), 'location' => 'Test meeting', 'result' => 'pending'];
    tj_assert(is_wp_error(tj_save_interview(array_merge($base, ['scheduled_at' => '2099-02-30T09:00']))), 'Impossible dates rejected');
    tj_assert(is_wp_error(tj_save_interview($base + ['id' => 99999999])), 'Cannot edit missing interview');
    $interview = tj_save_interview($base);
    tj_assert(is_int($interview), 'Admin can schedule interview'); $interview_ids[] = $interview;
    tj_assert(tj_row('applications', $app_id)['status'] === 'interview', 'Scheduling updates application status');
    $wpdb->suppress_errors(true);
    tj_assert(is_wp_error(tj_save_interview($base)), 'Duplicate interview time rejected');
    $wpdb->suppress_errors($previous_suppress);
    tj_save_interview(array_merge($base, ['id' => $interview, 'result' => 'passed']));
    tj_assert(tj_row('applications', $app_id)['status'] === 'passed', 'Passed interview updates application');
    tj_assert(is_wp_error(tj_save_interview(array_merge($base, ['scheduled_at' => wp_date('Y-m-d\TH:i', time() + 2 * DAY_IN_SECONDS)]))), 'Final application cannot receive new interview');
    tj_save_interview(array_merge($base, ['id' => $interview, 'result' => 'pending']));
    tj_assert(tj_row('applications', $app_id)['status'] === 'interview', 'Reversing a final result reconciles status');
    tj_cancel_interview($interview);
    tj_assert(tj_row('applications', $app_id)['status'] === 'reviewing', 'Cancelling the last interview returns application to review');
    tj_assert(is_wp_error(tj_cancel_interview($interview)), 'Repeated cancellation rejected');
    $rescheduled = tj_save_interview($base);
    tj_assert(is_int($rescheduled), 'Cancelled time slot can be booked again');
    $interview_ids[] = $rescheduled;
    wp_update_post(['ID' => $job, 'post_status' => 'private']);
    wp_set_current_user($other->ID);
    tj_assert(is_wp_error(tj_apply($job, $other_file, '', true)), 'Closed jobs reject new applications');
    wp_set_current_user($candidate->ID);
    $request = new WP_REST_Request('POST', '/techjob/v1/ai/start');
    $request->set_body_params(['skill' => 'Java', 'mode' => 'demo', 'level' => 'Junior', 'language' => 'en']);
    $start = tj_ai_start($request); $session_id = $start['id'];
    tj_assert($start['mode'] === 'demo' && $session_id > 0, 'Demo interview starts without API key');
    for ($step = 0; $step < 5; $step++) {
        $answer = new WP_REST_Request('POST'); $answer->set_body_params(['id' => $session_id, 'answer' => 'An example answer with test cases.', 'step' => $step]);
        $reply = tj_ai_answer($answer);
        tj_assert(!is_wp_error($reply) && $reply['step'] === $step + 1 && $reply['done'] === ($step === 4), 'Demo advances one question at a time');
    }
    tj_assert(is_wp_error(tj_ai_answer($answer)), 'Finished session cannot accept more answers');
    wp_set_current_user($other->ID);
    tj_assert(is_wp_error(tj_ai_answer($answer)), 'Other candidate cannot access AI session');
    wp_set_current_user(0);
    $denied = rest_do_request(new WP_REST_Request('POST', '/techjob/v1/ai/start'));
    tj_assert($denied->get_status() === 401, 'Anonymous AI request rejected');
    $key = 'test:' . wp_generate_uuid4();
    tj_assert(tj_limit($key, 1, 60) && !tj_limit($key, 1, 60), 'Rate limit persists between calls');
    $_GET['lang'] = 'en';
    tj_assert(tj_t('Xin chào', 'Hello') === 'Hello', 'English UI selection');
    tj_assert(!str_contains(tj_url('quan-tri/ho-so?application=1'), 'application=1/'), 'Query ID remains intact in generated URLs');
    unset($_GET['lang']);
    WP_CLI::success('PASS ' . $GLOBALS['tj_test_checks'] . ' integration checks');
} finally {
    wp_set_current_user($admin->ID);
    if ($app_id) { $wpdb->delete(tj_table('history'), ['application_id' => $app_id]); $wpdb->delete(tj_table('interviews'), ['application_id' => $app_id]); $wpdb->delete(tj_table('applications'), ['id' => $app_id]); }
    if ($session_id) { $wpdb->delete(tj_table('ai_sessions'), ['id' => $session_id]); }
    // Remove only notifications and audit entries belonging to this test's application/job.
    $wpdb->query($wpdb->prepare('DELETE FROM ' . tj_table('notifications') . ' WHERE target IN (%s,%s)', 'quan-tri/ho-so?application=' . $app_id, 'tai-khoan/ung-tuyen?application=' . $app_id));
    wp_delete_post($job, true);
    $wpdb->query($wpdb->prepare('DELETE FROM ' . tj_table('audit') . " WHERE (action='save_job' AND entity_id=%d) OR (action='change_application_status' AND entity_id=%d)", $job, $app_id));
    foreach ($interview_ids as $iid) { $wpdb->query($wpdb->prepare('DELETE FROM ' . tj_table('audit') . " WHERE action IN ('save_interview','cancel_interview') AND entity_id=%d", $iid)); }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($test_users as $uid) {
        $wpdb->delete(tj_table('files'), ['user_id' => $uid]);
        $wpdb->delete(tj_table('notifications'), ['user_id' => $uid]);
        $wpdb->delete(tj_table('ai_sessions'), ['user_id' => $uid]);
        $wpdb->delete(tj_table('audit'), ['actor_id' => $uid]);
        wp_delete_user($uid);
    }
}

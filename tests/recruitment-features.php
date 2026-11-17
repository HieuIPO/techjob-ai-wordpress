<?php
/** Run with PHP CLI. TJ_TEST_WP_LOAD may point at another isolated local wp-load.php. */
if (PHP_SAPI !== 'cli') { exit; }
require_once getenv('TJ_TEST_WP_LOAD') ?: dirname(__DIR__) . '/runtime/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
if (wp_get_environment_type() !== 'local' || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('This suite only runs against a local WordPress installation.');
}
$base = home_url();
$prefix = 'tj_feature_' . bin2hex(random_bytes(5));
$password = bin2hex(random_bytes(20));
$jars = []; $users = []; $posts = []; $terms = []; $checks = 0;
$pdf = tempnam(sys_get_temp_dir(), 'tj-feature-cv-');
function ft_check(bool $ok, string $message): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL ' . $message); }
    $checks++;
    echo "PASS $message\n";
}
function ft_request(string $session, string $path, ?array $data = null): array {
    global $base, $jars;
    $jars[$session] ??= tempnam(sys_get_temp_dir(), 'tj-feature-cookie-');
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4, CURLOPT_TIMEOUT => 30, CURLOPT_COOKIEJAR => $jars[$session], CURLOPT_COOKIEFILE => $jars[$session], CURLOPT_NOPROXY => '*']);
    if ($data !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, $data); }
    $body = curl_exec($curl);
    if ($body === false) { throw new RuntimeException(curl_error($curl)); }
    $response = ['body' => $body, 'code' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'type' => curl_getinfo($curl, CURLINFO_CONTENT_TYPE), 'url' => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL)];
    curl_close($curl);
    return $response;
}
function ft_form(array $page, string $action, string $predicate = ''): array {
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $page['body'], LIBXML_NONET);
    $xpath = new DOMXPath($doc);
    $form = $xpath->query("//form[.//input[@name='tj_action' and @value='$action']]" . $predicate)->item(0);
    if (!$form) { throw new RuntimeException("Missing form $action"); }
    $data = [];
    foreach ($xpath->query(".//input[@type='hidden']", $form) as $input) { $data[$input->getAttribute('name')] = $input->getAttribute('value'); }
    return $data;
}
function ft_submit(string $session, array $page, string $action, array $data = [], string $predicate = ''): array {
    return ft_request($session, '/wp-admin/admin-post.php', $data + ft_form($page, $action, $predicate));
}
function ft_saved(array $response): bool { return $response['code'] === 200 && str_contains($response['url'], 'notice='); }
function ft_nonce(string $session, int $user, string $action): string {
    global $jars;
    wp_set_current_user($user);
    foreach (file($jars[$session], FILE_IGNORE_NEW_LINES) as $line) {
        $parts = explode("\t", $line);
        if (count($parts) === 7 && $parts[5] === LOGGED_IN_COOKIE) { $_COOKIE[LOGGED_IN_COOKIE] = rawurldecode($parts[6]); }
    }
    $nonce = wp_create_nonce('tj_' . $action);
    unset($_COOKIE[LOGGED_IN_COOKIE]);
    return $nonce;
}
try {
    foreach (['admin' => 'administrator', 'candidate' => 'tj_candidate'] as $session => $role) {
        $uid = wp_insert_user(['user_login' => "$prefix-$session", 'user_email' => "$prefix-$session@example.test", 'user_pass' => $password, 'display_name' => "$prefix $session", 'role' => $role]);
        if (is_wp_error($uid)) { throw new RuntimeException($uid->get_error_message()); }
        $users[$session] = $uid;
        update_user_meta($uid, 'tj_phone', '0900000000');
        ft_check(ft_request($session, '/dang-nhap/?lang=vi')['code'] === 200, "$session login form");
        $logged_in = ft_submit($session, ft_request($session, '/dang-nhap/?lang=vi'), 'login', ['username' => "$prefix-$session", 'password' => $password]);
        ft_check($logged_in['code'] === 200 && str_contains($logged_in['url'], $session === 'admin' ? '/quan-tri/' : '/tai-khoan/'), "$session signs in");
    }
    wp_set_current_user($users['admin']);
    ft_check((int)get_option('tj_schema_version') === 3 && $wpdb->get_var("SHOW TABLES LIKE '" . tj_table('evaluations') . "'") === tj_table('evaluations'), 'Schema upgrades with scorecard table');

    $companyInput = ['title' => "$prefix Công ty", 'title_en' => "$prefix Company", 'description' => 'Công ty giả lập', 'description_en' => 'Fictional company'];
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', '/quan-tri/cong-ty/?edit=new'), 'save_company', $companyInput)), 'Create bilingual company');
    $company = (int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_title=%s AND post_type='tj_company'", $companyInput['title']));
    $posts[] = $company;
    foreach (['skill', 'category', 'position', 'location'] as $tax) {
        $predicate = "[.//input[@name='taxonomy' and @value='tj_$tax'] and not(.//input[@name='id'])]";
        $page = ft_request('admin', '/quan-tri/danh-muc/?lang=vi');
        ft_check(ft_saved(ft_submit('admin', $page, 'save_term', ['name' => "$prefix $tax VI", 'name_en' => "$prefix $tax EN"], $predicate)), "Create bilingual $tax");
        $term = get_term_by('name', "$prefix $tax VI", 'tj_' . $tax);
        $terms[$tax] = (int)$term->term_id;
        ft_check(get_term_meta($term->term_id, 'tj_name_en', true) === "$prefix $tax EN", "Persist $tax English name");
    }
    $input = ['title' => "$prefix Công việc", 'title_en' => "$prefix English job", 'description' => 'Mô tả công việc tiếng Việt', 'description_en' => '<p>English job description</p>', 'requirements' => 'Yêu cầu tiếng Việt', 'requirements_en' => '<p>English requirements</p>', 'company_id' => $company, 'status' => 'publish', 'negotiable' => '1', 'skill[]' => $terms['skill'], 'category' => $terms['category'], 'position' => $terms['position'], 'location' => $terms['location']];
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', '/quan-tri/viec-lam/?edit=new'), 'save_job', $input)), 'Create job with both languages through admin form');
    $job = (int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_title=%s AND post_type='tj_job'", $input['title']));
    $posts[] = $job;
    $path = '/viec-lam/' . get_post_field('post_name', $job) . '/';
    $vi = ft_request('guest', $path . '?lang=vi'); $en = ft_request('guest', $path . '?lang=en');
    ft_check(str_contains($vi['body'], $input['title']) && str_contains($vi['body'], $input['requirements']) && str_contains($vi['body'], "$prefix skill VI"), 'Vietnamese job content and skills');
    ft_check(str_contains($en['body'], $input['title_en']) && str_contains($en['body'], 'English job description') && str_contains($en['body'], 'English requirements') && str_contains($en['body'], "$prefix skill EN"), 'English job content and skills');
    $edit = ft_request('admin', "/quan-tri/viec-lam/?edit=$job");
    ft_check(str_contains($edit['body'], 'name="description_en"') && str_contains($edit['body'], 'name="requirements_en"') && str_contains($edit['body'], 'name="title_en"'), 'All translation fields editable');
    ft_check(ft_saved(ft_submit('admin', $edit, 'save_job', array_replace($input, ['title_en' => '', 'description_en' => '', 'requirements_en' => '']))), 'Clear optional job translations');
    $fallback = ft_request('guest', $path . '?lang=en');
    ft_check(str_contains($fallback['body'], $input['title']) && str_contains($fallback['body'], $input['requirements']), 'Empty translations fall back to Vietnamese');
    ft_check(ft_saved(ft_submit('admin', $edit, 'save_job', $input)), 'Restore translations');
    $term = $terms['skill'];
    $predicate = "[.//input[@name='taxonomy' and @value='tj_skill'] and .//input[@name='id' and @value='$term']]";
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', '/quan-tri/danh-muc/'), 'save_term', ['name' => "$prefix skill VI", 'name_en' => ''], $predicate)), 'Clear optional taxonomy translation');
    ft_check(str_contains(ft_request('guest', $path . '?lang=en')['body'], "$prefix skill VI"), 'Taxonomy falls back when English name is empty');

    file_put_contents($pdf, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
    $profile = ft_request('candidate', '/tai-khoan/ho-so/');
    ft_check(ft_saved(ft_submit('candidate', $profile, 'upload_cv', ['cv' => new CURLFile($pdf, 'application/pdf', "$prefix.pdf")])), 'Upload fictional test PDF');
    $file = (int)$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('files') . ' WHERE user_id=%d', $users['candidate']));
    ft_check(ft_saved(ft_submit('candidate', ft_request('candidate', $path), 'apply', ['file_id' => $file, 'consent' => '1'])), 'Apply with translated job sharing one application');
    $app = (int)$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('applications') . ' WHERE user_id=%d AND job_id=%d', $users['candidate'], $job));
    $schedule = ['application_id' => $app, 'scheduled_at' => wp_date('Y-m-d\T10:00', time() + 3 * DAY_IN_SECONDS), 'location' => "$prefix Meeting", 'result' => 'pending'];
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', "/quan-tri/lich-phong-van/?application=$app"), 'save_interview', $schedule)), 'Schedule interview for scorecard');
    $iid = (int)$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('interviews') . ' WHERE application_id=%d', $app));
    $scorePage = ft_request('admin', "/quan-tri/lich-phong-van/?edit=$iid");
    $scores = ['technical' => '4', 'problem_solving' => '3', 'communication' => '5', 'recommendation' => 'hire', 'evaluation_note' => "$prefix INTERNAL ONLY"];
    ft_check(ft_saved(ft_submit('admin', $scorePage, 'save_evaluation', $scores)), 'Admin saves scorecard through POST');
    $evaluation = tj_evaluation($iid);
    ft_check((int)$evaluation['technical'] + (int)$evaluation['problem_solving'] + (int)$evaluation['communication'] === 12 && (int)$evaluation['revision'] === 1, 'Scorecard totals 12/15 and revision 1');
    tj_activate();
    ft_check((int)tj_evaluation($iid)['revision'] === 1 && (int)tj_row('applications', $app)['file_id'] === $file, 'Repeating schema upgrade preserves scorecard and existing application');
    ft_check(tj_row('applications', $app)['status'] === 'interview', 'Score recommendation preserves explicit recruitment status workflow');
    ft_check(str_contains(ft_submit('admin', $scorePage, 'save_evaluation', $scores)['body'], 'Phiếu đã được cập nhật'), 'Stale scorecard form rejected');
    $current = ft_request('admin', "/quan-tri/lich-phong-van/?edit=$iid");
    ft_check(str_contains(ft_submit('admin', $current, 'save_evaluation', array_replace($scores, ['technical' => '6']))['body'], 'Chấm đủ ba tiêu chí'), 'Score outside 1-5 rejected');
    ft_check(str_contains(ft_submit('admin', $current, 'save_evaluation', array_replace($scores, ['technical' => '2.5']))['body'], 'Chấm đủ ba tiêu chí'), 'Fractional score rejected');
    foreach ([['communication' => ''], ['technical' => '0'], ['recommendation' => 'unknown'], ['evaluation_note' => str_repeat('x', 5001)]] as $invalid) {
        $response = ft_submit('admin', $current, 'save_evaluation', array_replace($scores, $invalid));
        ft_check(str_contains($response['body'], 'role="alert"') && (int)tj_evaluation($iid)['revision'] === 1, 'Invalid scorecard input preserves saved revision');
    }
    ft_check(ft_saved(ft_submit('admin', $current, 'save_evaluation', array_replace($scores, ['technical' => '5']))), 'Update existing scorecard');
    ft_check((int)tj_evaluation($iid)['revision'] === 2 && (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . tj_table('evaluations') . ' WHERE interview_id=%d', $iid)) === 1, 'Updating creates no duplicate scorecards');
    ft_check(ft_request('candidate', "/quan-tri/lich-phong-van/?edit=$iid")['code'] === 403 && !str_contains(ft_request('candidate', '/tai-khoan/lich-phong-van/')['body'], $scores['evaluation_note']), 'Scorecard and internal assessment restricted to admins');
    $denied = ft_request('candidate', '/wp-admin/admin-post.php', $scores + ['action' => 'tj_action', 'tj_action' => 'save_evaluation', '_tj_nonce' => ft_nonce('candidate', $users['candidate'], 'save_evaluation'), 'interview_id' => $iid, 'revision' => 2]);
    ft_check($denied['code'] === 403, 'Candidate with valid own nonce cannot save scorecard');
    wp_set_current_user($users['admin']);
    $badNonce = ft_submit('admin', $current, 'save_evaluation', $scores + ['_tj_nonce' => 'invalid']);
    ft_check($badNonce['code'] === 403, 'Scorecard CSRF rejected');
    $list = ft_request('admin', '/quan-tri/lich-phong-van/');
    ft_check(ft_saved(ft_submit('admin', $list, 'cancel_interview', [], "[.//input[@name='id' and @value='$iid']]")), 'Cancel evaluated interview');
    $cancelled = ft_request('admin', "/quan-tri/lich-phong-van/?edit=$iid");
    ft_check(str_contains($cancelled['body'], $scores['evaluation_note']) && !str_contains($cancelled['body'], 'name="tj_action" value="save_evaluation"'), 'Cancelled interview keeps read-only scorecard');
    ft_check(str_contains(ft_submit('admin', $current, 'save_evaluation', $scores + ['revision' => 2])['body'], 'Lịch không tồn tại hoặc đã hủy'), 'Stale form cannot evaluate cancelled interview');

    // Exercise inclusive local dates against the UTC timestamp used by the database.
    $from = new DateTimeImmutable('2098-01-02 00:00:00', wp_timezone());
    $utc = $from->setTimezone(new DateTimeZone('UTC'));
    $wpdb->update(tj_table('applications'), ['created_at' => $utc->format('Y-m-d H:i:s'), 'status' => 'passed'], ['id' => $app]);
    $filters = ['from' => '2098-01-02', 'to' => '2098-01-02', 'job' => $job];
    $report = tj_recruitment_report($filters);
    ft_check($report['totals']['total'] === 1 && $report['totals']['passed'] === 1 && $report['candidates'] === 1, 'Report includes local start of day with correct totals');
    $baseline = tj_recruitment_report(array_replace($filters, ['job' => 0]));
    $second_job = tj_save_job(array_replace($input, ['title' => "$prefix Second job"]));
    if (is_wp_error($second_job)) { throw new RuntimeException($second_job->get_error_message()); }
    $posts[] = $second_job;
    wp_set_current_user($users['candidate']);
    $second_app = tj_apply($second_job, $file, '', true);
    if (is_wp_error($second_app)) { throw new RuntimeException($second_app->get_error_message()); }
    $wpdb->update(tj_table('applications'), ['created_at' => $utc->format('Y-m-d H:i:s')], ['id' => $second_app]);
    wp_set_current_user($users['admin']);
    $all = tj_recruitment_report(array_replace($filters, ['job' => 0]));
    ft_check($all['totals']['total'] === $baseline['totals']['total'] + 1 && $all['candidates'] === $baseline['candidates'], 'Same candidate applying to two jobs counted once across report');
    ft_check(tj_recruitment_report($filters)['totals']['total'] === 1, 'Filtering by job excludes second job application');
    $wpdb->update(tj_table('applications'), ['created_at' => $utc->modify('+1 day -1 second')->format('Y-m-d H:i:s')], ['id' => $app]);
    ft_check(tj_recruitment_report($filters)['totals']['total'] === 1, 'Report includes last second of selected day');
    $wpdb->update(tj_table('applications'), ['created_at' => $utc->modify('+1 day')->format('Y-m-d H:i:s')], ['id' => $app]);
    ft_check(tj_recruitment_report($filters)['totals']['total'] === 0, 'Report excludes next local day');
    $wpdb->update(tj_table('applications'), ['created_at' => $utc->format('Y-m-d H:i:s')], ['id' => $app]);
    foreach ([['from' => '2098-02-30'], ['from' => ['bad']], ['to' => '2098-01-01'], ['job' => -1], ['job' => $company]] as $invalid) {
        ft_check(is_wp_error(tj_report_filters(array_replace($filters, $invalid))), 'Reject invalid report filter ' . json_encode($invalid));
    }
    $reportUrl = '/quan-tri/bao-cao/?' . http_build_query($filters + ['lang' => 'vi']);
    $reportPage = ft_request('admin', $reportUrl);
    ft_check($reportPage['code'] === 200 && str_contains($reportPage['body'], '100%') && str_contains($reportPage['body'], $input['title']), 'Admin report page matches data');
    $csv = ft_submit('admin', $reportPage, 'export_report');
    $lines = preg_split('/\r?\n/', trim(substr($csv['body'], 3)));
    $csvRow = str_getcsv($lines[1] ?? '', ',', '"', '');
    ft_check(str_starts_with($csv['body'], "\xEF\xBB\xBF") && str_contains($csv['type'], 'text/csv') && count($csvRow) === 10 && (int)$csvRow[4] === 1 && (int)$csvRow[8] === 1, 'CSV is UTF-8 and matches status table');
    $wpdb->update($wpdb->posts, ['post_title' => '=1+1'], ['ID' => $job]); clean_post_cache($job);
    $protected = ft_submit('admin', $reportPage, 'export_report');
    ft_check(str_contains($protected['body'], "'=1+1"), 'CSV protects formula-like job titles');
    wp_update_post(['ID' => $job, 'post_title' => $input['title']]);
    ft_check(ft_request('candidate', '/quan-tri/bao-cao/')['code'] === 403, 'Candidate cannot read report');
    $denied = ft_request('candidate', '/wp-admin/admin-post.php', ['action' => 'tj_action', 'tj_action' => 'export_report', '_tj_nonce' => ft_nonce('candidate', $users['candidate'], 'export_report')] + $filters);
    ft_check($denied['code'] === 403, 'Valid candidate nonce cannot export report');
    wp_set_current_user($users['admin']);
    ft_check(ft_submit('admin', $reportPage, 'export_report', ['_tj_nonce' => 'invalid'])['code'] === 403, 'Export CSRF rejected');
    ft_check(str_contains(ft_submit('admin', $reportPage, 'export_report', ['from' => 'invalid'])['body'], 'Nhập ngày hợp lệ'), 'Invalid export shows readable error');
    $empty = ft_request('admin', '/quan-tri/bao-cao/?from=2097-01-01&to=2097-01-01&job=' . $job);
    ft_check(str_contains($empty['body'], 'Chưa có hồ sơ trong khoảng đã chọn.') && str_contains($empty['body'], '>0%</strong>'), 'Empty report shows zeros without division error');
    ft_check(count(preg_split('/\r?\n/', trim(ft_submit('admin', $empty, 'export_report')['body']))) === 1, 'Empty CSV contains only header');
    ft_check(str_contains(ft_request('admin', "/quan-tri/bao-cao/?from=2098-02-30&to=2098-03-01")['body'], 'role="alert"'), 'Invalid report page has visible alert');
    echo "PASS $checks recruitment feature checks\n";
} finally {
    wp_set_current_user(0);
    foreach ($users as $uid) {
        $apps = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . tj_table('applications') . ' WHERE user_id=%d', $uid));
        foreach ($apps as $id) {
            $interviews = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . tj_table('interviews') . ' WHERE application_id=%d', $id));
            foreach ($interviews as $iid) { $wpdb->delete(tj_table('evaluations'), ['interview_id' => $iid]); }
            foreach (['interviews', 'history'] as $table) { $wpdb->delete(tj_table($table), ['application_id' => $id]); }
            $wpdb->delete(tj_table('notifications'), ['target' => 'quan-tri/ho-so?application=' . $id]);
        }
        foreach (tj_rows('files', 100, $uid) as $file) {
            $path = tj_private_dir() . '/' . basename($file['stored_name']);
            if (is_file($path)) { unlink($path); }
        }
        foreach (['applications', 'files', 'notifications', 'saved'] as $table) { $wpdb->delete(tj_table($table), ['user_id' => $uid]); }
        $wpdb->delete(tj_table('audit'), ['actor_id' => $uid]);
        wp_delete_user($uid);
    }
    foreach (array_reverse($posts) as $id) { wp_delete_post($id, true); }
    foreach ($terms as $tax => $id) { wp_delete_term($id, 'tj_' . $tax); }
    foreach (array_merge(array_values($jars), [$pdf]) as $path) { if (is_file($path)) { unlink($path); } }
    echo "Removed only this suite's users, posts, taxonomy and recruitment fixtures.\n";
}

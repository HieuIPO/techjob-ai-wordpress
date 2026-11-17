<?php
/** Self-contained HTTP checks for translations, scorecards and reports. */
if (PHP_SAPI !== 'cli') { exit; }
require __DIR__ . '/recruitment-support.php';

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
    $extra_skill = wp_insert_term("$prefix Second skill", 'tj_skill');
    if (is_wp_error($extra_skill)) { throw new RuntimeException($extra_skill->get_error_message()); }
    $additional_terms[] = [(int)$extra_skill['term_id'], 'tj_skill'];
    $multi_input = $input;
    unset($multi_input['skill[]']);
    $multi_input['skill[0]'] = $terms['skill'];
    $multi_input['skill[1]'] = $extra_skill['term_id'];
    ft_check(ft_saved(ft_submit('admin', $edit, 'save_job', $multi_input)), 'Save two skills from checkbox form');
    clean_object_term_cache($job, 'tj_job');
    ft_check(count(wp_get_post_terms($job, 'tj_skill')) === 2, 'Both selected skills persist');
    $two_skills = ft_request('admin', "/quan-tri/viec-lam/?edit=$job");
    $skill_dom = new DOMDocument(); @$skill_dom->loadHTML($two_skills['body'], LIBXML_NONET);
    ft_check((new DOMXPath($skill_dom))->query('//input[@name="skill[]" and @checked]')->length === 2, 'Editing restores both checked skills');
    $clear_skills = $input; unset($clear_skills['skill[]']);
    $cleared = ft_submit('admin', $edit, 'save_job', $clear_skills);
    clean_object_term_cache($job, 'tj_job');
    ft_check(ft_saved($cleared) && count(wp_get_post_terms($job, 'tj_skill')) === 0, 'Unchecking all skills clears saved selection');
    ft_check(ft_saved(ft_submit('admin', $edit, 'save_job', $input)), 'Restore original selected skill');
    $term = $terms['skill'];
    $predicate = "[.//input[@name='taxonomy' and @value='tj_skill'] and .//input[@name='id' and @value='$term']]";
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', '/quan-tri/danh-muc/'), 'save_term', ['name' => "$prefix skill VI", 'name_en' => ''], $predicate)), 'Clear optional taxonomy translation');
    ft_check(str_contains(ft_request('guest', $path . '?lang=en')['body'], "$prefix skill VI"), 'Taxonomy falls back when English name is empty');

    file_put_contents($pdf, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
    $profile = ft_request('candidate', '/tai-khoan/ho-so/');
    ft_check(ft_saved(ft_submit('candidate', $profile, 'upload_cv', ['cv' => new CURLFile($pdf, 'application/pdf', "$prefix.pdf")])), 'Upload fictional test PDF');
    $file = (int)$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('files') . ' WHERE user_id=%d', $users['candidate']));
    $docx = $pdf . '.docx'; $temporary_files[] = $docx;
    $zip = new ZipArchive(); $zip->open($docx, ZipArchive::CREATE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
    $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Fictional recruitment test CV</w:t></w:r></w:p></w:body></w:document>');
    $zip->close();
    ft_check(ft_saved(ft_submit('candidate', $profile, 'upload_cv', ['cv' => new CURLFile($docx, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', "$prefix.docx")])), 'Upload valid OpenXML DOCX through HTTP');
    $docx_id = (int)$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('files') . ' WHERE user_id=%d AND original_name=%s', $users['candidate'], "$prefix.docx"));
    ft_check(ft_request('candidate', "/tai-cv/$docx_id/")['body'] === file_get_contents($docx), 'DOCX download preserves exact bytes');
    $large = $pdf . '.large'; $temporary_files[] = $large;
    file_put_contents($large, "%PDF-1.4\n" . str_repeat(' ', 5 * 1024 * 1024 - 9));
    ft_check(filesize($large) === 5 * 1024 * 1024 && ft_saved(ft_submit('candidate', $profile, 'upload_cv', ['cv' => new CURLFile($large, 'application/pdf', "$prefix-limit.pdf")])), 'A 5 MB PDF is accepted');
    file_put_contents($large, ' ', FILE_APPEND);
    $before_limit = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . tj_table('files') . ' WHERE user_id=%d', $users['candidate']));
    $too_large = ft_submit('candidate', $profile, 'upload_cv', ['cv' => new CURLFile($large, 'application/pdf', "$prefix-too-large.pdf")]);
    ft_check(str_contains($too_large['body'], 'CV phải là PDF') && $before_limit === (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . tj_table('files') . ' WHERE user_id=%d', $users['candidate'])), 'A PDF over 5 MB is rejected without a file record');
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
} finally { ft_cleanup(); }

<?php
/** Complete recruitment HTTP regression suite, with owned temporary fixtures. */
if (PHP_SAPI !== 'cli') { exit; }
require __DIR__ . '/recruitment-support.php';
$tag = 'E2E-' . bin2hex(random_bytes(4));
$marker = strtolower($tag);
$companyName = "$tag Atelier"; $jobName = "$tag PHP Platform Engineer"; $otherJobName = "$tag Platform Support";
$phone = '0900000000'; $ids = [];
$adminLogin = "$marker-admin"; $adminPass = $password; $candidatePass = $password;
$testLogins = ['candidate' => "$marker-a", 'other' => "$marker-b"];
function ft_has(array $response, string $text): bool { return str_contains(html_entity_decode($response['body']), $text); }
function ft_find_post(string $title, string $type): int {
    global $wpdb;
    $id = (int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_title=%s AND post_type=%s", $title, $type));
    if (!$id) { throw new RuntimeException("Missing post $title"); }
    return $id;
}
function ft_route(string $path, array $params): string { return $path . '?' . http_build_query($params + ['lang' => 'vi']); }
function make_pdf(string $path): void {
    $stream = 'BT /F1 16 Tf 64 720 Td (TechJob E2E - fictional test CV) Tj ET';
    $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', '<< /Length ' . strlen($stream) . " >>\nstream\n$stream\nendstream"];
    $pdf = "%PDF-1.4\n"; $xref = "0000000000 65535 f \n";
    foreach ($objects as $n => $object) {
        $xref .= sprintf("%010d 00000 n \n", strlen($pdf));
        $pdf .= ($n + 1) . " 0 obj\n$object\nendobj\n";
    }
    $offset = strlen($pdf);
    file_put_contents($path, $pdf . "xref\n0 6\n$xref" . "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$offset\n%%EOF\n");
}

make_pdf($pdf);
try {
    $admin = wp_insert_user(['user_login' => $adminLogin, 'user_email' => "$adminLogin@example.test", 'user_pass' => $password, 'role' => 'administrator']);
    if (is_wp_error($admin)) { throw new RuntimeException($admin->get_error_message()); }
    $users['admin'] = $admin;
    ft_check(ft_has(ft_request('guest', '/?lang=vi'), 'Giỏi công nghệ.'), 'Public homepage and Vietnamese UI');
    ft_check(ft_has(ft_request('guest', '/?lang=en'), 'Build your skills.'), 'English UI');
    ft_check(str_contains(ft_request('guest', '/quan-tri/')['url'], '/dang-nhap/'), 'Guest must sign in to view admin');
    $login = ft_submit('admin', ft_request('admin', '/dang-nhap/?lang=vi'), 'login', ['username' => $adminLogin, 'password' => $adminPass]);
    ft_check($login['code'] === 200 && str_contains($login['url'], '/quan-tri/'), 'Admin login and dashboard');
    if (!str_contains($login['url'], '/quan-tri/')) { throw new RuntimeException('Admin login blocked'); }

    $companyPage = ft_request('admin', '/quan-tri/cong-ty/?edit=new');
    ft_check(ft_saved(ft_submit('admin', $companyPage, 'save_company', ['title' => $companyName, 'title_en' => "$tag Systems", 'location' => 'Hà Nội', 'description' => 'Công ty giả lập cho kiểm thử.', 'featured' => '1'])), 'Admin creates company');
    $ids['company'] = ft_find_post($companyName, 'tj_company'); $posts[] = $ids['company'];

    $catalog = ft_request('admin', '/quan-tri/danh-muc/');
    foreach (['skill', 'category', 'position', 'location'] as $tax) {
        $termName = "$tag $tax";
        $predicate = "[.//input[@name='taxonomy' and @value='tj_$tax'] and not(.//input[@name='id'])]";
        ft_check(ft_saved(ft_submit('admin', $catalog, 'save_term', ['name' => $termName], $predicate)), "Admin creates $tax term");
        $ids[$tax] = (int)get_term_by('name', $termName, 'tj_' . $tax)->term_id; $terms[$tax] = $ids[$tax];
    }
    $jobBase = ['title' => $jobName, 'company_id' => $ids['company'], 'description' => 'Xây dựng dịch vụ PHP và kiểm thử quy trình.', 'requirements' => 'Kinh nghiệm PHP và SQL.', 'salary_min' => '15000000', 'salary_max' => '25000000', 'job_type' => 'remote', 'deadline' => wp_date('Y-m-d', time() + 7 * DAY_IN_SECONDS), 'featured' => '1', 'skill[]' => $ids['skill'], 'category' => $ids['category'], 'position' => $ids['position'], 'location' => $ids['location']];
    $editJob = ft_request('admin', '/quan-tri/viec-lam/?edit=new');
    $draft = ft_submit('admin', $editJob, 'save_job', $jobBase + ['status' => 'draft']);
    ft_check(ft_saved($draft) && ft_has($draft, $jobName) && ft_has($draft, 'Nháp'), 'Admin creates draft job');
    $job = $ids['job'] = ft_find_post($jobName, 'tj_job'); $posts[] = $job;
    $publicList = ft_route('/viec-lam/', ['q' => $tag]);
    ft_check(!ft_has(ft_request('guest', $publicList), $jobName) && !ft_has(ft_request('guest', '/?lang=vi'), $jobName), 'Draft hidden from search and homepage');
    $draftPath = '/viec-lam/' . sanitize_title($jobName) . '/';
    ft_check(ft_request('guest', $draftPath)['code'] === 404, 'Draft detail hidden from guests');
    $editJob = ft_request('admin', "/quan-tri/viec-lam/?edit=$job");
    ft_check(ft_saved(ft_submit('admin', $editJob, 'save_job', $jobBase + ['status' => 'publish'])), 'Admin publishes draft');
    clean_post_cache($job);
    $jobPath = '/viec-lam/' . get_post_field('post_name', $job) . '/';
    ft_check(ft_has(ft_request('guest', '/?lang=vi'), $jobName), 'Published job appears on home');
    ft_check(ft_has(ft_request('guest', $publicList), $jobName), 'Published job appears in search');
    $jobGuest = ft_request('guest', $jobPath . '?lang=vi');
    ft_check($jobGuest['code'] === 200 && ft_has($jobGuest, $companyName) && ft_has($jobGuest, 'Đăng nhập để ứng tuyển'), 'Published detail and guest sign-in link');
    ft_check(ft_has(ft_request('guest', ft_route('/viec-lam/', ['q' => $companyName])), $jobName), 'Search by company');
    ft_check(ft_has(ft_request('guest', ft_route('/viec-lam/', ['q' => $companyName, 'lang' => 'en'])), $jobName), 'Search by Vietnamese company in English UI');
    ft_check(ft_has(ft_request('guest', ft_route('/viec-lam/', ['q' => $tag, 'skill' => $ids['skill'], 'category' => $ids['category'], 'location' => $ids['location'], 'job_type' => 'remote', 'min_salary' => 20000000, 'max_salary' => 23000000])), $jobName), 'Combined filters match job');
    ft_check(!ft_has(ft_request('guest', ft_route('/viec-lam/', ['q' => $tag, 'min_salary' => 30000000])), $jobName), 'Nonmatching salary excludes job');
    ft_check(ft_has(ft_request('guest', ft_route('/viec-lam/', ['q' => $companyName, 'lang' => 'en'])), $jobName) && ft_has(ft_request('guest', ft_route('/viec-lam/', ['q' => "$tag Systems"])), $jobName), 'Both company names searchable in either language');
    ft_check(ft_has(ft_request('guest', ft_route('/viec-lam/', ['q' => "$tag skill"])), $jobName), 'Keyword search finds skill absent from title and description');
    ft_check(ft_has(ft_request('guest', '/cong-ty/?lang=vi'), $companyName) && ft_has(ft_request('guest', '/cong-ty/?lang=en'), "$tag Systems"), 'Company listing in VI and EN');

    foreach ($testLogins as $session => $loginName) {
        $registration = ft_submit($session, ft_request($session, '/dang-ky/?lang=vi'), 'register', ['full_name' => "$tag $session", 'username' => $loginName, 'email' => "$loginName@example.test", 'password' => $candidatePass]);
        ft_check(ft_saved($registration) && str_contains($registration['url'], '/tai-khoan/ho-so/'), "$session registers and gets signed in");
        $ids[$session] = get_user_by('login', $loginName)->ID; $users[$session] = $ids[$session];
    }
    ft_check(ft_has(ft_request('candidate', $jobPath), 'Hoàn thiện họ tên và số điện thoại'), 'Incomplete profile is prompted before applying');
    $profile = ft_request('candidate', '/tai-khoan/ho-so/');
    ft_check(ft_saved(ft_submit('candidate', $profile, 'profile', ['full_name' => "$tag Candidate", 'email' => "$testLogins[candidate]@example.test", 'phone' => $phone])), 'Candidate saves profile');
    ft_check(ft_request('candidate', '/quan-tri/')['code'] === 403, 'Candidate denied access to admin');
    ft_check(ft_submit('candidate', $profile, 'profile', ['_tj_nonce' => 'invalid'])['code'] === 403, 'Invalid nonce rejected');
    $bad = ft_submit('candidate', $profile, 'upload_cv', ['cv' => new CURLFile(__FILE__, 'application/pdf', "$marker-invalid.pdf")]);
    ft_check(ft_has($bad, 'CV phải là PDF'), 'Invalid PDF rejected');
    $fileName = "$marker-cv.pdf";
    $upload = ft_submit('candidate', $profile, 'upload_cv', ['cv' => new CURLFile($pdf, 'application/pdf', $fileName)]);
    ft_check(ft_saved($upload) && ft_has($upload, $fileName), 'Candidate uploads valid synthetic PDF from profile');
    $file = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . tj_table('files') . ' WHERE user_id=%d AND original_name=%s', $ids['candidate'], $fileName), ARRAY_A);
    $cv = $ids['cv'] = (int)$file['id'];
    $cvPath = "/tai-cv/$cv/";
    ft_check(ft_request('candidate', $cvPath)['body'] === file_get_contents($pdf), 'Candidate downloads original PDF bytes');
    ft_check(ft_request('guest', $cvPath)['type'] !== 'application/pdf', 'Guest cannot download private CV');
    ft_check(ft_request('other', $cvPath)['code'] === 403, 'Another candidate cannot download CV');
    ft_check(!str_starts_with(ft_request('guest', '/wp-content/uploads/' . $file['stored_name'])['body'], '%PDF-'), 'CV is absent from public uploads');

    $jobPage = ft_request('candidate', $jobPath);
    ft_check(ft_has($jobPage, 'Nộp hồ sơ'), 'Candidate sees apply form');
    ft_check(ft_request('candidate', $jobPath)['code'] === 200 && ft_has(ft_submit('candidate', $jobPage, 'save_job_toggle'), 'Bỏ lưu việc làm'), 'Candidate saves job');
    ft_check(ft_has(ft_request('candidate', '/tai-khoan/da-luu/'), $jobName), 'Saved job appears in candidate account');
    ft_check(!ft_has(ft_submit('candidate', $jobPage, 'save_job_toggle'), 'Bỏ lưu việc làm') && !ft_has(ft_request('candidate', '/tai-khoan/da-luu/'), $jobName), 'Candidate unsaves job');
    $apply = ['job_id' => $job, 'file_id' => $cv, 'letter' => "$tag Cover letter"];
    ft_check(ft_has(ft_submit('candidate', $jobPage, 'apply', $apply), 'đồng ý gửi hồ sơ'), 'Consent is enforced server-side');
    $applied = ft_submit('candidate', $jobPage, 'apply', $apply + ['consent' => '1']);
    ft_check(ft_saved($applied) && ft_has($applied, 'Mới nộp'), 'Candidate applies with saved CV');
    $app = $ids['app'] = (int)$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('applications') . ' WHERE job_id=%d AND user_id=%d AND file_id=%d', $job, $ids['candidate'], $cv));
    if (!$app) { throw new RuntimeException('Application missing from DB'); }
    $duplicate = ft_submit('candidate', $jobPage, 'apply', $apply + ['consent' => '1']);
    $count = (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . tj_table('applications') . ' WHERE job_id=%d', $job));
    ft_check(ft_has($duplicate, 'đã ứng tuyển') && $count === 1, 'Duplicate application rejected, one database row');
    $applyAgain = ft_request('candidate', $jobPath);
    ft_check(ft_has($applyAgain, 'Xem hồ sơ đã gửi'), 'Job page links to submitted application');
    $candidatePath = "/tai-khoan/ung-tuyen/?application=$app";
    ft_check(ft_request('other', $candidatePath)['code'] === 403, 'Another candidate cannot view application');
    ft_check(ft_has(ft_request('admin', '/quan-tri/ho-so/'), "$tag Candidate"), 'Admin finds candidate application');
    ft_check(ft_has(ft_request('admin', '/quan-tri/thong-bao/'), "Có hồ sơ ứng tuyển mới #$app"), 'Admin sees new application notification');
    $adminPath = "/quan-tri/ho-so/?application=$app";
    $review = ft_request('admin', $adminPath);
    ft_check(ft_has($review, $fileName) && ft_has($review, $apply['letter']) && ft_request('admin', $cvPath)['body'] === file_get_contents($pdf), 'Admin sees cover letter and downloads exact PDF');
    $note = "$tag Admin-only note";
    $updated = ft_submit('admin', $review, 'status', ['status' => 'reviewing', 'note' => $note]);
    ft_check(ft_saved($updated) && ft_has($updated, $note), 'Admin updates application and internal note');
    $view = ft_request('candidate', $candidatePath);
    ft_check(ft_has($view, 'Đang xem xét') && !ft_has($view, $note), 'Candidate sees updated history without internal note');
    $notify = ft_request('candidate', '/tai-khoan/thong-bao/');
    ft_check(ft_has($notify, "Hồ sơ #$app có cập nhật trạng thái"), 'Candidate receives status notification');
    ft_check(ft_saved(ft_submit('candidate', $notify, 'read_notifications')), 'Candidate marks notifications read');

    $schedulePage = ft_request('admin', "/quan-tri/lich-phong-van/?application=$app");
    $when = wp_date('Y-m-d\T09:00', time() + 3 * DAY_IN_SECONDS);
    $interviewInput = ['application_id' => $app, 'scheduled_at' => $when, 'location' => "$tag Interview Room", 'result' => 'pending'];
    $scheduled = ft_submit('admin', $schedulePage, 'save_interview', $interviewInput);
    ft_check(ft_saved($scheduled) && ft_has($scheduled, $interviewInput['location']), 'Admin schedules interview');
    $interview = $ids['interview'] = (int)$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('interviews') . ' WHERE application_id=%d', $app));
    ft_check(ft_has(ft_request('candidate', '/tai-khoan/lich-phong-van/'), $interviewInput['location']), 'Candidate sees interview schedule');
    $calendar = ft_request('candidate', "/lich-ics/$interview/");
    ft_check(str_contains($calendar['type'], 'text/calendar') && ft_has($calendar, 'BEGIN:VCALENDAR') && ft_has($calendar, 'STATUS:CONFIRMED'), 'Candidate downloads valid calendar response');
    ft_check(ft_request('other', "/lich-ics/$interview/")['code'] === 403, 'Another candidate cannot download schedule');
    $scheduleEdit = ft_request('admin', "/quan-tri/lich-phong-van/?edit=$interview");
    $newLocation = "$tag Updated Room";
    ft_check(ft_saved(ft_submit('admin', $scheduleEdit, 'save_interview', array_replace($interviewInput, ['location' => $newLocation, 'result' => 'passed']))), 'Admin edits interview and sets result');
    ft_check(tj_row('applications', $app)['status'] === 'passed' && ft_has(ft_request('candidate', '/tai-khoan/lich-phong-van/'), $newLocation), 'Interview result synchronizes application and location');
    $schedules = ft_request('admin', '/quan-tri/lich-phong-van/');
    ft_check(ft_saved(ft_submit('admin', $schedules, 'cancel_interview', [], "[.//input[@name='id' and @value='$interview']]")), 'Admin cancels interview');
    ft_check(tj_row('applications', $app)['status'] === 'reviewing' && ft_has(ft_request('candidate', "/lich-ics/$interview/"), 'STATUS:CANCELLED'), 'Cancellation reconciles status and .ics');

    $editJob = ft_request('admin', "/quan-tri/viec-lam/?edit=$job");
    ft_check(ft_saved(ft_submit('admin', $editJob, 'save_job', array_replace($jobBase, ['status' => 'publish', 'deadline' => wp_date('Y-m-d', time() - DAY_IN_SECONDS)]))), 'Admin sets elapsed deadline');
    ft_check(!ft_has(ft_request('guest', $publicList), $jobName) && ft_has(ft_request('guest', $jobPath), 'Tin đã đóng hoặc hết hạn'), 'Expired job leaves listing and stops applications');
    ft_check(ft_has(ft_submit('candidate', $jobPage, 'apply', $apply + ['consent' => '1']), 'Tin đã đóng hoặc hết hạn'), 'Server rejects stale form after deadline');
    ft_check(ft_saved(ft_submit('admin', $editJob, 'save_job', $jobBase + ['status' => 'private'])), 'Admin closes job');
    $closedDetail = ft_request('candidate', $candidatePath);
    ft_check(ft_has($closedDetail, $jobName) && !ft_has($closedDetail, "Private: $jobName"), 'Candidate history retains plain title after job is closed');
    ft_check(ft_request('guest', $jobPath)['code'] === 404 && !ft_has(ft_request('guest', $publicList), $jobName), 'Closed job hidden publicly');

    $extraJob = ft_submit('admin', ft_request('admin', '/quan-tri/viec-lam/?edit=new'), 'save_job', array_replace($jobBase, ['title' => $otherJobName, 'status' => 'publish', 'featured' => '0']));
    ft_check(ft_saved($extraJob), 'Admin publishes nonfeatured job');
    $ids['inlineJob'] = ft_find_post($otherJobName, 'tj_job'); $posts[] = $ids['inlineJob'];
    $inlinePath = '/viec-lam/' . get_post_field('post_name', $ids['inlineJob']) . '/';
    $inlinePage = ft_request('candidate', $inlinePath);
    $inlineFilename = "$marker-inline.pdf";
    $inline = ft_submit('candidate', $inlinePage, 'apply', ['consent' => '1', 'cv' => new CURLFile($pdf, 'application/pdf', $inlineFilename)]);
    $inlineApp = $wpdb->get_row($wpdb->prepare('SELECT a.id,a.file_id,f.original_name FROM ' . tj_table('applications') . ' a JOIN ' . tj_table('files') . ' f ON a.file_id=f.id WHERE a.user_id=%d AND a.job_id=%d', $ids['candidate'], $ids['inlineJob']), ARRAY_A);
    ft_check(ft_saved($inline) && ($inlineApp['original_name'] ?? '') === $inlineFilename, 'Inline CV upload attaches new file to application');
    $ids['inlineApp'] = (int)$inlineApp['id']; $ids['inlineCv'] = (int)$inlineApp['file_id'];

    $candidateProfile = ft_request('candidate', '/tai-khoan/ho-so/');
    preg_match('~href="([^"]+/dang-xuat/[^"]*)"~', $candidateProfile['body'], $logout);
    ft_check(ft_request('candidate', html_entity_decode($logout[1]))['code'] === 200 && str_contains(ft_request('candidate', '/tai-khoan/')['url'], '/dang-nhap/'), 'Candidate signs out and protected route redirects');
    $loginPage = ft_request('candidate', '/dang-nhap/?lang=vi');
    $wrongPassword = ft_submit('candidate', $loginPage, 'login', ['username' => $testLogins['candidate'], 'password' => 'wrong-password']);
    ft_check($wrongPassword['code'] === 200 && ft_has($wrongPassword, 'role="alert" class="tj-alert error">Thông tin đăng nhập không đúng'), 'Invalid password shows readable alert');
    ft_check(str_contains(ft_submit('candidate', $loginPage, 'login', ['username' => $testLogins['candidate'], 'password' => $candidatePass])['url'], '/tai-khoan/'), 'Candidate signs in again');
    $candidates = ft_request('admin', '/quan-tri/ung-vien/');
    $candidateId = $ids['candidate'];
    $predicate = "[.//input[@name='id' and @value='$candidateId']]";
    ft_check(ft_saved(ft_submit('admin', $candidates, 'save_user', [], $predicate)), 'Admin disables candidate');
    ft_check(str_contains(ft_request('candidate', '/tai-khoan/')['url'], '/dang-nhap/'), 'Disabling revokes candidate session');
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', '/quan-tri/ung-vien/'), 'save_user', [], $predicate)), 'Admin re-enables candidate');
    ft_check(str_contains(ft_submit('candidate', ft_request('candidate', '/dang-nhap/?lang=vi'), 'login', ['username' => $testLogins['candidate'], 'password' => $candidatePass])['url'], '/tai-khoan/'), 'Re-enabled candidate can sign in');
    $companyId = $ids['company'];
    $companyList = ft_request('admin', '/quan-tri/cong-ty/');
    ft_check(ft_has(ft_submit('admin', $companyList, 'delete_company', [], "[.//input[@name='id' and @value='$companyId']]"), 'Công ty đang có tin tuyển dụng'), 'Referenced company cannot be archived');
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', '/quan-tri/cong-ty/?edit=new'), 'save_company', ['title' => "$tag Spare Company"])), 'Create company for CRUD verification');
    $spareCompany = ft_find_post("$tag Spare Company", 'tj_company'); $posts[] = $spareCompany;
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', "/quan-tri/cong-ty/?edit=$spareCompany"), 'save_company', ['title' => "$tag Revised Company"])) && ft_has(ft_request('guest', '/cong-ty/?lang=vi'), "$tag Revised Company"), 'Edited company appears publicly');
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', '/quan-tri/cong-ty/'), 'delete_company', [], "[.//input[@name='id' and @value='$spareCompany']]")) && !ft_has(ft_request('guest', '/cong-ty/?lang=vi'), "$tag Revised Company"), 'Archive unreferenced company');
    $catalog = ft_request('admin', '/quan-tri/danh-muc/');
    $categoryForm = "[.//input[@name='taxonomy' and @value='tj_category'] and not(.//input[@name='id'])]";
    ft_check(ft_saved(ft_submit('admin', $catalog, 'save_term', ['name' => "$tag Spare Category"], $categoryForm)), 'Create unused category');
    $spareTerm = (int)get_term_by('name', "$tag Spare Category", 'tj_category')->term_id;
    $additional_terms[] = [$spareTerm, 'tj_category'];
    $termForm = "[.//input[@name='taxonomy' and @value='tj_category'] and .//input[@name='id' and @value='$spareTerm']]";
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', '/quan-tri/danh-muc/'), 'save_term', ['name' => "$tag Revised Category"], $termForm)) && ft_has(ft_request('guest', '/viec-lam/?lang=vi'), "$tag Revised Category"), 'Renamed category appears in public filters');
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', '/quan-tri/danh-muc/'), 'delete_term', [], $termForm)) && !ft_has(ft_request('guest', '/viec-lam/?lang=vi'), "$tag Revised Category"), 'Delete unused category');
    wp_set_current_user($users['admin']);
    $pageJobs = [];
    for ($i = 1; $i <= 10; $i++) {
        $pageJob = tj_save_job(['title' => "$tag Page Fixture $i", 'company_id' => $companyId, 'status' => 'publish', 'negotiable' => '1', 'description' => 'Pagination fixture', 'requirements' => 'Pagination fixture']);
        if (is_wp_error($pageJob)) { throw new RuntimeException($pageJob->get_error_message()); }
        $pageJobs[] = $pageJob; $posts[] = $pageJob;
    }
    $page1 = ft_request('guest', ft_route('/viec-lam/', ['q' => "$tag Page Fixture"]));
    $page2 = ft_request('guest', ft_route('/viec-lam/', ['q' => "$tag Page Fixture", 'pg' => 2]));
    ft_check(substr_count($page1['body'], 'class="saas-job-card"') === 9 && substr_count($page2['body'], 'class="saas-job-card"') === 1, 'Ten matching jobs paginate 9 plus 1');
    $archiveJob = $pageJobs[0];
    $archivePath = '/viec-lam/' . get_post_field('post_name', $archiveJob) . '/';
    ft_check(ft_saved(ft_submit('admin', ft_request('admin', ft_route('/quan-tri/viec-lam/', ['q' => "$tag Page Fixture 1"])), 'delete_job', [], "[.//input[@name='id' and @value='$archiveJob']]")) && ft_request('guest', $archivePath)['code'] === 404, 'Archived job becomes unavailable to guests');
    echo "PASS $checks recruitment E2E checks\n";
} finally { ft_cleanup(); }

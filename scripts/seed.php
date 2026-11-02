<?php
if (!defined('WP_CLI') || !WP_CLI || !function_exists('tj_activate')) { exit; }
if ((int)get_option('tj_seed_version') === 1) { WP_CLI::success('Demo data already exists; no duplicates created.'); return; }
global $wpdb;
function tj_seed_pdf(string $name): string {
    $stream = "BT /F1 18 Tf 50 770 Td (TechJob AI - DEMO CV) Tj 0 -36 Td /F1 12 Tf ($name) Tj 0 -28 Td (Fictional candidate. IT recruitment demonstration only.) Tj ET";
    $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>', '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', '<< /Length ' . strlen($stream) . ">>\nstream\n" . $stream . "\nendstream"];
    $pdf = "%PDF-1.4\n"; $offsets = [0];
    foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1) . " 0 obj\n$object\nendobj\n"; }
    $xref = strlen($pdf); $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    foreach (array_slice($offsets, 1) as $offset) { $pdf .= sprintf("%010d 00000 n \n", $offset); }
    return $pdf . "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
}
$terms = ['skill' => ['Java' => 'Java', 'Python' => 'Python', 'ReactJS' => 'ReactJS', 'SQL' => 'SQL', 'DevOps' => 'DevOps', 'PHP' => 'PHP', 'Node.js' => 'Node.js', 'Docker' => 'Docker', 'TypeScript' => 'TypeScript'], 'category' => ['Phát triển phần mềm' => 'Software development', 'Dữ liệu & AI' => 'Data & AI', 'Hạ tầng & Bảo mật' => 'Infrastructure & security', 'Kiểm thử phần mềm' => 'Quality assurance'], 'position' => ['Thực tập sinh' => 'Intern', 'Lập trình viên' => 'Developer', 'Kỹ sư cao cấp' => 'Senior engineer'], 'location' => ['Hồ Chí Minh' => 'Ho Chi Minh City', 'Hà Nội' => 'Hanoi', 'Đà Nẵng' => 'Da Nang', 'Làm việc từ xa' => 'Remote']];
$term_ids = [];
foreach ($terms as $tax => $items) { foreach ($items as $vi => $en) { $term = term_exists($vi, 'tj_' . $tax) ?: wp_insert_term($vi, 'tj_' . $tax); if (is_wp_error($term)) { WP_CLI::error($term->get_error_message()); } $term_ids[$tax][$vi] = (int)$term['term_id']; update_term_meta($term['term_id'], 'tj_name_en', $en); } }
$companies = [];
foreach (['Lotus Digital', 'Mekong Software', 'NovaCloud', 'Saigon Data Lab', 'GreenByte', 'Delta Tech'] as $i => $name) {
    $post = get_page_by_path(sanitize_title($name), OBJECT, 'tj_company');
    $id = $post ? $post->ID : wp_insert_post(['post_type' => 'tj_company', 'post_status' => 'publish', 'post_title' => $name, 'post_content' => "$name là công ty công nghệ mẫu trong bộ dữ liệu TechJob AI. Đội ngũ phát triển sản phẩm số, chú trọng chất lượng kỹ thuật và cơ hội học hỏi."]);
    foreach (['title_en' => $name, 'description_en' => "$name is a fictional technology company in the TechJob AI demo dataset. The team builds digital products and values engineering quality and continuous learning.", 'location' => array_keys($terms['location'])[$i % 3], 'featured' => 1] as $key => $value) { update_post_meta($id, 'tj_' . $key, $value); }
    $companies[] = $id;
}
$skills = array_keys($terms['skill']);
$jobs = [];
for ($i = 0; $i < 24; $i++) {
    $skill = $skills[$i % count($skills)]; $level = ['Junior', 'Middle', 'Senior'][$i % 3];
    $title = "$level $skill Developer"; $slug = 'demo-' . ($i + 1) . '-' . sanitize_title($title);
    $post = get_page_by_path($slug, OBJECT, 'tj_job');
    $id = $post ? $post->ID : wp_insert_post(['post_type' => 'tj_job', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => "<p>Tham gia đội ngũ xây dựng sản phẩm với $skill. Bạn sẽ thiết kế, phát triển và kiểm thử tính năng, phối hợp với đồng đội để đưa giải pháp đến người dùng.</p><ul><li>Phát triển tính năng và cải tiến chất lượng mã nguồn.</li><li>Tham gia code review và viết kiểm thử tự động.</li><li>Môi trường làm việc linh hoạt, có chương trình đào tạo và mentor.</li></ul>"]);
    $meta = ['title_en' => $title, 'description_en' => "<p>Join our product team using $skill. Design, implement and test features, working with your teammates to deliver reliable solutions.</p><ul><li>Build features and improve code quality.</li><li>Participate in code reviews and automated testing.</li><li>Flexible working environment with training and mentorship.</li></ul>", 'requirements' => "<ul><li>Nắm vững nền tảng $skill và tư duy giải quyết vấn đề.</li><li>Biết Git, SQL và quy trình làm việc nhóm.</li><li>Chủ động học hỏi, giao tiếp rõ ràng, có dự án thực tế.</li></ul>", 'requirements_en' => "<ul><li>Solid $skill fundamentals and problem-solving skills.</li><li>Knowledge of Git, SQL and team workflows.</li><li>Clear communication and practical project experience.</li></ul>", 'company_id' => $companies[$i % 6], 'salary_min' => (12 + $i % 5 * 5) * 1000000, 'salary_max' => (22 + $i % 5 * 5) * 1000000, 'negotiable' => $i % 9 === 8 ? 1 : 0, 'deadline' => wp_date('Y-m-d', time() + (20 + $i) * DAY_IN_SECONDS), 'featured' => $i < 6 ? 1 : 0, 'job_type' => ['full-time', 'full-time', 'remote', 'internship'][$i % 4]];
    foreach ($meta as $key => $value) { update_post_meta($id, 'tj_' . $key, $value); }
    wp_set_object_terms($id, [$term_ids['skill'][$skill], $term_ids['skill']['SQL']], 'tj_skill');
    foreach (['category', 'position', 'location'] as $tax) { $ids = array_values($term_ids[$tax]); wp_set_object_terms($id, [$ids[$i % count($ids)]], 'tj_' . $tax); }
    $jobs[] = $id;
}
$candidates = []; $files = [];
$credentials = tj_private_dir() . '/demo-accounts.txt';
for ($i = 1; $i <= 8; $i++) {
    $login = 'candidate' . sprintf('%02d', $i); $user = get_user_by('login', $login);
    if (!$user) {
        $password = wp_generate_password(20, true, false);
        $id = wp_insert_user(['user_login' => $login, 'user_pass' => $password, 'user_email' => $login . '@techjob.test', 'display_name' => 'Ứng viên mẫu ' . sprintf('%02d', $i), 'role' => 'tj_candidate']);
        if (is_wp_error($id)) { WP_CLI::error($id->get_error_message()); }
        file_put_contents($credentials, "\nCandidate: $login\nPassword: $password\n", FILE_APPEND); $user = get_user_by('id', $id);
    }
    update_user_meta($user->ID, 'tj_phone', '090000000' . $i);
    $file_id = (int)$wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('files') . ' WHERE user_id=%d LIMIT 1', $user->ID));
    if (!$file_id) {
        $stored = wp_generate_uuid4() . '.pdf'; $content = tj_seed_pdf('Demo candidate ' . $i); file_put_contents(tj_private_dir() . '/' . $stored, $content);
        $wpdb->insert(tj_table('files'), ['user_id' => $user->ID, 'original_name' => $login . '-demo-cv.pdf', 'stored_name' => $stored, 'mime' => 'application/pdf', 'size' => strlen($content), 'created_at' => tj_now()]); $file_id = (int)$wpdb->insert_id;
    }
    $candidates[] = $user->ID; $files[] = $file_id;
}
$statuses = array_keys(tj_statuses());
for ($i = 0; $i < 16; $i++) {
    $user = get_user_by('id', $candidates[$i % 8]);
    $existing = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('applications') . ' WHERE user_id=%d AND job_id=%d', $user->ID, $jobs[$i]));
    if ($existing) { continue; }
    $status = $statuses[$i % count($statuses)];
    $wpdb->insert(tj_table('applications'), ['job_id' => $jobs[$i], 'user_id' => $user->ID, 'file_id' => $files[$i % 8], 'candidate_name' => $user->display_name, 'email' => $user->user_email, 'phone' => get_user_meta($user->ID, 'tj_phone', true), 'cover_letter' => 'Hồ sơ mẫu phục vụ trình diễn TechJob AI.', 'status' => $status, 'note' => '', 'created_at' => gmdate('Y-m-d H:i:s', time() - $i * 3600), 'updated_at' => tj_now()]);
    $app_id = (int)$wpdb->insert_id;
    $wpdb->insert(tj_table('history'), ['application_id' => $app_id, 'actor_id' => $user->ID, 'old_status' => '', 'new_status' => 'submitted', 'note' => '', 'created_at' => tj_now()]);
    if ($status !== 'submitted') { $wpdb->insert(tj_table('history'), ['application_id' => $app_id, 'actor_id' => 1, 'old_status' => 'submitted', 'new_status' => $status, 'note' => 'Demo', 'created_at' => tj_now()]); }
    if ($status === 'interview') { $wpdb->insert(tj_table('interviews'), ['application_id' => $app_id, 'interviewer_id' => get_user_by('login', 'techjob_admin')->ID, 'scheduled_at' => gmdate('Y-m-d H:i:00', time() + (2 + $i) * DAY_IN_SECONDS), 'location' => 'Online · Phòng phỏng vấn TechJob AI', 'result' => 'pending', 'note' => '', 'created_at' => tj_now()]); }
    tj_notify($user->ID, 'Chào mừng đến với TechJob AI. Hồ sơ mẫu #' . $app_id, 'Welcome to TechJob AI. Demo application #' . $app_id, 'tai-khoan/ung-tuyen?application=' . $app_id);
}
update_option('tj_seed_version', 1);
WP_CLI::success('Seeded 6 companies, 24 IT jobs, 8 candidates and demo recruitment activity.');

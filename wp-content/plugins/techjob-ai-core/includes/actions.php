<?php
defined('ABSPATH') || exit;
function tj_post(string $key, string $default = ''): string { return is_scalar($_POST[$key] ?? null) ? sanitize_text_field(wp_unslash($_POST[$key])) : $default; }
function tj_back(string $path, $result = true): void {
    $args = is_wp_error($result) ? ['error' => $result->get_error_message()] : ['notice' => tj_t('Đã lưu thành công.', 'Saved successfully.')];
    wp_safe_redirect(add_query_arg($args, tj_url($path))); exit;
}
function tj_handle_action(): void {
    $action = tj_post('tj_action');
    if (!wp_verify_nonce(tj_post('_tj_nonce'), 'tj_' . $action)) { wp_die(tj_t('Phiên đã hết hạn. Tải lại trang rồi thử lại.', 'Session expired. Reload and try again.'), '', ['response' => 403]); }
    if (in_array($action, ['login', 'register'], true)) { tj_auth_action($action); }
    if (!is_user_logged_in()) { wp_safe_redirect(tj_url('dang-nhap')); exit; }
    $admin_actions = ['save_job', 'delete_job', 'save_company', 'delete_company', 'save_term', 'delete_term', 'status', 'save_interview', 'cancel_interview', 'save_user', 'save_settings'];
    if (in_array($action, $admin_actions, true) && !tj_admin()) { wp_die('Forbidden', '', ['response' => 403]); }
    if (in_array($action, ['apply', 'save_job_toggle', 'upload_cv'], true) && !tj_candidate()) { wp_die('Candidates only', '', ['response' => 403]); }
    global $wpdb;
    switch ($action) {
        case 'profile':
            $name = tj_post('full_name'); $email = sanitize_email(tj_post('email')); $phone = tj_post('phone');
            if (!$name || !is_email($email) || !preg_match('/^[+0-9 ()-]{8,25}$/', $phone)) { tj_back('tai-khoan/ho-so', new WP_Error('profile', tj_t('Kiểm tra họ tên, email và số điện thoại.', 'Check your name, email and phone.'))); }
            $result = wp_update_user(['ID' => get_current_user_id(), 'display_name' => $name, 'user_email' => $email]);
            if (!is_wp_error($result)) { update_user_meta(get_current_user_id(), 'tj_phone', $phone); tj_audit('update_profile', get_current_user_id()); }
            tj_back('tai-khoan/ho-so', $result);
        case 'upload_cv': tj_back('tai-khoan/ho-so', tj_upload_cv($_FILES['cv'] ?? []));
        case 'save_job_toggle':
            $job = absint(tj_post('job_id'));
            if (get_post_type($job) !== 'tj_job' || get_post_status($job) !== 'publish') { tj_back('viec-lam', new WP_Error('job', 'Job not found')); }
            $where = ['user_id' => get_current_user_id(), 'job_id' => $job];
            $exists = $wpdb->get_var($wpdb->prepare('SELECT id FROM ' . tj_table('saved') . ' WHERE user_id=%d AND job_id=%d', get_current_user_id(), $job));
            if ($exists) { $wpdb->delete(tj_table('saved'), $where); } else { $wpdb->insert(tj_table('saved'), $where + ['created_at' => tj_now()]); }
            wp_safe_redirect(tj_job_url($job)); exit;
        case 'apply':
            $file = absint(tj_post('file_id'));
            if (!empty($_FILES['cv']['name'])) { $file = tj_upload_cv($_FILES['cv']); if (is_wp_error($file)) { tj_back('tai-khoan/ho-so', $file); } }
            $result = tj_apply(absint(tj_post('job_id')), (int)$file, tj_post('letter'), tj_post('consent') === '1');
            tj_back('tai-khoan/ung-tuyen', $result);
        case 'status':
            $id = absint(tj_post('application_id')); $status = tj_post('status'); $app = tj_row('applications', $id);
            if (!$app || !isset(tj_statuses()[$status])) { tj_back('quan-tri/ho-so', new WP_Error('status', 'Invalid status')); }
            if ($wpdb->query('START TRANSACTION') === false) { tj_back('quan-tri/ho-so', new WP_Error('database', tj_t('Không thể cập nhật hồ sơ lúc này.', 'Could not update the application.'))); }
            $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
            if ($wpdb->update(tj_table('applications'), ['note' => $note], ['id' => $id]) === false || !tj_change_status($id, $status, $note)) {
                $wpdb->query('ROLLBACK');
                tj_back('quan-tri/ho-so?application=' . $id, new WP_Error('database', tj_t('Không thể cập nhật hồ sơ lúc này. Chưa có thay đổi nào được lưu.', 'Could not update the application. No changes were saved.')));
            }
            if ($wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); tj_back('quan-tri/ho-so?application=' . $id, new WP_Error('database', tj_t('Không thể hoàn tất cập nhật hồ sơ.', 'Could not complete the application update.'))); }
            tj_back('quan-tri/ho-so?application=' . $id);
        case 'save_interview': tj_back('quan-tri/lich-phong-van', tj_save_interview(wp_unslash($_POST)));
        case 'cancel_interview': tj_back('quan-tri/lich-phong-van', tj_cancel_interview(absint(tj_post('id'))));
        case 'read_notifications':
            $wpdb->update(tj_table('notifications'), ['is_read' => 1], ['user_id' => get_current_user_id()]);
            tj_back(tj_admin() ? 'quan-tri/thong-bao' : 'tai-khoan/thong-bao');
        case 'save_job': tj_back('quan-tri/viec-lam', tj_save_job(wp_unslash($_POST)));
        case 'delete_job':
            $id = absint(tj_post('id'));
            if (get_post_type($id) === 'tj_job') { wp_trash_post($id); tj_audit('archive_job', $id); }
            tj_back('quan-tri/viec-lam');
        case 'save_company': tj_back('quan-tri/cong-ty', tj_save_company(wp_unslash($_POST)));
        case 'delete_company':
            $id = absint(tj_post('id'));
            $used = get_posts(['post_type' => 'tj_job', 'post_status' => 'any', 'meta_key' => 'tj_company_id', 'meta_value' => $id, 'numberposts' => 1]);
            if ($used) { tj_back('quan-tri/cong-ty', new WP_Error('used', tj_t('Công ty đang có tin tuyển dụng.', 'Company is referenced by jobs.'))); }
            if (get_post_type($id) === 'tj_company') { wp_trash_post($id); }
            tj_back('quan-tri/cong-ty');
        case 'save_term':
            $tax = tj_post('taxonomy'); $id = absint(tj_post('id'));
            if (!in_array($tax, ['tj_skill', 'tj_category', 'tj_position', 'tj_location'], true)) { tj_back('quan-tri/danh-muc', new WP_Error('tax', 'Invalid taxonomy')); }
            $result = $id ? wp_update_term($id, $tax, ['name' => tj_post('name')]) : wp_insert_term(tj_post('name'), $tax);
            if (!is_wp_error($result) && array_key_exists('name_en', $_POST)) { update_term_meta($result['term_id'], 'tj_name_en', tj_post('name_en')); }
            tj_back('quan-tri/danh-muc', $result);
        case 'delete_term':
            $tax = tj_post('taxonomy'); $term = get_term(absint(tj_post('id')), $tax);
            if (!in_array($tax, ['tj_skill', 'tj_category', 'tj_position', 'tj_location'], true) || !$term || is_wp_error($term) || $term->count > 0) { tj_back('quan-tri/danh-muc', new WP_Error('used', tj_t('Danh mục đang được dùng hoặc không hợp lệ.', 'Category is in use or invalid.'))); }
            tj_back('quan-tri/danh-muc', wp_delete_term($term->term_id, $tax));
        case 'save_user':
            $id = absint(tj_post('id')); $user = get_user_by('id', $id);
            if (!$user || !in_array('tj_candidate', $user->roles, true)) { tj_back('quan-tri/ung-vien', new WP_Error('user', tj_t('Chỉ được quản lý tài khoản ứng viên tại đây.', 'Only candidate accounts can be managed here.'))); }
            update_user_meta($id, 'tj_disabled', tj_post('disabled') === '1' ? 1 : 0);
            if (tj_post('disabled') === '1') { WP_Session_Tokens::get_instance($id)->destroy_all(); }
            tj_audit('update_candidate_access', $id); tj_back('quan-tri/ung-vien');
        case 'save_settings':
            $model = tj_post('model', 'gemini-flash-latest');
            if (!preg_match('/^[a-zA-Z0-9.-]{1,100}$/', $model)) { tj_back('quan-tri/cai-dat', new WP_Error('model', 'Invalid model')); }
            update_option('tj_ai_model', $model, false);
            update_option('tj_ai_prompt', mb_substr(sanitize_textarea_field(wp_unslash($_POST['prompt'] ?? '')), 0, 3000), false);
            update_option('tj_ai_demo', tj_post('demo') === '1');
            tj_back('quan-tri/cai-dat');
        default: wp_die('Unknown action', '', ['response' => 400]);
    }
}
function tj_auth_action(string $action): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
    if (!tj_limit('auth:' . $ip, 20, 900)) { tj_back('dang-nhap', new WP_Error('limit', tj_t('Quá nhiều lần thử. Chờ 15 phút.', 'Too many attempts. Wait 15 minutes.'))); }
    if ($action === 'register') {
        $name = tj_post('full_name'); $login = tj_post('username'); $email = sanitize_email(tj_post('email')); $pass = (string)wp_unslash($_POST['password'] ?? '');
        if (!$name || !preg_match('/^[a-zA-Z0-9_.-]{3,40}$/', $login) || !is_email($email) || strlen($pass) < 10 || strlen($pass) > 256) { tj_back('dang-ky', new WP_Error('register', tj_t('Tên đăng nhập 3–40 ký tự, email hợp lệ, mật khẩu 10–256 ký tự.', 'Username: 3–40 characters; valid email; password: 10–256 characters.'))); }
        $id = wp_insert_user(['user_login' => $login, 'user_email' => $email, 'user_pass' => $pass, 'display_name' => $name, 'role' => 'tj_candidate']);
        if (is_wp_error($id)) { tj_back('dang-ky', new WP_Error('exists', tj_t('Tên đăng nhập hoặc email đã được dùng.', 'Username or email is already in use.'))); }
        wp_set_current_user($id); wp_set_auth_cookie($id, false, is_ssl()); tj_back('tai-khoan/ho-so');
    }
    $user = wp_signon(['user_login' => tj_post('username'), 'user_password' => (string)wp_unslash($_POST['password'] ?? ''), 'remember' => false], is_ssl());
    if (is_wp_error($user)) { tj_back('dang-nhap', new WP_Error('login', tj_t('Thông tin đăng nhập không đúng hoặc tài khoản bị khóa.', 'Invalid credentials or disabled account.'))); }
    wp_set_current_user($user->ID);
    $next = tj_login_target(tj_post('next'), $user);
    wp_safe_redirect($next); exit;
}
function tj_login_target(string $next, WP_User $user): string {
    $fallback = tj_url(user_can($user, 'manage_options') ? 'quan-tri' : 'tai-khoan');
    return $next !== '' ? (wp_validate_redirect($next, $fallback) ?: $fallback) : $fallback;
}
function tj_save_job(array $input) {
    if (!tj_admin()) { return new WP_Error('forbidden', 'Forbidden'); }
    $id = absint($input['id'] ?? 0);
    if ($id && get_post_type($id) !== 'tj_job') { return new WP_Error('job', 'Invalid job'); }
    $title = sanitize_text_field($input['title'] ?? ''); $status = sanitize_key($input['status'] ?? 'draft');
    $min = absint($input['salary_min'] ?? 0); $max = absint($input['salary_max'] ?? 0); $negotiable = empty($input['negotiable']) ? 0 : 1;
    $company = absint($input['company_id'] ?? 0); $deadline = sanitize_text_field($input['deadline'] ?? '');
    $date = $deadline ? DateTimeImmutable::createFromFormat('!Y-m-d', $deadline, wp_timezone()) : false;
    if (!$title || !in_array($status, ['publish', 'draft', 'private'], true) || get_post_type($company) !== 'tj_company' || get_post_status($company) !== 'publish' || (!$negotiable && ($min < 1 || $max < $min)) || ($deadline && (!$date || $date->format('Y-m-d') !== $deadline))) { return new WP_Error('job', tj_t('Kiểm tra tiêu đề, công ty, lương và hạn nộp.', 'Check title, company, salary and deadline.')); }
    $description = wp_kses_post($input['description'] ?? ''); $requirements = wp_kses_post($input['requirements'] ?? '');
    if (!trim(wp_strip_all_tags($description)) || !trim(wp_strip_all_tags($requirements))) { return new WP_Error('content', tj_t('Nhập mô tả và yêu cầu công việc.', 'Enter job description and requirements.')); }
    $id = wp_insert_post(['ID' => $id, 'post_type' => 'tj_job', 'post_status' => $status, 'post_title' => $title, 'post_content' => $description], true);
    if (is_wp_error($id)) { return $id; }
    $meta = ['company_id' => $company, 'salary_min' => $min, 'salary_max' => $max, 'negotiable' => $negotiable, 'deadline' => $deadline, 'requirements' => $requirements, 'job_type' => in_array($input['job_type'] ?? '', ['full-time', 'part-time', 'internship', 'remote'], true) ? $input['job_type'] : 'full-time', 'featured' => empty($input['featured']) ? 0 : 1];
    foreach ($meta as $key => $value) { update_post_meta($id, 'tj_' . $key, $value); }
    foreach (['title_en' => 'text', 'description_en' => 'html', 'requirements_en' => 'html'] as $key => $type) {
        if (!array_key_exists($key, $input)) { continue; }
        $value = $type === 'text' ? sanitize_text_field($input[$key]) : wp_kses_post($input[$key]);
        update_post_meta($id, 'tj_' . $key, $value);
    }
    foreach (['skill', 'category', 'position', 'location'] as $tax) {
        $ids = array_map('absint', (array)($input[$tax] ?? []));
        $ids = array_values(array_filter($ids, fn($term) => term_exists($term, 'tj_' . $tax)));
        wp_set_object_terms($id, $ids, 'tj_' . $tax);
    }
    tj_audit('save_job', $id); return $id;
}
function tj_save_company(array $input) {
    $id = absint($input['id'] ?? 0); $name = sanitize_text_field($input['title'] ?? '');
    if (!tj_admin() || !$name || ($id && get_post_type($id) !== 'tj_company')) { return new WP_Error('company', tj_t('Tên công ty không hợp lệ.', 'Invalid company name.')); }
    $id = wp_insert_post(['ID' => $id, 'post_type' => 'tj_company', 'post_status' => 'publish', 'post_title' => $name, 'post_content' => wp_kses_post($input['description'] ?? '')], true);
    if (is_wp_error($id)) { return $id; }
    foreach (['title_en', 'description_en', 'website', 'location', 'featured'] as $key) { update_post_meta($id, 'tj_' . $key, $key === 'website' ? esc_url_raw($input[$key] ?? '', ['http', 'https']) : ($key === 'description_en' ? wp_kses_post($input[$key] ?? '') : sanitize_text_field($input[$key] ?? ''))); }
    tj_audit('save_company', $id); return $id;
}

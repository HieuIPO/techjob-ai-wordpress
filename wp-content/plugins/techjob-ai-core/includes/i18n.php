<?php
defined('ABSPATH') || exit;
function tj_lang(): string {
    $value = $_GET['lang'] ?? ($_COOKIE['tj_lang'] ?? 'vi');
    $lang = is_scalar($value) ? sanitize_key($value) : 'vi';
    return $lang === 'en' ? 'en' : 'vi';
}
/** Literal pairs keep translations reviewable; no user text is executed. */
function tj_t(string $vi, string $en = ''): string { return tj_lang() === 'en' && $en !== '' ? $en : $vi; }
function tj_e(string $vi, string $en = ''): void { echo esc_html(tj_t($vi, $en)); }
function tj_statuses(): array {
    return ['submitted' => tj_t('Mới nộp', 'Submitted'), 'reviewing' => tj_t('Đang xem xét', 'In review'), 'interview' => tj_t('Mời phỏng vấn', 'Interview'), 'passed' => tj_t('Đạt', 'Passed'), 'rejected' => tj_t('Không đạt', 'Rejected')];
}
function tj_results(): array {
    return ['pending' => tj_t('Chưa có kết quả', 'Pending'), 'passed' => tj_t('Đạt', 'Passed'), 'rejected' => tj_t('Không đạt', 'Rejected'), 'absent' => tj_t('Ứng viên không tham gia', 'No show')];
}
function tj_job_types(): array {
    return ['full-time' => tj_t('Toàn thời gian', 'Full-time'), 'part-time' => tj_t('Bán thời gian', 'Part-time'), 'internship' => tj_t('Thực tập', 'Internship'), 'remote' => tj_t('Làm việc từ xa', 'Remote')];
}
function tj_action_label(string $action): string {
    $labels = ['save_job' => tj_t('Cập nhật tin tuyển dụng', 'Updated job'), 'archive_job' => tj_t('Xóa tin tuyển dụng', 'Archived job'), 'save_company' => tj_t('Cập nhật công ty', 'Updated company'), 'submit_application' => tj_t('Nộp hồ sơ', 'Submitted application'), 'change_application_status' => tj_t('Cập nhật trạng thái hồ sơ', 'Changed application status'), 'save_interview' => tj_t('Cập nhật lịch phỏng vấn', 'Updated interview'), 'cancel_interview' => tj_t('Hủy lịch phỏng vấn', 'Cancelled interview'), 'update_profile' => tj_t('Cập nhật hồ sơ cá nhân', 'Updated profile'), 'upload_cv' => tj_t('Tải CV lên', 'Uploaded CV'), 'update_candidate_access' => tj_t('Cập nhật quyền truy cập ứng viên', 'Updated candidate access')];
    return $labels[$action] ?? tj_t('Cập nhật hệ thống', 'System update');
}
function tj_url(string $path = '', array $args = []): string {
    $parts = explode('?', $path, 2); $query = [];
    if (isset($parts[1])) { parse_str($parts[1], $query); }
    return add_query_arg(array_merge(['lang' => tj_lang()], $query, $args), home_url('/' . trim($parts[0], '/') . ($parts[0] ? '/' : '')));
}
function tj_meta(int $id, string $key, $default = '') { $value = get_post_meta($id, 'tj_' . $key, true); return $value === '' ? $default : $value; }
function tj_title(int $id): string { return tj_lang() === 'en' ? tj_meta($id, 'title_en', get_the_title($id)) : get_the_title($id); }
function tj_content(int $id, string $key = 'description'): string {
    $vi = $key === 'description' ? get_post_field('post_content', $id) : tj_meta($id, $key);
    return tj_lang() === 'en' ? tj_meta($id, $key . '_en', $vi) : $vi;
}
function tj_term_name($term): string { return tj_lang() === 'en' ? (get_term_meta($term->term_id, 'tj_name_en', true) ?: $term->name) : $term->name; }

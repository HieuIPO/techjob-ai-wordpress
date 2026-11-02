<?php
defined('ABSPATH') || exit;
function tj_private_dir(): string {
    // Deliberately outside ABSPATH: never publish CVs in the media library.
    return defined('TECHJOB_PRIVATE_DIR') ? TECHJOB_PRIVATE_DIR : dirname(rtrim(ABSPATH, '/')) . '/techjob-private';
}
function tj_upload_cv(array $file) {
    global $wpdb;
    if (!is_user_logged_in()) { return new WP_Error('auth', tj_t('Vui lòng đăng nhập.', 'Please sign in.')); }
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) { return new WP_Error('file', tj_t('Vui lòng chọn CV hợp lệ.', 'Choose a valid CV.')); }
    $size = filesize($file['tmp_name']);
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    $types = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if ($size < 1 || $size > 5 * 1024 * 1024 || !isset($types[$extension]) || !tj_file_signature($file['tmp_name'], $extension)) {
        return new WP_Error('file', tj_t('CV phải là PDF, DOC hoặc DOCX hợp lệ, tối đa 5 MB.', 'CV must be a valid PDF, DOC or DOCX, up to 5 MB.'));
    }
    $dir = tj_private_dir();
    if (!wp_mkdir_p($dir) || !is_writable($dir)) { return new WP_Error('storage', tj_t('Kho CV chưa sẵn sàng.', 'CV storage is unavailable.')); }
    $stored = wp_generate_uuid4() . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) { return new WP_Error('storage', tj_t('Không thể lưu CV.', 'Could not save CV.')); }
    chmod($dir . '/' . $stored, 0600);
    $ok = $wpdb->insert(tj_table('files'), ['user_id' => get_current_user_id(), 'original_name' => sanitize_file_name($file['name']), 'stored_name' => $stored, 'mime' => $types[$extension], 'size' => $size, 'created_at' => tj_now()]);
    if (!$ok) { unlink($dir . '/' . $stored); return new WP_Error('storage', tj_t('Không thể ghi nhận CV.', 'Could not register CV.')); }
    tj_audit('upload_cv', (int)$wpdb->insert_id);
    return (int)$wpdb->insert_id;
}
function tj_can_read_file(array $file): bool { return is_user_logged_in() && (tj_admin() || (int)$file['user_id'] === get_current_user_id()); }
function tj_download_cv(int $id): void {
    $file = tj_row('files', $id);
    if (!$file || !tj_can_read_file($file)) { status_header(403); wp_die(tj_t('Bạn không có quyền tải CV này.', 'You cannot download this CV.')); }
    $dir = realpath(tj_private_dir());
    $path = realpath(tj_private_dir() . '/' . basename($file['stored_name']));
    if (!$dir || !$path || !str_starts_with($path, $dir . DIRECTORY_SEPARATOR) || !is_file($path)) { status_header(404); wp_die('CV unavailable'); }
    nocache_headers();
    header('X-Content-Type-Options: nosniff');
    header('Content-Type: ' . $file['mime']);
    header('Content-Disposition: attachment; filename="CV.' . pathinfo($path, PATHINFO_EXTENSION) . '"; filename*=UTF-8\'\'' . rawurlencode($file['original_name']));
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

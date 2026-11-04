<?php
defined('ABSPATH') || exit;
function tj_apply(int $job, int $file_id, string $letter, bool $consent) {
    global $wpdb;
    if (!tj_candidate()) { return new WP_Error('forbidden', tj_t('Chỉ ứng viên được nộp hồ sơ.', 'Only candidates can apply.')); }
    $file = tj_row('files', $file_id);
    if (!$consent || !$file || (int)$file['user_id'] !== get_current_user_id()) { return new WP_Error('file', tj_t('Chọn CV của bạn và đồng ý gửi hồ sơ.', 'Select your CV and consent to submit.')); }
    $user = wp_get_current_user();
    $phone = get_user_meta($user->ID, 'tj_phone', true);
    if (!$user->display_name || !$phone) { return new WP_Error('profile', tj_t('Cập nhật họ tên và số điện thoại trước khi ứng tuyển.', 'Complete your name and phone before applying.')); }
    if ($wpdb->query('START TRANSACTION') === false) { return new WP_Error('database', tj_t('Không thể nộp hồ sơ lúc này.', 'Could not submit the application.')); }
    // Lock the job row so closing the job and applying cannot interleave.
    $status = $wpdb->get_var($wpdb->prepare("SELECT post_status FROM {$wpdb->posts} WHERE ID=%d AND post_type='tj_job' FOR UPDATE", $job));
    if (!$status || !tj_open_policy($status, (string)tj_meta($job, 'deadline'), wp_date('Y-m-d'))) { $wpdb->query('ROLLBACK'); return new WP_Error('closed', tj_t('Tin đã đóng hoặc hết hạn.', 'This job is closed or expired.')); }
    $ok = $wpdb->insert(tj_table('applications'), ['job_id' => $job, 'user_id' => $user->ID, 'file_id' => $file_id, 'candidate_name' => $user->display_name, 'email' => $user->user_email, 'phone' => $phone, 'cover_letter' => mb_substr(sanitize_textarea_field($letter), 0, 5000), 'status' => 'submitted', 'note' => '', 'created_at' => tj_now(), 'updated_at' => tj_now()]);
    if ($ok === false) { $wpdb->query('ROLLBACK'); return new WP_Error('duplicate', tj_t('Bạn đã ứng tuyển công việc này hoặc dữ liệu chưa lưu được.', 'You already applied, or the application could not be saved.')); }
    $id = (int)$wpdb->insert_id;
    if ($wpdb->insert(tj_table('history'), ['application_id' => $id, 'actor_id' => $user->ID, 'old_status' => '', 'new_status' => 'submitted', 'note' => '', 'created_at' => tj_now()]) === false) { $wpdb->query('ROLLBACK'); return new WP_Error('database', tj_t('Không thể lưu lịch sử hồ sơ.', 'Could not save application history.')); }
    foreach (get_users(['role' => 'administrator', 'fields' => 'ID']) as $admin) { if (!tj_notify((int)$admin, 'Có hồ sơ ứng tuyển mới #' . $id, 'New application #' . $id, 'quan-tri/ho-so?application=' . $id)) { $wpdb->query('ROLLBACK'); return new WP_Error('database', tj_t('Không thể tạo thông báo ứng tuyển.', 'Could not create the application notification.')); } }
    if (!tj_audit('submit_application', $id) || $wpdb->query('COMMIT') === false) { $wpdb->query('ROLLBACK'); return new WP_Error('database', tj_t('Không thể hoàn tất nộp hồ sơ.', 'Could not complete the application submission.')); }
    return $id;
}
function tj_change_status(int $id, string $status, string $note = ''): bool {
    global $wpdb;
    $app = tj_row('applications', $id);
    if (!$app || !isset(tj_statuses()[$status])) { return false; }
    if ($app['status'] === $status) { return true; }
    if ($wpdb->update(tj_table('applications'), ['status' => $status, 'updated_at' => tj_now()], ['id' => $id]) === false) { return false; }
    if ($wpdb->insert(tj_table('history'), ['application_id' => $id, 'actor_id' => get_current_user_id(), 'old_status' => $app['status'], 'new_status' => $status, 'note' => $note, 'created_at' => tj_now()]) === false) { return false; }
    if (!tj_notify((int)$app['user_id'], 'Hồ sơ #' . $id . ' có cập nhật trạng thái.', 'Application #' . $id . ' status updated.', 'tai-khoan/ung-tuyen?application=' . $id)) { return false; }
    if (!tj_audit('change_application_status', $id)) { return false; }
    return true;
}
function tj_save_interview(array $input) {
    global $wpdb;
    if (!tj_admin()) { return new WP_Error('forbidden', 'Forbidden'); }
    $id = absint($input['id'] ?? 0);
    $existing = $id ? tj_row('interviews', $id) : null;
    if ($id && (!$existing || $existing['cancelled'])) { return new WP_Error('missing', tj_t('Lịch không tồn tại hoặc đã hủy.', 'Interview is missing or cancelled.')); }
    $app_id = $existing ? (int)$existing['application_id'] : absint($input['application_id'] ?? 0);
    $app = tj_row('applications', $app_id);
    $result = sanitize_key($input['result'] ?? 'pending');
    if (!$app || !isset(tj_results()[$result])) { return new WP_Error('invalid', tj_t('Hồ sơ hoặc kết quả không hợp lệ.', 'Invalid application or result.')); }
    if (!$existing && in_array($app['status'], ['passed', 'rejected'], true)) { return new WP_Error('final', tj_t('Mở lại hồ sơ trước khi tạo lịch.', 'Reopen this application before scheduling.')); }
    $date_input = sanitize_text_field($input['scheduled_at'] ?? '');
    try { $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $date_input, wp_timezone()); } catch (Exception $e) { $date = false; }
    if (!$date || $date->format('Y-m-d\TH:i') !== $date_input || (!$existing && $date->getTimestamp() <= time()) || (!$existing && $result !== 'pending')) { return new WP_Error('date', tj_t('Thời gian không hợp lệ. Lịch mới phải ở tương lai và chưa có kết quả.', 'Invalid date. New interviews must be in the future with a pending result.')); }
    $values = ['application_id' => $app_id, 'interviewer_id' => get_current_user_id(), 'scheduled_at' => $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'), 'location' => sanitize_text_field($input['location'] ?? ''), 'result' => $result, 'note' => sanitize_textarea_field($input['note'] ?? '')];
    if (!$values['location']) { return new WP_Error('location', tj_t('Nhập địa điểm hoặc link phỏng vấn.', 'Enter a location or interview link.')); }
    if ($wpdb->query('START TRANSACTION') === false) { return new WP_Error('database', tj_t('Không thể lưu lịch lúc này.', 'Could not save the interview.')); }
    $ok = $id ? $wpdb->update(tj_table('interviews'), $values, ['id' => $id]) : $wpdb->insert(tj_table('interviews'), $values + ['created_at' => tj_now()]);
    if ($ok === false) { $wpdb->query('ROLLBACK'); return new WP_Error('duplicate', tj_t('Hồ sơ đã có lịch tại thời điểm này.', 'This application already has an interview at that time.')); }
    $id = $id ?: (int)$wpdb->insert_id;
    $status_ok = true;
    if (in_array($result, ['passed', 'rejected'], true)) { $status_ok = tj_change_status($app_id, $result); }
    elseif (!$existing || ($existing['result'] !== $result && $app['status'] === $existing['result'])) { $status_ok = tj_reconcile_interviews($app_id, $id, true); }
    if (!$status_ok || !tj_notify((int)$app['user_id'], 'Lịch phỏng vấn hồ sơ #' . $app_id . ' đã cập nhật.', 'Interview for application #' . $app_id . ' updated.', 'tai-khoan/lich-phong-van') || !tj_audit('save_interview', $id) || $wpdb->query('COMMIT') === false) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('database', tj_t('Không thể hoàn tất cập nhật lịch. Chưa có thay đổi nào được lưu.', 'Could not complete the interview update. No changes were saved.'));
    }
    return $id;
}
function tj_reconcile_interviews(int $app_id, int $excluded, bool $active): bool {
    global $wpdb;
    $wpdb->last_error = '';
    $results = $wpdb->get_col($wpdb->prepare('SELECT result FROM ' . tj_table('interviews') . ' WHERE application_id=%d AND id<>%d AND cancelled=0 ORDER BY scheduled_at DESC,id DESC', $app_id, $excluded));
    if ($wpdb->last_error !== '') { return false; }
    return tj_change_status($app_id, tj_interview_status($results, $active));
}
function tj_cancel_interview(int $id) {
    global $wpdb;
    if (!tj_admin()) { return new WP_Error('forbidden', 'Forbidden'); }
    $interview = tj_row('interviews', $id);
    if (!$interview || $interview['cancelled']) { return new WP_Error('missing', tj_t('Lịch đã hủy hoặc không tồn tại.', 'Interview already cancelled or missing.')); }
    $app = tj_row('applications', (int)$interview['application_id']);
    if ($wpdb->query('START TRANSACTION') === false) { return new WP_Error('database', tj_t('Không thể hủy lịch lúc này.', 'Could not cancel the interview.')); }
    if ($wpdb->update(tj_table('interviews'), ['cancelled' => 1, 'active_slot' => null], ['id' => $id]) === false) { $wpdb->query('ROLLBACK'); return new WP_Error('database', tj_t('Không thể hủy lịch phỏng vấn.', 'Could not cancel the interview.')); }
    if ($app && ($app['status'] === 'interview' || (in_array($interview['result'], ['passed', 'rejected'], true) && $app['status'] === $interview['result']))) {
        if (!tj_reconcile_interviews((int)$app['id'], $id, false)) { $wpdb->query('ROLLBACK'); return new WP_Error('database', tj_t('Không thể đồng bộ trạng thái hồ sơ.', 'Could not synchronize the application status.')); }
    }
    if (($app && !tj_notify((int)$app['user_id'], 'Một lịch phỏng vấn đã hủy.', 'An interview was cancelled.', 'tai-khoan/lich-phong-van')) || !tj_audit('cancel_interview', $id) || $wpdb->query('COMMIT') === false) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('database', tj_t('Không thể hoàn tất hủy lịch.', 'Could not complete the interview cancellation.'));
    }
    return true;
}

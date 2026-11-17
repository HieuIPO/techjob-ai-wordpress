<?php
defined('ABSPATH') || exit;

function tj_evaluation_criteria(): array {
    return [
        'technical' => tj_t('Kiến thức chuyên môn', 'Technical knowledge'),
        'problem_solving' => tj_t('Giải quyết vấn đề', 'Problem solving'),
        'communication' => tj_t('Giao tiếp', 'Communication'),
    ];
}
function tj_evaluation_recommendations(): array {
    return ['hire' => tj_t('Đề xuất tuyển', 'Recommend hiring'), 'hold' => tj_t('Cân nhắc thêm', 'Further review'), 'no_hire' => tj_t('Chưa phù hợp', 'Not recommended')];
}
function tj_evaluation(int $interview): ?array {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . tj_table('evaluations') . ' WHERE interview_id=%d', $interview), ARRAY_A);
}
function tj_save_evaluation(array $input) {
    global $wpdb;
    if (!tj_admin()) { return new WP_Error('forbidden', 'Forbidden'); }
    $interview_id = absint($input['interview_id'] ?? 0);
    $revision = filter_var($input['revision'] ?? '', FILTER_VALIDATE_INT);
    $recommendation = is_string($input['recommendation'] ?? null) ? $input['recommendation'] : '';
    $note = is_string($input['evaluation_note'] ?? null) ? sanitize_textarea_field($input['evaluation_note']) : '';
    if ($revision === false || $revision < 0 || !isset(tj_evaluation_recommendations()[$recommendation]) || mb_strlen($note) > 5000) {
        return new WP_Error('invalid', tj_t('Kiểm tra đề xuất và nhận xét tối đa 5.000 ký tự.', 'Check the recommendation and limit comments to 5,000 characters.'));
    }
    $values = [];
    foreach (tj_evaluation_criteria() as $key => $label) {
        $score = filter_var($input[$key] ?? '', FILTER_VALIDATE_INT);
        if ($score === false || $score < 1 || $score > 5) {
            return new WP_Error('score', tj_t('Chấm đủ ba tiêu chí bằng số nguyên từ 1 đến 5.', 'Score all three criteria with integers from 1 to 5.'));
        }
        $values[$key] = $score;
    }
    if ($wpdb->query('START TRANSACTION') === false) { return new WP_Error('database', tj_t('Không thể lưu đánh giá.', 'Could not save the evaluation.')); }
    // Serialize edits with interview updates/cancellation and reject outdated scorecards.
    $interview = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . tj_table('interviews') . ' WHERE id=%d FOR UPDATE', $interview_id), ARRAY_A);
    if (!$interview || $interview['cancelled'] || !tj_row('applications', (int)$interview['application_id'])) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('missing', tj_t('Lịch không tồn tại hoặc đã hủy.', 'Interview is missing or cancelled.'));
    }
    $existing = tj_evaluation($interview_id);
    if ($revision !== (int)($existing['revision'] ?? 0)) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('conflict', tj_t('Phiếu đã được cập nhật ở phiên khác. Tải lại trang trước khi sửa.', 'The scorecard was updated in another session. Reload before editing.'));
    }
    $values += ['interview_id' => $interview_id, 'reviewer_id' => get_current_user_id(), 'recommendation' => $recommendation, 'note' => $note, 'revision' => $revision + 1, 'updated_at' => tj_now()];
    $ok = $existing ? $wpdb->update(tj_table('evaluations'), $values, ['id' => $existing['id']]) : $wpdb->insert(tj_table('evaluations'), $values);
    if ($ok === false || !tj_audit('save_evaluation', $interview_id) || $wpdb->query('COMMIT') === false) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('database', tj_t('Không thể hoàn tất lưu đánh giá.', 'Could not complete the evaluation update.'));
    }
    return true;
}

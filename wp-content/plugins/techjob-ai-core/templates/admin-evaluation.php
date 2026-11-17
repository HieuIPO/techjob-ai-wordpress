<?php
defined('ABSPATH') || exit;
if (!tj_admin() || !$interview) { return; }
$evaluation = tj_evaluation((int)$interview['id']);
?>
<section class="tj-panel tj-evaluation">
    <div class="tj-row tj-between">
        <h2><?php tj_e('Phiếu đánh giá phỏng vấn', 'Interview scorecard'); ?></h2>
        <?php if ($evaluation) { ?>
            <strong class="tj-score-total"><?php echo (int)$evaluation['technical'] + (int)$evaluation['problem_solving'] + (int)$evaluation['communication']; ?> / 15</strong>
        <?php } ?>
    </div>
    <p class="tj-muted"><?php tj_e('Chấm từ 1 (cần cải thiện) đến 5 (rất tốt). Phiếu và nhận xét chỉ dành cho Admin. Trạng thái hồ sơ được cập nhật tại mục xử lý hồ sơ hoặc kết quả lịch phỏng vấn.', 'Score from 1 (needs improvement) to 5 (excellent). This scorecard is for administrators. Update application status in the application review or interview result.'); ?></p>
    <?php if ($evaluation) {
        $reviewer = get_userdata((int)$evaluation['reviewer_id']); ?>
        <p class="tj-muted"><?php echo esc_html(tj_t('Cập nhật bởi ', 'Updated by ') . ($reviewer ? $reviewer->display_name : tj_t('Quản trị viên', 'Administrator')) . ' · ' . tj_date($evaluation['updated_at'], true)); ?></p>
    <?php }
    if ($interview['cancelled']) { ?>
        <p class="tj-alert notice"><?php tj_e('Lịch đã hủy. Phiếu đã lưu được giữ để đối chiếu.', 'This interview is cancelled. The saved scorecard remains available for reference.'); ?></p>
        <?php if ($evaluation) { ?>
            <dl class="tj-dl">
                <?php foreach (tj_evaluation_criteria() as $key => $label) { ?><dt><?php echo esc_html($label); ?></dt><dd><?php echo (int)$evaluation[$key]; ?> / 5</dd><?php } ?>
                <dt><?php tj_e('Đề xuất', 'Recommendation'); ?></dt><dd><?php echo esc_html(tj_evaluation_recommendations()[$evaluation['recommendation']] ?? ''); ?></dd>
            </dl>
            <p class="tj-prewrap"><?php echo esc_html($evaluation['note']); ?></p>
        <?php }
    } else {
        tj_form_start('save_evaluation', 'tj-stack');
        tj_hidden('interview_id', $interview['id']); tj_hidden('revision', $evaluation['revision'] ?? 0);
        $scores = ['' => tj_t('Chọn điểm', 'Choose a score'), 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']; ?>
        <div class="tj-score-grid">
            <?php foreach (tj_evaluation_criteria() as $key => $label) { tj_select($key, $label, $scores, $evaluation[$key] ?? ''); } ?>
        </div>
        <?php tj_select('recommendation', tj_t('Đề xuất', 'Recommendation'), ['' => tj_t('Chọn đề xuất', 'Choose a recommendation')] + tj_evaluation_recommendations(), $evaluation['recommendation'] ?? '');
        tj_textarea('evaluation_note', tj_t('Nhận xét nội bộ', 'Internal assessment'), $evaluation['note'] ?? '');
        tj_button('Lưu đánh giá', 'Save scorecard'); ?></form>
    <?php } ?>
    <p><a href="<?php echo esc_url(tj_url('quan-tri/ho-so', ['application' => $interview['application_id']])); ?>"><?php tj_e('Xem hồ sơ ứng tuyển', 'View application'); ?> →</a></p>
</section>

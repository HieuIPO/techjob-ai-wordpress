<?php
defined('ABSPATH') || exit;
global $wpdb;

$user = wp_get_current_user();
$applications_table = tj_table('applications');
$interviews_table = tj_table('interviews');
$statuses = tj_statuses();
$status_counts = array_fill_keys(array_keys($statuses), 0);

foreach ($wpdb->get_results('SELECT status, COUNT(*) AS total FROM ' . $applications_table . ' GROUP BY status', ARRAY_A) ?: [] as $status_row) {
    if (array_key_exists($status_row['status'], $status_counts)) {
        $status_counts[$status_row['status']] = (int)$status_row['total'];
    }
}

$total_applications = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . $applications_table);
$waiting_applications = $status_counts['submitted'] + $status_counts['reviewing'];
$utc = new DateTimeZone('UTC');
$site_now = current_datetime();
$utc_now = $site_now->setTimezone($utc)->format('Y-m-d H:i:s');
$site_day_end = $site_now->setTime(0, 0)->modify('+1 day');
$site_week_end = $site_now->setTime(0, 0)->modify('+7 days');
$today_end_utc = $site_day_end->setTimezone($utc)->format('Y-m-d H:i:s');
$week_end_utc = $site_week_end->setTimezone($utc)->format('Y-m-d H:i:s');

$count_upcoming_interviews = static function (string $end) use ($wpdb, $interviews_table, $utc_now): int {
    $sql = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$interviews_table} WHERE cancelled=0 AND result=%s AND scheduled_at >= %s AND scheduled_at < %s",
        'pending',
        $utc_now,
        $end
    );
    return (int)$wpdb->get_var($sql);
};

$today_interview_count = $count_upcoming_interviews($today_end_utc);
$week_interview_count = $count_upcoming_interviews($week_end_utc);
$schedule_window_value = $_GET['schedule_window'] ?? 'today';
$schedule_window = is_scalar($schedule_window_value) ? sanitize_key((string)wp_unslash($schedule_window_value)) : 'today';
if (!in_array($schedule_window, ['today', 'week'], true)) {
    $schedule_window = 'today';
}
$schedule_end_utc = $schedule_window === 'week' ? $week_end_utc : $today_end_utc;
$interviews = $wpdb->get_results($wpdb->prepare(
    "SELECT i.id, i.application_id, i.scheduled_at, i.location, a.candidate_name, a.job_id
     FROM {$interviews_table} i
     INNER JOIN {$applications_table} a ON a.id=i.application_id
     WHERE i.cancelled=0 AND i.result=%s AND i.scheduled_at >= %s AND i.scheduled_at < %s
     ORDER BY i.scheduled_at ASC, i.id ASC
     LIMIT 8",
    'pending',
    $utc_now,
    $schedule_end_utc
), ARRAY_A) ?: [];

$queue = $wpdb->get_results(
    "SELECT id, job_id, candidate_name, status, created_at
     FROM {$applications_table}
     WHERE status IN ('submitted', 'reviewing')
     ORDER BY CASE status WHEN 'submitted' THEN 0 ELSE 1 END, created_at ASC, id ASC
     LIMIT 6",
    ARRAY_A
) ?: [];
$today_url = tj_url('quan-tri', ['schedule_window' => 'today']);
$week_url = tj_url('quan-tri', ['schedule_window' => 'week']);
$queue_url = tj_url('quan-tri/ho-so');
?>
<div class="tj-admin-dashboard">
    <section class="tj-admin-dashboard-heading" aria-labelledby="tj-admin-dashboard-title">
        <div>
            <p class="tj-admin-eyebrow"><span aria-hidden="true"></span><?php echo esc_html(tj_t('HÔM NAY · ', 'TODAY · ') . wp_date('d/m/Y')); ?></p>
            <h1 id="tj-admin-dashboard-title"><?php echo esc_html(tj_t('Chào buổi sáng, ', 'Good morning, ') . $user->display_name); ?></h1>
            <p class="tj-admin-subtitle"><?php tj_e('Đây là những việc cần bạn chú ý hôm nay.', 'Here are the items that need your attention today.'); ?></p>
        </div>
        <div class="tj-admin-dashboard-actions">
            <a class="tj-admin-button tj-admin-button--primary" href="<?php echo esc_url(tj_url('quan-tri/lich-phong-van', ['new' => 1])); ?>">
                <?php tj_admin_icon('plus'); ?><span><?php tj_e('Lên lịch phỏng vấn', 'Schedule interview'); ?></span>
            </a>
        </div>
    </section>

    <section class="tj-admin-metrics" aria-label="<?php tj_e('Chỉ số tuyển dụng', 'Recruitment metrics'); ?>">
        <article class="tj-admin-metric tj-admin-metric--priority">
            <div class="tj-admin-metric-top"><span><?php tj_e('Hồ sơ chờ xử lý', 'Applications to review'); ?></span><span class="tj-admin-metric-icon tj-admin-tone-teal"><?php tj_admin_icon('inbox'); ?></span></div>
            <strong class="tj-admin-metric-value"><?php echo esc_html(number_format_i18n($waiting_applications)); ?></strong>
            <div class="tj-admin-metric-foot"><span class="tj-admin-dot tj-admin-tone-teal"></span><span><?php echo esc_html(number_format_i18n($status_counts['submitted'])); ?> <?php tj_e('hồ sơ mới nộp', 'new submissions'); ?></span><a href="<?php echo esc_url($queue_url); ?>"><?php tj_e('Mở hàng đợi', 'Open queue'); ?> <span aria-hidden="true">↗</span></a></div>
        </article>
        <article class="tj-admin-metric">
            <div class="tj-admin-metric-top"><span><?php tj_e('Phỏng vấn tuần này', 'Interviews this week'); ?></span><span class="tj-admin-metric-icon tj-admin-tone-amber"><?php tj_admin_icon('calendar'); ?></span></div>
            <strong class="tj-admin-metric-value"><?php echo esc_html(number_format_i18n($week_interview_count)); ?></strong>
            <div class="tj-admin-metric-foot"><span class="tj-admin-dot tj-admin-tone-amber"></span><span><?php echo esc_html(number_format_i18n($today_interview_count)); ?> <?php tj_e('lịch trong hôm nay', 'scheduled today'); ?></span></div>
        </article>
        <article class="tj-admin-metric">
            <div class="tj-admin-metric-top"><span><?php tj_e('Hồ sơ đạt', 'Successful applications'); ?></span><span class="tj-admin-metric-icon tj-admin-tone-green"><?php tj_admin_icon('users'); ?></span></div>
            <strong class="tj-admin-metric-value"><?php echo esc_html(number_format_i18n($status_counts['passed'])); ?></strong>
            <div class="tj-admin-metric-foot"><span class="tj-admin-dot tj-admin-tone-green"></span><span><?php tj_e('Trạng thái đã đạt', 'Currently marked passed'); ?></span></div>
        </article>
        <article class="tj-admin-metric">
            <div class="tj-admin-metric-top"><span><?php tj_e('Tổng hồ sơ', 'Total applications'); ?></span><span class="tj-admin-metric-icon tj-admin-tone-slate"><?php tj_admin_icon('file'); ?></span></div>
            <strong class="tj-admin-metric-value"><?php echo esc_html(number_format_i18n($total_applications)); ?></strong>
            <div class="tj-admin-metric-foot"><span class="tj-admin-dot tj-admin-tone-slate"></span><span><?php tj_e('Toàn bộ trạng thái', 'Across all statuses'); ?></span></div>
        </article>
    </section>

    <div class="tj-admin-workbench">
        <section class="tj-admin-panel tj-admin-queue" aria-labelledby="tj-admin-queue-title">
            <div class="tj-admin-panel-heading">
                <div>
                    <div class="tj-admin-title-line"><h2 id="tj-admin-queue-title"><?php tj_e('Hồ sơ cần xử lý', 'Applications to review'); ?></h2><span class="tj-admin-count"><?php echo esc_html(number_format_i18n($waiting_applications)); ?></span></div>
                    <p><?php tj_e('Ưu tiên hồ sơ mới nộp và đang xem xét.', 'Prioritize new submissions and applications in review.'); ?></p>
                </div>
                <a class="tj-admin-text-link" href="<?php echo esc_url($queue_url); ?>"><?php tj_e('Xem tất cả', 'View all'); ?> <span aria-hidden="true">↗</span></a>
            </div>

            <nav class="tj-admin-filter-tabs" aria-label="<?php tj_e('Lọc hồ sơ', 'Filter applications'); ?>">
                <a class="is-active" href="<?php echo esc_url($queue_url); ?>"><?php tj_e('Tất cả', 'All'); ?><span><?php echo esc_html(number_format_i18n($waiting_applications)); ?></span></a>
                <a href="<?php echo esc_url(tj_url('quan-tri/ho-so', ['status' => 'submitted'])); ?>"><?php tj_e('Mới nộp', 'Submitted'); ?><span><?php echo esc_html(number_format_i18n($status_counts['submitted'])); ?></span></a>
                <a href="<?php echo esc_url(tj_url('quan-tri/ho-so', ['status' => 'reviewing'])); ?>"><?php tj_e('Đang xem xét', 'In review'); ?><span><?php echo esc_html(number_format_i18n($status_counts['reviewing'])); ?></span></a>
            </nav>

            <div class="tj-admin-queue-labels" aria-hidden="true">
                <span><?php tj_e('ỨNG VIÊN & VỊ TRÍ', 'CANDIDATE & POSITION'); ?></span>
                <span><?php tj_e('NGÀY NỘP', 'SUBMITTED'); ?></span>
                <span><?php tj_e('TRẠNG THÁI', 'STATUS'); ?></span>
                <span></span>
            </div>

            <?php if ($queue) { ?>
                <div class="tj-admin-application-list" role="list" aria-label="<?php tj_e('Hồ sơ cần xử lý', 'Applications to review'); ?>">
                    <?php foreach ($queue as $application) {
                        $application_id = (int)$application['id'];
                        $status = $application['status'];
                        $candidate_name = (string)$application['candidate_name'];
                        $initial = mb_strtoupper(mb_substr(trim($candidate_name), 0, 1));
                        $detail_url = tj_url('quan-tri/ho-so', ['application' => $application_id]);
                        $status_class = isset($statuses[$status]) ? 'tj-admin-status--' . sanitize_html_class($status) : '';
                    ?>
                        <article class="tj-admin-application-row" role="listitem">
                            <div class="tj-admin-candidate">
                                <span class="tj-admin-avatar tj-admin-avatar-tone-<?php echo esc_attr((string)(($application_id % 5) + 1)); ?>" aria-hidden="true"><?php echo esc_html($initial); ?></span>
                                <div><strong><?php echo esc_html($candidate_name); ?></strong><span><?php echo esc_html(tj_title((int)$application['job_id'])); ?></span></div>
                            </div>
                            <time class="tj-admin-submitted" datetime="<?php echo esc_attr(gmdate(DATE_ATOM, strtotime($application['created_at'] . ' UTC'))); ?>"><?php echo esc_html(tj_date($application['created_at'], true)); ?></time>
                            <span class="tj-admin-status <?php echo esc_attr($status_class); ?>"><span aria-hidden="true"></span><?php echo esc_html($statuses[$status] ?? $status); ?></span>
                            <a class="tj-admin-row-action" href="<?php echo esc_url($detail_url); ?>" aria-label="<?php echo esc_attr(tj_t('Mở hồ sơ của ', 'Open application for ') . $candidate_name); ?>"><?php tj_admin_icon('chevron'); ?></a>
                        </article>
                    <?php } ?>
                </div>
                <footer class="tj-admin-panel-footer"><span><?php echo esc_html(tj_t('Đang hiển thị ', 'Showing ') . number_format_i18n(count($queue)) . tj_t(' trên ', ' of ') . number_format_i18n($waiting_applications) . tj_t(' hồ sơ cần xử lý', ' applications to review')); ?></span><a href="<?php echo esc_url($queue_url); ?>"><?php tj_e('Mở hàng đợi đầy đủ', 'Open full queue'); ?> <span aria-hidden="true">↗</span></a></footer>
            <?php } else { ?>
                <div class="tj-admin-empty" role="status">
                    <span class="tj-admin-empty-icon"><?php tj_admin_icon('inbox'); ?></span>
                    <strong><?php tj_e('Chưa có hồ sơ cần xử lý', 'No applications need review'); ?></strong>
                    <span><?php tj_e('Hồ sơ mới nộp hoặc đang xem xét sẽ xuất hiện tại đây.', 'New submissions and applications in review will appear here.'); ?></span>
                </div>
            <?php } ?>
        </section>

        <section class="tj-admin-panel tj-admin-schedule" aria-labelledby="tj-admin-schedule-title">
            <div class="tj-admin-panel-heading">
                <div>
                    <div class="tj-admin-title-line"><h2 id="tj-admin-schedule-title"><?php tj_e('Lịch phỏng vấn', 'Interview schedule'); ?></h2><span class="tj-admin-schedule-total"><?php echo esc_html(number_format_i18n($week_interview_count)); ?> <small><?php tj_e('tuần này', 'this week'); ?></small></span></div>
                    <p><?php tj_e('Chuẩn bị cho các cuộc gặp sắp tới.', 'Prepare for upcoming conversations.'); ?></p>
                </div>
                <a class="tj-admin-icon-link" href="<?php echo esc_url(tj_url('quan-tri/lich-phong-van', ['new' => 1])); ?>" aria-label="<?php tj_e('Tạo lịch phỏng vấn', 'Create interview'); ?>"><?php tj_admin_icon('plus'); ?></a>
            </div>
            <nav class="tj-admin-schedule-tabs" aria-label="<?php tj_e('Khoảng thời gian', 'Schedule range'); ?>">
                <a class="<?php echo $schedule_window === 'today' ? 'is-active' : ''; ?>" href="<?php echo esc_url($today_url); ?>"><?php tj_e('Hôm nay', 'Today'); ?><span><?php echo esc_html(number_format_i18n($today_interview_count)); ?></span></a>
                <a class="<?php echo $schedule_window === 'week' ? 'is-active' : ''; ?>" href="<?php echo esc_url($week_url); ?>"><?php tj_e('Tuần này', 'This week'); ?><span><?php echo esc_html(number_format_i18n($week_interview_count)); ?></span></a>
            </nav>

            <?php if ($interviews) { ?>
                <div class="tj-admin-interview-list" role="list" aria-label="<?php tj_e('Các lịch phỏng vấn sắp tới', 'Upcoming interviews'); ?>">
                    <?php foreach ($interviews as $interview) {
                        $scheduled_utc = new DateTimeImmutable($interview['scheduled_at'], $utc);
                        $application_url = tj_url('quan-tri/ho-so', ['application' => (int)$interview['application_id']]);
                        $edit_url = tj_url('quan-tri/lich-phong-van', ['edit' => (int)$interview['id']]);
                    ?>
                        <article class="tj-admin-interview-row" role="listitem">
                            <time class="tj-admin-interview-time" datetime="<?php echo esc_attr($scheduled_utc->format(DATE_ATOM)); ?>">
                                <strong><?php echo esc_html(wp_date('H:i', $scheduled_utc->getTimestamp())); ?></strong>
                                <span><?php echo esc_html(wp_date('d/m', $scheduled_utc->getTimestamp())); ?></span>
                            </time>
                            <span class="tj-admin-interview-line" aria-hidden="true"></span>
                            <div class="tj-admin-interview-copy">
                                <div><a href="<?php echo esc_url($application_url); ?>"><?php echo esc_html($interview['candidate_name']); ?></a><span class="tj-admin-interview-kind"><?php tj_e('Đã lên lịch', 'Scheduled'); ?></span></div>
                                <span><?php echo esc_html(tj_title((int)$interview['job_id'])); ?></span>
                                <span class="tj-admin-location"><?php tj_admin_icon('pin'); ?><?php echo esc_html($interview['location']); ?></span>
                            </div>
                            <a class="tj-admin-open-interview" href="<?php echo esc_url($edit_url); ?>"><?php tj_e('Mở', 'Open'); ?></a>
                        </article>
                    <?php } ?>
                </div>
                <a class="tj-admin-schedule-footer" href="<?php echo esc_url(tj_url('quan-tri/lich-phong-van')); ?>"><?php tj_e('Xem toàn bộ lịch', 'View all interviews'); ?> <span aria-hidden="true">↗</span></a>
            <?php } else { ?>
                <div class="tj-admin-empty tj-admin-empty--schedule" role="status">
                    <?php tj_admin_icon('calendar'); ?>
                    <strong><?php tj_e('Không có lịch phỏng vấn', 'No interviews scheduled'); ?></strong>
                    <span><?php tj_e('Lịch sắp tới sẽ xuất hiện tại đây.', 'Upcoming interviews will appear here.'); ?></span>
                    <a href="<?php echo esc_url(tj_url('quan-tri/lich-phong-van', ['new' => 1])); ?>"><?php tj_e('Tạo lịch phỏng vấn', 'Schedule an interview'); ?></a>
                </div>
            <?php } ?>
        </section>
    </div>

    <section class="tj-admin-panel tj-admin-pipeline" aria-labelledby="tj-admin-pipeline-title">
        <div class="tj-admin-panel-heading">
            <div><h2 id="tj-admin-pipeline-title"><?php tj_e('Tiến trình hồ sơ', 'Application pipeline'); ?></h2><p><?php tj_e('Phân bổ theo trạng thái hiện tại', 'Current distribution by status'); ?> <span>· <?php tj_e('Tất cả thời gian', 'All time'); ?></span></p></div>
            <a class="tj-admin-text-link" href="<?php echo esc_url($queue_url); ?>"><?php tj_e('Quản lý hồ sơ', 'Manage applications'); ?> <span aria-hidden="true">↗</span></a>
        </div>
        <div class="tj-admin-pipeline-grid" role="list" aria-label="<?php tj_e('Số hồ sơ theo trạng thái', 'Applications by status'); ?>">
            <?php foreach ($statuses as $status => $label) {
                $count = $status_counts[$status] ?? 0;
                $percentage = $total_applications > 0 ? round(($count / $total_applications) * 100, 1) : 0;
            ?>
                <div class="tj-admin-pipeline-stage" role="listitem">
                    <div class="tj-admin-pipeline-label"><span class="tj-admin-status-dot tj-admin-status-dot--<?php echo esc_attr(sanitize_html_class($status)); ?>"></span><span><?php echo esc_html($label); ?></span><strong><?php echo esc_html(number_format_i18n($count)); ?></strong></div>
                    <div class="tj-admin-pipeline-track" role="progressbar" aria-label="<?php echo esc_attr($label); ?>" aria-valuemin="0" aria-valuemax="<?php echo esc_attr((string)max(1, $total_applications)); ?>" aria-valuenow="<?php echo esc_attr((string)$count); ?>"><span class="tj-admin-pipeline-fill tj-admin-pipeline-fill--<?php echo esc_attr(sanitize_html_class($status)); ?>" style="width: <?php echo esc_attr((string)$percentage); ?>%"></span></div>
                    <small><?php echo esc_html($status); ?></small>
                </div>
            <?php } ?>
        </div>
    </section>
</div>

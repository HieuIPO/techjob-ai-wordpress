<?php
defined('ABSPATH') || exit;
$filters = tj_report_filters(wp_unslash($_GET));
?>
<div class="tj-stack tj-reports">
    <section class="tj-panel">
        <h2><?php tj_e('Thống kê tuyển dụng', 'Recruitment statistics'); ?></h2>
        <p class="tj-muted"><?php echo esc_html(tj_t('Lọc theo ngày nộp hồ sơ, theo múi giờ ', 'Filter by application submission date in ') . wp_timezone_string()); ?>. <?php tj_e('Số liệu thể hiện trạng thái hiện tại của các hồ sơ trong khoảng ngày đã chọn.', 'Counts show the current status of applications submitted in the selected date range.'); ?></p>
        <form class="tj-report-filters" method="get" action="<?php echo esc_url(home_url('/quan-tri/bao-cao/')); ?>">
            <?php tj_hidden('lang', tj_lang());
            tj_input('from', tj_t('Từ ngày', 'From'), is_wp_error($filters) ? '' : $filters['from'], 'date', true);
            tj_input('to', tj_t('Đến ngày', 'To'), is_wp_error($filters) ? '' : $filters['to'], 'date', true);
            $options = [0 => tj_t('Tất cả tin tuyển dụng', 'All jobs')];
            foreach (get_posts(['post_type' => 'tj_job', 'post_status' => ['publish', 'draft', 'private', 'trash'], 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']) as $report_job) {
                $options[$report_job->ID] = '#' . $report_job->ID . ' · ' . tj_title($report_job->ID);
            }
            tj_select('job', tj_t('Tin tuyển dụng', 'Job'), $options, is_wp_error($filters) ? 0 : $filters['job']);
            tj_button('Xem báo cáo', 'View report'); ?>
        </form>
    </section>
    <?php
    $report = is_wp_error($filters) ? $filters : tj_recruitment_report($filters);
    if (is_wp_error($report)) {
        echo '<p class="tj-alert error" role="alert">' . esc_html($report->get_error_message()) . '</p>';
        return;
    }
    $totals = $report['totals'];
    $rate = $totals['total'] ? round(100 * $totals['passed'] / $totals['total'], 1) : 0;
    ?>
    <div class="tj-report-metrics">
        <?php foreach ([
            [tj_t('Hồ sơ đã nộp', 'Applications'), $totals['total']],
            [tj_t('Ứng viên duy nhất', 'Unique candidates'), $report['candidates']],
            [tj_t('Hồ sơ đạt', 'Passed applications'), $totals['passed']],
            [tj_t('Tỷ lệ hồ sơ đạt', 'Pass rate'), $rate . '%'],
        ] as [$label, $value]) { ?>
            <article class="tj-panel"><span class="tj-muted"><?php echo esc_html($label); ?></span><strong><?php echo esc_html($value); ?></strong></article>
        <?php } ?>
    </div>
    <section class="tj-panel">
        <h2><?php tj_e('Phân bố trạng thái', 'Status distribution'); ?></h2>
        <div class="tj-report-statuses">
            <?php foreach (tj_statuses() as $key => $label) { ?>
                <div>
                    <div class="tj-row tj-between"><span><?php echo esc_html($label); ?></span><strong><?php echo (int)$totals[$key]; ?></strong></div>
                    <meter min="0" max="<?php echo max(1, $totals['total']); ?>" value="<?php echo (int)$totals[$key]; ?>"><?php echo (int)$totals[$key]; ?></meter>
                </div>
            <?php } ?>
        </div>
    </section>
    <section class="tj-panel">
        <div class="tj-row tj-between">
            <h2><?php tj_e('Hồ sơ theo tin tuyển dụng', 'Applications by job'); ?></h2>
            <?php tj_form_start('export_report', 'tj-inline tj-download');
            foreach ($filters as $key => $value) { tj_hidden($key, $value); }
            tj_button('Xuất CSV', 'Export CSV', 'secondary'); ?></form>
        </div>
        <p class="tj-muted"><?php tj_e('CSV gồm các dòng trong bảng bên dưới. Tin chưa có hồ sơ trong khoảng đã chọn sẽ không có dòng thống kê.', 'The CSV contains the rows in the table below. Jobs with no applications in the selected period have no report row.'); ?></p>
        <?php if (!$report['rows']) { tj_empty(tj_t('Chưa có hồ sơ trong khoảng đã chọn.', 'No applications in the selected period.')); } else { ?>
            <div class="tj-table-wrap">
                <table class="tj-table tj-report-table">
                    <caption class="screen-reader-text"><?php tj_e('Số hồ sơ theo tin tuyển dụng và trạng thái', 'Applications grouped by job and status'); ?></caption>
                    <thead><tr><th scope="col"><?php tj_e('Tin tuyển dụng', 'Job'); ?></th><th scope="col"><?php tj_e('Tổng', 'Total'); ?></th><?php foreach (tj_statuses() as $label) { ?><th scope="col"><?php echo esc_html($label); ?></th><?php } ?></tr></thead>
                    <tbody><?php foreach ($report['rows'] as $row) { ?>
                        <tr>
                            <th scope="row"><?php echo esc_html('#' . $row['job_id'] . ' · ' . $row['title']); ?></th>
                            <td><?php echo (int)$row['total']; ?></td>
                            <?php foreach (array_keys(tj_statuses()) as $key) { ?><td><?php echo (int)$row[$key]; ?></td><?php } ?>
                        </tr>
                    <?php } ?></tbody>
                </table>
            </div>
        <?php } ?>
    </section>
</div>

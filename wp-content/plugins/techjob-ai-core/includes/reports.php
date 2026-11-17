<?php
defined('ABSPATH') || exit;

function tj_report_filters(array $input) {
    $filters = [];
    foreach (['from' => wp_date('Y-m-01'), 'to' => wp_date('Y-m-d')] as $key => $default) {
        $value = $input[$key] ?? $default;
        $date = is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $value, wp_timezone()) : false;
        if (!$date || $date->format('Y-m-d') !== $value || (int)$date->format('Y') < 1000 || (int)$date->format('Y') > 9998) {
            return new WP_Error('date', tj_t('Nhập ngày hợp lệ theo định dạng năm-tháng-ngày.', 'Enter valid dates in year-month-day format.'));
        }
        $filters[$key] = $value;
    }
    if ($filters['from'] > $filters['to']) { return new WP_Error('range', tj_t('Ngày bắt đầu phải trước hoặc bằng ngày kết thúc.', 'The start date must be on or before the end date.')); }
    $job = filter_var($input['job'] ?? 0, FILTER_VALIDATE_INT);
    if ($job === false || $job < 0 || ($job && get_post_type($job) !== 'tj_job')) {
        return new WP_Error('job', tj_t('Tin tuyển dụng không hợp lệ.', 'Invalid job selection.'));
    }
    $filters['job'] = $job;
    return $filters;
}
function tj_recruitment_report(array $filters) {
    global $wpdb;
    if (!tj_admin()) { return new WP_Error('forbidden', 'Forbidden'); }
    $filters = tj_report_filters($filters);
    if (is_wp_error($filters)) { return $filters; }
    $zone = wp_timezone();
    $from = new DateTimeImmutable($filters['from'] . ' 00:00:00', $zone);
    $to = (new DateTimeImmutable($filters['to'] . ' 00:00:00', $zone))->modify('+1 day');
    $where = $wpdb->prepare('a.created_at >= %s AND a.created_at < %s', $from->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'), $to->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
    if ($filters['job']) { $where .= $wpdb->prepare(' AND a.job_id=%d', $filters['job']); }
    $columns = [];
    foreach (array_keys(tj_statuses()) as $status) { $columns[] = $wpdb->prepare("SUM(a.status=%s) AS $status", $status); }
    $table = tj_table('applications');
    if ($wpdb->query('START TRANSACTION WITH CONSISTENT SNAPSHOT') === false) {
        return new WP_Error('database', tj_t('Không thể đọc báo cáo lúc này.', 'Could not read the report.'));
    }
    $rows = $wpdb->get_results("SELECT a.job_id, COUNT(*) AS total, " . implode(', ', $columns) . " FROM $table a WHERE $where GROUP BY a.job_id ORDER BY total DESC, a.job_id DESC", ARRAY_A);
    $error = $wpdb->last_error;
    $candidates = $wpdb->get_var("SELECT COUNT(DISTINCT a.user_id) FROM $table a WHERE $where");
    if ($error || $wpdb->last_error || $wpdb->query('COMMIT') === false) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('database', tj_t('Không thể đọc báo cáo lúc này.', 'Could not read the report.'));
    }
    $totals = array_fill_keys(array_merge(['total'], array_keys(tj_statuses())), 0);
    foreach ($rows as &$row) {
        foreach ($totals as $key => $unused) { $row[$key] = (int)$row[$key]; $totals[$key] += $row[$key]; }
        $row['title'] = get_post_type((int)$row['job_id']) === 'tj_job' ? tj_title((int)$row['job_id']) : tj_t('Tin đã xóa #', 'Deleted job #') . $row['job_id'];
    }
    unset($row);
    return ['filters' => $filters, 'rows' => $rows, 'totals' => $totals, 'candidates' => (int)$candidates];
}
function tj_export_report(array $input): void {
    if (!tj_admin()) { wp_die('Forbidden', '', ['response' => 403]); }
    $report = tj_recruitment_report($input);
    if (is_wp_error($report)) { tj_back('quan-tri/bao-cao', $report); }
    nocache_headers();
    header('X-Content-Type-Options: nosniff');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="techjob-report-' . $report['filters']['from'] . '-' . $report['filters']['to'] . '.csv"');
    $stream = fopen('php://output', 'wb');
    fwrite($stream, "\xEF\xBB\xBF");
    fputcsv($stream, array_merge([tj_t('Từ ngày', 'From'), tj_t('Đến ngày', 'To'), 'Job ID', tj_t('Tin tuyển dụng', 'Job'), tj_t('Tổng hồ sơ', 'Applications')], array_values(tj_statuses())), ',', '"', '');
    foreach ($report['rows'] as $row) {
        // Spreadsheet apps can execute titles beginning with formula markers.
        $title = preg_match('/^[\s\x{FEFF}]*[=+@-]/u', $row['title']) ? "'" . $row['title'] : $row['title'];
        fputcsv($stream, array_merge([$report['filters']['from'], $report['filters']['to'], (int)$row['job_id'], $title, $row['total']], array_map(fn($key) => $row[$key], array_keys(tj_statuses()))), ',', '"', '');
    }
    fclose($stream);
    exit;
}

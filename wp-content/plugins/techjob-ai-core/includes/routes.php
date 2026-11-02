<?php
defined('ABSPATH') || exit;
function tj_register_routes(): void {
    add_rewrite_rule('^viec-lam/([^/]+)/?$', 'index.php?tj_route=job&tj_slug=$matches[1]', 'top');
    add_rewrite_rule('^(viec-lam|cong-ty|dang-nhap|dang-ky|dang-xuat|tai-khoan|quan-tri|tai-cv|lich-ics)(?:/(.*))?/?$', 'index.php?tj_route=$matches[1]&tj_slug=$matches[2]', 'top');
    add_filter('query_vars', function ($vars) { return array_merge($vars, ['tj_route', 'tj_slug']); });
}
function tj_route_request(): void {
    $route = (string)get_query_var('tj_route');
    if (!$route && !is_front_page() && trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') !== '') { return; }
    if (isset($_GET['lang']) && !headers_sent()) { setcookie('tj_lang', tj_lang(), ['expires' => time() + YEAR_IN_SECONDS, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => is_ssl()]); }
    $slug = trim((string)get_query_var('tj_slug'), '/');
    if (in_array($route, ['tai-khoan', 'quan-tri', 'tai-cv', 'lich-ics', 'dang-xuat'], true) && !is_user_logged_in()) {
        wp_safe_redirect(tj_url('dang-nhap', ['next' => tj_url($route . ($slug ? '/' . $slug : ''), $_GET)])); exit;
    }
    if ($route === 'quan-tri' && !tj_admin()) { status_header(403); tj_render('error', ['message' => tj_t('Chỉ quản trị viên được truy cập.', 'Administrators only.')]); exit; }
    if ($route === 'tai-cv') { tj_download_cv(absint($slug)); }
    if ($route === 'lich-ics') { tj_download_calendar(absint($slug)); }
    if ($route === 'dang-xuat') {
        if (!wp_verify_nonce(sanitize_text_field($_GET['_wpnonce'] ?? ''), 'tj_logout')) { status_header(403); wp_die('Invalid logout nonce'); }
        wp_logout(); wp_safe_redirect(tj_url()); exit;
    }
    if (is_user_logged_in() && in_array($route, ['dang-nhap', 'dang-ky'], true)) { wp_safe_redirect(tj_url(tj_admin() ? 'quan-tri' : 'tai-khoan')); exit; }
    status_header(200);
    if (is_user_logged_in()) { nocache_headers(); }
    $data = ['route' => $route, 'section' => $slug];
    if ($route === 'job') {
        $job = get_page_by_path($slug, OBJECT, 'tj_job');
        if (!$job || (!tj_admin() && $job->post_status !== 'publish')) { status_header(404); tj_render('error', ['message' => tj_t('Không tìm thấy việc làm.', 'Job not found.')]); exit; }
        $data['job'] = $job;
    }
    $view = match ($route) { 'job' => 'job', 'dang-nhap', 'dang-ky' => 'auth', 'tai-khoan' => 'candidate', 'quan-tri' => 'admin', 'cong-ty' => 'companies', default => 'jobs' };
    tj_render($view, $data);
    exit;
}
function tj_download_calendar(int $id): void {
    $interview = tj_row('interviews', $id);
    $app = $interview ? tj_row('applications', (int)$interview['application_id']) : null;
    if (!$interview || !$app || (!tj_admin() && (int)$app['user_id'] !== get_current_user_id())) { status_header(403); wp_die('Forbidden'); }
    $escape = fn($value) => str_replace(["\\", "\r", "\n", ';', ','], ["\\\\", '', '\\n', '\\;', '\\,'], $value);
    $start = strtotime($interview['scheduled_at'] . ' UTC');
    nocache_headers();
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="interview-' . $id . '.ics"');
    echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//TechJob AI//Interview//VI\r\nBEGIN:VEVENT\r\nUID:interview-$id@techjob.local\r\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\nDTSTART:" . gmdate('Ymd\THis\Z', $start) . "\r\nDTEND:" . gmdate('Ymd\THis\Z', $start + HOUR_IN_SECONDS) . "\r\nSUMMARY:" . $escape('Interview: ' . tj_title((int)$app['job_id'])) . "\r\nLOCATION:" . $escape($interview['location']) . "\r\nSTATUS:" . ($interview['cancelled'] ? 'CANCELLED' : 'CONFIRMED') . "\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    exit;
}

<?php
defined('ABSPATH') || exit;
function tj_render(string $view, array $data = []): void {
    $allowed = ['jobs', 'job', 'auth', 'candidate', 'admin', 'companies', 'error'];
    if (!in_array($view, $allowed, true)) { return; }
    $admin = $view === 'admin';
    $route = $data['route'] ?? ''; $section = $data['section'] ?? '';
    $job = $data['job'] ?? null;
    if ($admin && in_array($section, ['viec-lam', 'cong-ty'], true) && isset($_GET['edit'])) { wp_enqueue_editor(); }
    $GLOBALS['tj_view_admin'] = $admin;
    ?><!doctype html><html lang="<?php echo esc_attr(tj_lang()); ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>TechJob AI · <?php tj_e('Tuyển dụng IT & Luyện phỏng vấn', 'IT Jobs & Interview Practice'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo esc_url(TJ_URL . 'assets/ats-' . ($admin ? 'admin' : 'public') . '.css'); ?>">
    <link rel="stylesheet" href="<?php echo esc_url(TJ_URL . 'assets/techjob.css?v=' . filemtime(TJ_DIR . 'assets/techjob.css')); ?>">
    <?php if ($admin) { ?><link rel="stylesheet" href="<?php echo esc_url(TJ_URL . 'assets/admin-dashboard.css?v=' . filemtime(TJ_DIR . 'assets/admin-dashboard.css')); ?>"><?php } ?>
    <?php if (!$admin) { ?><link rel="stylesheet" href="<?php echo esc_url(TJ_URL . 'assets/stitch-public.css?v=' . filemtime(TJ_DIR . 'assets/stitch-public.css')); ?>"><?php } ?>
    </head><body class="<?php echo $admin ? 'admin-shell' : 'tj-public tj-view-' . esc_attr($view); ?>"><?php if (!$admin) { ?><div class="tj-grain-overlay" aria-hidden="true"></div><?php } ?><a class="tj-skip" href="#main-content"><?php tj_e('Bỏ qua menu', 'Skip navigation'); ?></a>
    <?php if ($admin) { tj_admin_header($section); } else { tj_public_header(); } ?>
    <main id="main-content" class="<?php echo $admin ? 'admin-main' : 'body-content'; ?>">
    <?php foreach (['error', 'notice'] as $kind) { if (!empty($_GET[$kind]) && is_scalar($_GET[$kind])) { echo '<div role="' . ($kind === 'error' ? 'alert' : 'status') . '" class="tj-alert ' . esc_attr($kind) . '">' . esc_html(mb_substr(sanitize_text_field(wp_unslash($_GET[$kind])), 0, 400)) . '</div>'; } } ?>
    <?php if ($view === 'error') { echo '<div class="tj-container"><h1>' . esc_html($data['message'] ?? 'Not found') . '</h1><a href="' . esc_url(tj_url()) . '">TechJob AI</a></div>'; } else { require TJ_DIR . 'templates/' . $view . '.php'; } ?>
    </main>
    <?php if ($admin) { echo '</div></div>'; } else { ?><footer class="tj-footer"><strong>TechJob AI</strong><span><?php tj_e('Tìm việc IT. Chuẩn bị cho bước tiếp theo.', 'Find IT jobs. Prepare for your next step.'); ?></span><span>© <?php echo esc_html(wp_date('Y')); ?></span><?php if ($view === 'jobs' && $route === '') { ?><nav class="tj-image-credits" aria-label="<?php tj_e('Nguồn ảnh', 'Image credits'); ?>"><span><?php tj_e('Nguồn ảnh:', 'Image credits:'); ?></span><span><a href="https://stocksnap.io/photo/coding-programming-MJZPCHLERD">Coding Programming, Tirza van Dijk</a>, <a href="https://creativecommons.org/publicdomain/zero/1.0/">CC0</a></span><span><a href="https://commons.wikimedia.org/wiki/File:Programmer_at_work_(Unsplash).jpg">Programmer at work, Crew crew</a>, <a href="https://creativecommons.org/publicdomain/zero/1.0/deed.en">CC0</a></span><span><a href="https://commons.wikimedia.org/wiki/File:Software_Developer_at_work_03.jpg">Software Developer at work 03, Daudi mukiibi</a>, <a href="https://creativecommons.org/licenses/by-sa/4.0/">CC BY-SA 4.0</a></span><span><a href="https://commons.wikimedia.org/wiki/File:Programmers.jpg">Programmers, Lbronn</a>, <a href="https://creativecommons.org/licenses/by-sa/4.0/">CC BY-SA 4.0</a></span></nav><?php } ?></footer><?php tj_chat_widget(); } ?>
    <script id="tj-config" type="application/json"><?php echo wp_json_encode(['rest' => rest_url('techjob/v1/'), 'nonce' => wp_create_nonce('wp_rest'), 'lang' => tj_lang()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    <script src="<?php echo esc_url(TJ_URL . 'assets/techjob.js?v=' . filemtime(TJ_DIR . 'assets/techjob.js')); ?>" defer></script>
    <?php wp_footer(); ?></body></html><?php
}
function tj_language_link(): void {
    $args = array_map(fn($v) => is_scalar($v) ? sanitize_text_field(wp_unslash($v)) : '', $_GET);
    unset($args['error'], $args['notice']);
    $args['lang'] = tj_lang() === 'en' ? 'vi' : 'en';
    $path = sanitize_text_field(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    echo '<a class="tj-language" href="' . esc_url(add_query_arg($args, home_url($path))) . '" lang="' . esc_attr($args['lang']) . '">' . (tj_lang() === 'en' ? 'Tiếng Việt' : 'English') . '</a>';
}
function tj_public_header(): void { ?>
    <nav class="navbar-custom"><div class="container"><a class="brand" href="<?php echo esc_url(tj_url()); ?>"><span class="brand-mark" aria-hidden="true">&lt;/&gt;</span> TechJob <span class="tj-brand-ai">AI</span></a><button class="tj-menu-toggle" aria-label="<?php tj_e('Mở menu', 'Open menu'); ?>" aria-expanded="false"><?php tj_e('Menu', 'Menu'); ?></button><div class="nav-links">
    <a href="<?php echo esc_url(tj_url()); ?>"><?php tj_e('Trang chủ', 'Home'); ?></a><a href="<?php echo esc_url(tj_url('viec-lam')); ?>"><?php tj_e('Việc làm IT', 'IT jobs'); ?></a><a href="<?php echo esc_url(tj_url('cong-ty')); ?>"><?php tj_e('Công ty', 'Companies'); ?></a><?php tj_language_link(); ?>
    <?php if (is_user_logged_in()) { ?><a href="<?php echo esc_url(tj_url('tai-khoan')); ?>"><?php echo esc_html(wp_get_current_user()->display_name); ?></a><?php if (tj_admin()) { ?><a class="nav-btn" href="<?php echo esc_url(tj_url('quan-tri')); ?>"><?php tj_e('Quản trị', 'Admin'); ?></a><?php } ?><a href="<?php echo esc_url(wp_nonce_url(tj_url('dang-xuat'), 'tj_logout')); ?>"><?php tj_e('Đăng xuất', 'Sign out'); ?></a>
    <?php } else { ?><a href="<?php echo esc_url(tj_url('dang-ky')); ?>"><?php tj_e('Đăng ký', 'Register'); ?></a><a class="nav-btn" href="<?php echo esc_url(tj_url('dang-nhap')); ?>"><?php tj_e('Đăng nhập', 'Sign in'); ?></a><?php } ?></div></div></nav><?php
}
function tj_admin_icon(string $name): void {
    $icons = [
        'grid' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'briefcase' => '<rect x="3" y="7.5" width="18" height="13" rx="2"/><path d="M8 7.5v-3h8v3M3 12h18M10 12v2h4v-2"/>',
        'inbox' => '<path d="M4 5.5h16l1.5 10v3H2.5v-3L4 5.5Z"/><path d="M2.5 15h5l1.5 2h6l1.5-2h5"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M7.5 3v4M16.5 3v4M3.5 9.5h17M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01"/>',
        'building' => '<path d="M4 20V5.5L12 3l8 2.5V20M2.5 20h19M8 8h1M15 8h1M8 12h1M15 12h1M10 20v-4h4v4"/>',
        'tag' => '<path d="M20 13 12.5 20.5 3 11V4h7l10 9Z"/><circle cx="7.5" cy="8" r="1"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3.5 20v-1.5a5.5 5.5 0 0 1 11 0V20M16 5.5a3 3 0 0 1 0 5.8M17 14a5 5 0 0 1 3.5 4.8V20"/>',
        'bell' => '<path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m19 13 .1.1 1.2.9-1.6 2.7-1.4-.5a7 7 0 0 1-1.4.8l-.2 1.5h-3.2l-.3-1.5a7 7 0 0 1-1.4-.8l-1.4.5-1.6-2.7 1.2-.9a7 7 0 0 1 0-1.8l-1.2-.9 1.6-2.7 1.4.5a7 7 0 0 1 1.4-.8l.3-1.5h3.2l.2 1.5a7 7 0 0 1 1.4.8l1.4-.5 1.6 2.7-1.2.9a7 7 0 0 1 0 1.7Z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'file' => '<path d="M6 3.5h8l4 4V20H6z"/><path d="M14 3.5V8h4M9 13h6M9 16h6"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'pin' => '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2"/>',
    ];
    echo '<svg class="tj-admin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($icons[$name] ?? '') . '</svg>';
}
function tj_admin_nav(): array {
    return ['' => ['grid', tj_t('Bảng điều khiển', 'Dashboard')], 'viec-lam' => ['briefcase', tj_t('Tin tuyển dụng', 'Jobs')], 'ho-so' => ['inbox', tj_t('Hồ sơ ứng tuyển', 'Applications')], 'lich-phong-van' => ['calendar', tj_t('Lịch phỏng vấn', 'Interviews')], 'cong-ty' => ['building', tj_t('Công ty', 'Companies')], 'danh-muc' => ['tag', tj_t('Danh mục & kỹ năng', 'Categories & skills')], 'ung-vien' => ['users', tj_t('Ứng viên', 'Candidates')], 'thong-bao' => ['bell', tj_t('Thông báo', 'Notifications')], 'cai-dat' => ['settings', tj_t('Cấu hình hệ thống', 'Settings')]];
}
function tj_admin_header(string $section): void { $user = wp_get_current_user(); $unread = tj_unread(); ?>
    <div class="admin-shell-grid"><aside class="admin-sidebar"><a class="admin-sidebar-brand" href="<?php echo esc_url(tj_url('quan-tri')); ?>"><span class="admin-sidebar-logo">T<span>.</span></span><span class="admin-sidebar-brand-name">TechJob <b>AI</b><small>ADMIN WORKSPACE</small></span></a><nav class="admin-sidebar-nav">
    <?php foreach (tj_admin_nav() as $path => [$icon, $label]) { ?><a class="<?php echo $section === $path ? 'is-active' : ''; ?>" href="<?php echo esc_url(tj_url('quan-tri' . ($path ? '/' . $path : ''))); ?>"<?php echo $section === $path ? ' aria-current="page"' : ''; ?>><?php tj_admin_icon($icon); ?><span><?php echo esc_html($label); ?></span></a><?php } ?></nav><div class="admin-sidebar-footer"><a href="<?php echo esc_url(tj_url()); ?>"><?php tj_e('← Xem website', '← View website'); ?></a><a href="<?php echo esc_url(admin_url()); ?>">WordPress</a></div></aside>
    <div class="admin-content-wrapper"><header class="admin-navbar"><div class="tj-row"><button type="button" class="tj-sidebar-toggle" aria-label="<?php tj_e('Đóng/mở menu', 'Toggle menu'); ?>" aria-expanded="true"><svg class="tj-admin-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button><form class="admin-navbar-search" method="get" action="<?php echo esc_url(home_url('/quan-tri/viec-lam/')); ?>"><input type="hidden" name="lang" value="<?php echo esc_attr(tj_lang()); ?>"><input name="q" aria-label="<?php tj_e('Tìm việc làm', 'Search jobs'); ?>" placeholder="<?php tj_e('Tìm kiếm việc làm…', 'Search jobs…'); ?>"></form></div><div class="admin-navbar-right"><?php tj_language_link(); ?><a class="admin-navbar-notify" href="<?php echo esc_url(tj_url('quan-tri/thong-bao')); ?>" aria-label="<?php echo esc_attr(tj_t('Thông báo, ', 'Notifications, ') . $unread); ?>"><span class="admin-navbar-notify-icon"><?php tj_admin_icon('bell'); ?></span><?php if ($unread > 0) { ?><span class="admin-navbar-notify-count"><?php echo esc_html($unread); ?></span><?php } ?></a><div class="tj-profile-menu"><button type="button" class="tj-profile-toggle" aria-expanded="false" aria-controls="tj-profile-links"><span class="tj-avatar"><?php echo esc_html(mb_substr($user->display_name, 0, 1)); ?></span><span class="tj-profile-name"><?php echo esc_html($user->display_name); ?></span></button><div id="tj-profile-links" hidden><a href="<?php echo esc_url(tj_url('tai-khoan/ho-so')); ?>"><?php tj_e('Hồ sơ', 'Profile'); ?></a><a href="<?php echo esc_url(wp_nonce_url(tj_url('dang-xuat'), 'tj_logout')); ?>"><?php tj_e('Đăng xuất', 'Sign out'); ?></a></div></div></div></header><?php
}
function tj_unread(): int { global $wpdb; return (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . tj_table('notifications') . ' WHERE user_id=%d AND is_read=0', get_current_user_id())); }
function tj_form_start(string $action, string $class = '', bool $upload = false): void {
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="' . esc_attr($class) . '"' . ($upload ? ' enctype="multipart/form-data"' : '') . '>';
    echo '<input type="hidden" name="action" value="tj_action"><input type="hidden" name="tj_action" value="' . esc_attr($action) . '">';
    wp_nonce_field('tj_' . $action, '_tj_nonce');
}
function tj_hidden(string $name, $value): void { echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">'; }
function tj_input(string $name, string $label, $value = '', string $type = 'text', bool $required = false): void {
    echo '<label class="tj-field"><span>' . esc_html($label) . '</span><input name="' . esc_attr($name) . '" type="' . esc_attr($type) . '" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . ($type === 'password' ? ' autocomplete="current-password"' : '') . '></label>';
}
function tj_textarea(string $name, string $label, string $value = '', bool $required = false): void { echo '<label class="tj-field"><span>' . esc_html($label) . '</span><textarea name="' . esc_attr($name) . '" rows="5"' . ($required ? ' required' : '') . '>' . esc_textarea($value) . '</textarea></label>'; }
function tj_editor(string $name, string $label, string $value = ''): void {
    $id = 'tj_editor_' . sanitize_key($name);
    echo '<div class="tj-field"><label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
    wp_editor(wp_kses_post($value), $id, ['textarea_name' => $name, 'teeny' => true, 'media_buttons' => false, 'textarea_rows' => 8, 'quicktags' => false]);
    echo '</div>';
}
function tj_select(string $name, string $label, array $options, $value = '', bool $multiple = false): void {
    echo '<label class="tj-field"><span>' . esc_html($label) . '</span><select name="' . esc_attr($name) . ($multiple ? '[]' : '') . '"' . ($multiple ? ' multiple size="5"' : '') . '>';
    foreach ($options as $key => $text) { echo '<option value="' . esc_attr($key) . '"' . (in_array((string)$key, array_map('strval', (array)$value), true) ? ' selected' : '') . '>' . esc_html($text) . '</option>'; } echo '</select></label>';
}
function tj_term_options(string $tax, bool $all = true): array {
    $options = $all ? ['' => tj_t('Tất cả', 'All')] : [];
    $terms = get_terms(['taxonomy' => 'tj_' . $tax, 'hide_empty' => false]);
    if (!is_wp_error($terms)) { foreach ($terms as $term) { $options[$term->term_id] = tj_term_name($term); } }
    return $options;
}
function tj_button(string $vi = 'Lưu thay đổi', string $en = 'Save changes', string $class = ''): void { echo '<button type="submit" class="tj-button ' . esc_attr($class) . '">' . esc_html(tj_t($vi, $en)) . '</button>'; }
function tj_badge(string $status): void { echo '<span class="tj-badge ' . esc_attr($status) . '">' . esc_html(tj_statuses()[$status] ?? tj_results()[$status] ?? $status) . '</span>'; }
function tj_date(string $date, bool $time = false): string { return $date ? wp_date($time ? 'd/m/Y H:i' : 'd/m/Y', strtotime($date . ' UTC')) : '-'; }
function tj_page_heading(string $title, string $description = ''): void { echo '<div class="tj-page-heading"><div><h1>' . esc_html($title) . '</h1>' . ($description ? '<p>' . esc_html($description) . '</p>' : '') . '</div></div>'; }
function tj_empty(string $text): void { echo '<div class="tj-empty" data-tj-reveal>' . esc_html($text) . '</div>'; }
function tj_pagination(int $pages): void {
    if ($pages < 2) { return; }
    echo '<nav class="tj-pagination" aria-label="Pagination">';
    for ($i = 1; $i <= $pages; $i++) { echo '<a class="' . (max(1, absint($_GET['pg'] ?? 1)) === $i ? 'active' : '') . '" href="' . esc_url(add_query_arg('pg', $i)) . '">' . $i . '</a>'; }
    echo '</nav>';
}
function tj_job_card(WP_Post $job): void { $id = $job->ID; ?>
    <article class="saas-job-card" data-tj-reveal><div class="saas-job-card-header"><span class="saas-job-card-logo-placeholder"><?php echo esc_html(mb_substr(tj_company_name($id), 0, 1)); ?></span><div class="tj-grow"><h3><a href="<?php echo esc_url(tj_job_url($id)); ?>"><?php echo esc_html(tj_title($id)); ?></a></h3><p class="tj-muted"><?php echo esc_html(tj_company_name($id) . ' · ' . tj_terms($id, 'location')); ?></p></div><?php if (tj_meta($id, 'featured')) { ?><span class="tj-badge passed"><?php tj_e('Nổi bật', 'Featured'); ?></span><?php } ?></div><div class="tj-tags"><?php foreach (array_filter([tj_terms($id, 'skill'), tj_job_types()[tj_meta($id, 'job_type')] ?? '']) as $tag) { ?><span><?php echo esc_html($tag); ?></span><?php } ?></div><p class="tj-salary"><?php echo esc_html(tj_salary($id)); ?></p><p><?php echo esc_html(wp_trim_words(wp_strip_all_tags(str_replace(['</p>', '</li>'], ' ', tj_content($id))), 28)); ?></p><div class="tj-row tj-between"><span class="tj-muted"><?php tj_e('Hạn nộp:', 'Apply by:'); ?> <?php echo esc_html(tj_meta($id, 'deadline') ?: '-'); ?></span><a class="tj-button" href="<?php echo esc_url(tj_job_url($id)); ?>"><?php tj_e('Xem & ứng tuyển', 'View & apply'); ?></a></div></article><?php
}
function tj_notifications(): void {
    $rows = tj_rows('notifications', 100, get_current_user_id());
    tj_form_start('read_notifications'); tj_button('Đánh dấu tất cả đã đọc', 'Mark all as read'); echo '</form><div class="tj-stack">';
    if (!$rows) { tj_empty(tj_t('Chưa có thông báo.', 'No notifications yet.')); }
    foreach ($rows as $row) { echo '<a class="tj-panel ' . (!$row['is_read'] ? 'tj-unread' : '') . '" href="' . esc_url(tj_url($row['target'])) . '"><strong>' . esc_html(tj_lang() === 'en' ? $row['message_en'] : $row['message']) . '</strong><p class="tj-muted">' . esc_html(tj_date($row['created_at'], true)) . '</p></a>'; }
    echo '</div>';
}
function tj_chat_widget(): void { ?>
    <button type="button" class="tj-chat-launch" aria-label="<?php tj_e('Luyện phỏng vấn AI', 'AI interview practice'); ?>" aria-controls="tj-chat" aria-expanded="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z"></path><path d="m19 15 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z"></path></svg></button><section id="tj-chat" class="tj-chat" hidden aria-label="<?php tj_e('Luyện phỏng vấn', 'Interview practice'); ?>"><header><div><strong><?php tj_e('Trợ lý luyện phỏng vấn', 'Interview practice assistant'); ?></strong><small>TechJob AI · Java / Python / React / SQL / DevOps</small></div><button type="button" class="tj-chat-close" aria-label="<?php tj_e('Đóng', 'Close'); ?>">×</button></header><div class="tj-chat-body">
    <?php if (!is_user_logged_in()) { ?><p><?php tj_e('Đăng nhập để bắt đầu luyện phỏng vấn kỹ thuật.', 'Sign in to practice a technical interview.'); ?></p><a class="tj-button" href="<?php echo esc_url(tj_url('dang-nhap')); ?>"><?php tj_e('Đăng nhập', 'Sign in'); ?></a><?php } else { ?>
    <p class="tj-muted"><?php tj_e('5 câu hỏi mỗi phiên. Nội dung luyện tập không quyết định kết quả tuyển dụng.', 'Five questions per session. Practice does not determine hiring outcomes.'); ?></p><form id="tj-ai-start">
    <?php tj_select('skill', tj_t('Kỹ năng', 'Skill'), ['Java' => 'Java', 'Python' => 'Python', 'ReactJS' => 'ReactJS', 'SQL' => 'SQL', 'DevOps' => 'DevOps'], 'Java'); tj_select('level', tj_t('Cấp độ', 'Level'), array_combine(['Intern', 'Junior', 'Middle', 'Senior'], ['Intern', 'Junior', 'Middle', 'Senior']), 'Junior'); tj_select('mode', tj_t('Chế độ', 'Mode'), ['auto' => tj_t('Gemini nếu đã cấu hình', 'Gemini when configured'), 'demo' => tj_t('Demo - câu hỏi có sẵn', 'Demo - fixed practice guide')], 'auto'); tj_button('Bắt đầu phiên', 'Start session'); ?></form>
    <p id="tj-ai-mode" class="tj-badge" hidden></p><div id="tj-ai-messages" aria-live="polite" role="log"></div><form id="tj-ai-answer" hidden><?php tj_textarea('answer', tj_t('Câu trả lời', 'Your answer'), '', true); tj_button('Gửi câu trả lời', 'Send answer'); ?></form><p id="tj-ai-error" role="alert"></p>
    <?php } ?></div></section><?php
}
function tj_application_list(bool $admin): void {
    global $wpdb;
    $where = $admin ? '1=1' : $wpdb->prepare('user_id=%d', get_current_user_id());
    $status = sanitize_key($_GET['status'] ?? '');
    if (isset(tj_statuses()[$status])) { $where .= $wpdb->prepare(' AND status=%s', $status); }
    $job_id = absint($_GET['job'] ?? 0);
    if ($job_id) { $where .= $wpdb->prepare(' AND job_id=%d', $job_id); }
    $q = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
    if ($q) { $like = '%' . $wpdb->esc_like($q) . '%'; $where .= $wpdb->prepare(' AND (candidate_name LIKE %s OR email LIKE %s)', $like, $like); }
    $total = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . tj_table('applications') . ' WHERE ' . $where);
    $page = min(max(1, absint($_GET['pg'] ?? 1)), max(1, (int)ceil($total / 12)));
    $rows = $wpdb->get_results('SELECT * FROM ' . tj_table('applications') . ' WHERE ' . $where . $wpdb->prepare(' ORDER BY id DESC LIMIT 12 OFFSET %d', ($page - 1) * 12), ARRAY_A);
    ?><form class="tj-filter-row" method="get"><?php tj_hidden('lang', tj_lang()); tj_select('status', tj_t('Trạng thái', 'Status'), ['' => tj_t('Tất cả', 'All')] + tj_statuses(), $status); if ($admin) { tj_input('q', tj_t('Tên / email ứng viên', 'Candidate name / email'), $q); } tj_button('Lọc hồ sơ', 'Filter'); ?></form><div class="tj-table-wrap"><table class="tj-table"><thead><tr><th>#</th><?php if ($admin) { ?><th><?php tj_e('Ứng viên', 'Candidate'); ?></th><?php } ?><th><?php tj_e('Việc làm', 'Job'); ?></th><th><?php tj_e('Trạng thái', 'Status'); ?></th><th><?php tj_e('Ngày nộp', 'Applied'); ?></th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $app) { ?><tr><td>#<?php echo (int)$app['id']; ?></td><?php if ($admin) { ?><td><strong><?php echo esc_html($app['candidate_name']); ?></strong><br><small><?php echo esc_html($app['email']); ?></small></td><?php } ?><td><?php echo esc_html(tj_title((int)$app['job_id'])); ?></td><td><?php tj_badge($app['status']); ?></td><td><?php echo esc_html(tj_date($app['created_at'])); ?></td><td><a href="<?php echo esc_url(tj_url($admin ? 'quan-tri/ho-so' : 'tai-khoan/ung-tuyen', ['application' => $app['id']])); ?>"><?php tj_e('Chi tiết', 'Details'); ?> →</a></td></tr><?php } ?></tbody></table></div><?php if (!$rows) { tj_empty(tj_t('Chưa có hồ sơ.', 'No applications yet.')); } tj_pagination((int)ceil($total / 12));
}
function tj_application_detail(int $id, bool $admin): void {
    global $wpdb; $app = tj_row('applications', $id);
    if (!$app || (!$admin && (int)$app['user_id'] !== get_current_user_id())) { status_header(403); tj_empty(tj_t('Không có quyền xem hồ sơ này.', 'You cannot view this application.')); return; }
    $file = tj_row('files', (int)$app['file_id']);
    ?><div class="tj-detail-grid"><section class="tj-panel"><h2>#<?php echo (int)$app['id']; ?> · <?php echo esc_html(tj_title((int)$app['job_id'])); ?></h2><p><?php tj_badge($app['status']); ?></p><dl class="tj-dl"><dt><?php tj_e('Ứng viên', 'Candidate'); ?></dt><dd><?php echo esc_html($app['candidate_name']); ?></dd><dt>Email</dt><dd><?php echo esc_html($app['email']); ?></dd><dt><?php tj_e('Điện thoại', 'Phone'); ?></dt><dd><?php echo esc_html($app['phone']); ?></dd><dt>CV</dt><dd><a href="<?php echo esc_url(tj_url('tai-cv/' . $app['file_id'])); ?>"><?php echo esc_html($file['original_name'] ?? 'CV'); ?> ↓</a></dd></dl><h3><?php tj_e('Lời giới thiệu', 'Cover letter'); ?></h3><p class="tj-prewrap"><?php echo esc_html($app['cover_letter'] ?: '-'); ?></p><h3><?php tj_e('Tiến trình hồ sơ', 'Application history'); ?></h3><ol class="tj-timeline"><?php foreach ($wpdb->get_results($wpdb->prepare('SELECT * FROM ' . tj_table('history') . ' WHERE application_id=%d ORDER BY id', $id), ARRAY_A) as $history) { ?><li><strong><?php echo esc_html(tj_statuses()[$history['new_status']] ?? $history['new_status']); ?></strong><small><?php echo esc_html(tj_date($history['created_at'], true)); ?></small><?php if ($admin && $history['note']) { ?><p><?php echo esc_html($history['note']); ?></p><?php } ?></li><?php } ?></ol></section>
    <?php if ($admin) { ?><aside class="tj-panel"><h2><?php tj_e('Xử lý hồ sơ', 'Review application'); ?></h2><?php tj_form_start('status', 'tj-stack'); tj_hidden('application_id', $id); tj_select('status', tj_t('Trạng thái', 'Status'), tj_statuses(), $app['status']); tj_textarea('note', tj_t('Ghi chú nội bộ', 'Internal note'), $app['note']); tj_button(); ?></form><p><a href="<?php echo esc_url(tj_url('quan-tri/lich-phong-van', ['application' => $id])); ?>"><?php tj_e('Tạo lịch phỏng vấn', 'Schedule interview'); ?> →</a></p></aside><?php } ?></div><?php
}
function tj_interview_list(bool $admin): void {
    global $wpdb;
    $sql = 'SELECT i.*,a.candidate_name,a.job_id FROM ' . tj_table('interviews') . ' i JOIN ' . tj_table('applications') . ' a ON a.id=i.application_id';
    if (!$admin) { $sql .= $wpdb->prepare(' WHERE a.user_id=%d', get_current_user_id()); }
    $sql .= ' ORDER BY i.scheduled_at DESC LIMIT 100';
    $rows = $wpdb->get_results($sql, ARRAY_A);
    ?><div class="tj-stack"><?php if (!$rows) { tj_empty(tj_t('Chưa có lịch phỏng vấn.', 'No interviews yet.')); } foreach ($rows as $row) { ?><article class="tj-panel"><div class="tj-row tj-between"><h3><?php echo esc_html(tj_title((int)$row['job_id'])); ?></h3><span class="tj-badge"><?php echo esc_html($row['cancelled'] ? tj_t('Đã hủy', 'Cancelled') : (tj_results()[$row['result']] ?? $row['result'])); ?></span></div><p><?php echo esc_html($row['candidate_name'] . ' · ' . tj_date($row['scheduled_at'], true)); ?></p><p><?php echo esc_html($row['location']); ?></p><div class="tj-row"><a href="<?php echo esc_url(tj_url('lich-ics/' . $row['id'])); ?>"><?php tj_e('Tải lịch .ics', 'Download .ics'); ?> ↓</a><?php if ($admin && !$row['cancelled']) { ?><a href="<?php echo esc_url(tj_url('quan-tri/lich-phong-van', ['edit' => $row['id']])); ?>"><?php tj_e('Chỉnh sửa', 'Edit'); ?></a><?php tj_form_start('cancel_interview', 'tj-inline tj-confirm'); tj_hidden('id', $row['id']); tj_button('Hủy lịch', 'Cancel interview', 'danger'); echo '</form>'; } ?></div></article><?php } ?></div><?php
}

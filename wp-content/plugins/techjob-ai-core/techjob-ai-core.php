<?php
/**
 * Plugin Name: TechJob AI Core
 * Description: IT recruitment, candidate tracking and interview practice migrated from ATS Mini.
 * Version: 0.1.0
 * Requires PHP: 8.2
 * Text Domain: techjob-ai
 */
defined('ABSPATH') || exit;
define('TJ_DIR', plugin_dir_path(__FILE__));
define('TJ_URL', plugin_dir_url(__FILE__));
foreach (['policy', 'i18n', 'schema', 'data', 'files', 'applications', 'actions', 'ai', 'routes', 'view'] as $module) {
    require_once TJ_DIR . 'includes/' . $module . '.php';
}
register_activation_hook(__FILE__, 'tj_activate');
add_action('init', 'tj_register_content');
add_action('init', function () { if ((int)get_option('tj_schema_version') < 2) { tj_activate(); } }, 20);
add_action('init', 'tj_register_routes');
add_action('template_redirect', 'tj_route_request', 1);
add_action('admin_post_tj_action', 'tj_handle_action');
add_action('admin_post_nopriv_tj_action', 'tj_handle_action');
add_action('rest_api_init', 'tj_ai_routes');
add_filter('show_admin_bar', '__return_false');
add_filter('authenticate', function ($user) {
    return $user instanceof WP_User && get_user_meta($user->ID, 'tj_disabled', true)
        ? new WP_Error('disabled', tj_t('Tài khoản đã bị khóa.', 'This account is disabled.')) : $user;
}, 100);
add_action('init', function () {
    if (is_user_logged_in() && get_user_meta(get_current_user_id(), 'tj_disabled', true)) {
        wp_logout();
        wp_safe_redirect(tj_url('dang-nhap'));
        exit;
    }
});
add_action('admin_init', function () {
    if (!wp_doing_ajax() && !current_user_can('manage_options') && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'admin-post.php') {
        wp_safe_redirect(tj_url('tai-khoan'));
        exit;
    }
});
add_action('admin_menu', function () {
    add_menu_page('TechJob AI', 'TechJob AI', 'manage_options', 'techjob-ai', function () {
        echo '<div class="wrap"><h1>TechJob AI</h1><p><a class="button button-primary" href="' . esc_url(tj_url('quan-tri')) . '">Dashboard TechJob AI</a></p></div>';
    }, 'dashicons-businessperson');
});

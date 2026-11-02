<?php
if (!defined('WP_CLI') || !WP_CLI) { exit; }
if (!function_exists('is_blog_installed')) {
    define('WP_INSTALLING', true);
    $wp_path = is_file(getcwd() . '/wp-load.php') ? getcwd() : getcwd() . '/runtime/wordpress';
    require_once $wp_path . '/wp-load.php';
}
if (!is_blog_installed()) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $password = wp_generate_password(24, true, false);
    wp_install('TechJob AI', 'techjob_admin', 'admin@techjob.test', false, '', $password);
    $user = get_user_by('login', 'techjob_admin');
    wp_update_user(['ID' => $user->ID, 'display_name' => 'Admin TechJob']);
    $private = defined('TECHJOB_PRIVATE_DIR') ? TECHJOB_PRIVATE_DIR : '/var/techjob-private';
    wp_mkdir_p($private);
    file_put_contents($private . '/demo-accounts.txt', "LOCAL DEMO ONLY\nAdmin: techjob_admin\nPassword: $password\n");
    chmod($private . '/demo-accounts.txt', 0600);
    update_option('blog_public', 0);
    update_option('default_comment_status', 'closed');
    WP_CLI::success('WordPress installed. Credentials saved privately.');
} else { WP_CLI::success('WordPress is already installed.'); }

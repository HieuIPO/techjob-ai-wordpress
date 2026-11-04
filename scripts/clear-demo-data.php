<?php
/** WP-CLI only. Run without arguments to preview, with `delete` after backing up. */
if (!defined('WP_CLI') || !WP_CLI) { exit; }
global $wpdb;
$admin = get_user_by('login', 'techjob_admin');
if (!$admin || !user_can($admin, 'manage_options')) { WP_CLI::error('Primary administrator is missing.'); }
$tables = ['history', 'interviews', 'applications', 'saved', 'notifications', 'audit', 'ai_sessions', 'limits', 'files'];
$counts = [];
foreach ($tables as $table) { $counts[$table] = (int)$wpdb->get_var('SELECT COUNT(*) FROM ' . tj_table($table)); }
$users = get_users(['role' => 'tj_candidate', 'fields' => 'ID']);
$posts = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('tj_job','tj_company')");
$counts['candidates'] = count($users); $counts['jobs_and_companies'] = count($posts);
WP_CLI::log(wp_json_encode($counts));
if (($args[0] ?? '') !== 'delete') { return; }
require_once ABSPATH . 'wp-admin/includes/user.php';
$dir = realpath(tj_private_dir());
if (!$dir || $dir === realpath(ABSPATH) || !str_contains(str_replace('\\', '/', $dir), '/private') && !str_contains($dir, 'techjob-private')) { WP_CLI::error('Unexpected private directory.'); }
// Exact files only, within the verified private directory; never recursively delete it.
foreach (glob($dir . '/*') as $file) {
    if (is_file($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['pdf', 'doc', 'docx'], true)) {
        $resolved = realpath($file);
        if (!$resolved || dirname($resolved) !== $dir || !unlink($resolved)) { WP_CLI::error('Could not remove private CV.'); }
    }
}
foreach ($tables as $table) { if ($wpdb->query('DELETE FROM ' . tj_table($table)) === false) { WP_CLI::error('Could not clear ' . $table); } }
foreach ($posts as $id) { if (!wp_delete_post((int)$id, true)) { WP_CLI::error('Could not delete recruitment post.'); } }
foreach (['tj_skill', 'tj_category', 'tj_position', 'tj_location'] as $tax) {
    foreach (get_terms(['taxonomy' => $tax, 'hide_empty' => false]) as $term) { if (is_wp_error(wp_delete_term($term->term_id, $tax))) { WP_CLI::error('Could not delete taxonomy term.'); } }
}
foreach ($users as $id) { if ((int)$id !== (int)$admin->ID && !wp_delete_user((int)$id, $admin->ID)) { WP_CLI::error('Could not delete candidate.'); } }
// Keep the seed marker, so setup does not recreate deleted demo data.
update_option('tj_seed_version', 1);
$accounts = $dir . '/demo-accounts.txt';
if (is_file($accounts)) { $content = file_get_contents($accounts); $end = strpos($content, 'Candidate:'); if ($end !== false) { file_put_contents($accounts, rtrim(substr($content, 0, $end)) . "\n"); } }
wp_cache_flush();
WP_CLI::success('Recruitment data and candidates cleared; primary admin retained.');

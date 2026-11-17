<?php
/** Run with PHP CLI. TJ_TEST_WP_LOAD may point at another isolated local wp-load.php. */
if (PHP_SAPI !== 'cli') { exit; }
if (!defined('ABSPATH')) { require_once getenv('TJ_TEST_WP_LOAD') ?: dirname(__DIR__) . '/runtime/wordpress/wp-load.php'; }
require_once ABSPATH . 'wp-admin/includes/user.php';
if (wp_get_environment_type() !== 'local' || !in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('This suite only runs against a local WordPress installation.');
}
$base = home_url();
$prefix = 'tj_feature_' . bin2hex(random_bytes(5));
$password = bin2hex(random_bytes(20));
$jars = []; $users = []; $posts = []; $terms = []; $checks = 0;
$pdf = tempnam(sys_get_temp_dir(), 'tj-feature-cv-');
$temporary_files = [$pdf];
$additional_terms = [];
function ft_check(bool $ok, string $message): void {
    global $checks;
    if (!$ok) { throw new RuntimeException('FAIL ' . $message); }
    $checks++;
    echo "PASS $message\n";
}
function ft_request(string $session, string $path, ?array $data = null): array {
    global $base, $jars;
    $jars[$session] ??= tempnam(sys_get_temp_dir(), 'tj-feature-cookie-');
    $url = str_starts_with($path, $base . '/') ? $path : $base . $path;
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4, CURLOPT_TIMEOUT => 30, CURLOPT_COOKIEJAR => $jars[$session], CURLOPT_COOKIEFILE => $jars[$session], CURLOPT_NOPROXY => '*']);
    if (getenv('TJ_TEST_CONNECT_TO')) { curl_setopt($curl, CURLOPT_CONNECT_TO, [getenv('TJ_TEST_CONNECT_TO')]); }
    if ($data !== null) { curl_setopt($curl, CURLOPT_POSTFIELDS, $data); }
    $body = curl_exec($curl);
    if ($body === false) { throw new RuntimeException(curl_error($curl)); }
    $response = ['body' => $body, 'code' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'type' => curl_getinfo($curl, CURLINFO_CONTENT_TYPE), 'url' => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL)];
    curl_close($curl);
    return $response;
}
function ft_form(array $page, string $action, string $predicate = ''): array {
    $doc = new DOMDocument();
    @$doc->loadHTML('<?xml encoding="UTF-8">' . $page['body'], LIBXML_NONET);
    $xpath = new DOMXPath($doc);
    $form = $xpath->query("//form[.//input[@name='tj_action' and @value='$action']]" . $predicate)->item(0);
    if (!$form) { throw new RuntimeException("Missing form $action"); }
    $data = [];
    foreach ($xpath->query(".//input[@type='hidden']", $form) as $input) { $data[$input->getAttribute('name')] = $input->getAttribute('value'); }
    return $data;
}
function ft_submit(string $session, array $page, string $action, array $data = [], string $predicate = ''): array {
    return ft_request($session, '/wp-admin/admin-post.php', $data + ft_form($page, $action, $predicate));
}
function ft_saved(array $response): bool { return $response['code'] === 200 && str_contains($response['url'], 'notice='); }
function ft_nonce(string $session, int $user, string $action): string {
    global $jars;
    wp_set_current_user($user);
    foreach (file($jars[$session], FILE_IGNORE_NEW_LINES) as $line) {
        $parts = explode("\t", $line);
        if (count($parts) === 7 && $parts[5] === LOGGED_IN_COOKIE) { $_COOKIE[LOGGED_IN_COOKIE] = rawurldecode($parts[6]); }
    }
    $nonce = wp_create_nonce('tj_' . $action);
    unset($_COOKIE[LOGGED_IN_COOKIE]);
    return $nonce;
}
function ft_cleanup(): void {
    global $users, $posts, $terms, $jars, $temporary_files, $additional_terms, $wpdb;

    wp_set_current_user(0);
    foreach ($users as $uid) {
        $apps = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . tj_table('applications') . ' WHERE user_id=%d', $uid));
        foreach ($apps as $id) {
            $interviews = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . tj_table('interviews') . ' WHERE application_id=%d', $id));
            foreach ($interviews as $iid) { $wpdb->delete(tj_table('evaluations'), ['interview_id' => $iid]); }
            foreach (['interviews', 'history'] as $table) { $wpdb->delete(tj_table($table), ['application_id' => $id]); }
            $wpdb->delete(tj_table('notifications'), ['target' => 'quan-tri/ho-so?application=' . $id]);
        }
        foreach (tj_rows('files', 100, $uid) as $file) {
            $path = tj_private_dir() . '/' . basename($file['stored_name']);
            if (is_file($path)) { unlink($path); }
        }
        foreach (['applications', 'files', 'notifications', 'saved'] as $table) { $wpdb->delete(tj_table($table), ['user_id' => $uid]); }
        $wpdb->delete(tj_table('audit'), ['actor_id' => $uid]);
        wp_delete_user($uid);
    }
    foreach (array_reverse($posts) as $id) { wp_delete_post($id, true); }
    foreach ($terms as $tax => $id) { wp_delete_term($id, 'tj_' . $tax); }
    foreach ($additional_terms as [$id, $taxonomy]) { wp_delete_term($id, $taxonomy); }
    foreach (array_merge(array_values($jars), $temporary_files) as $path) { if (is_file($path)) { unlink($path); } }
    echo "Removed only this suite's users, posts, taxonomy and recruitment fixtures.\n";
}

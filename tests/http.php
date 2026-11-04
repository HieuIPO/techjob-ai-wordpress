<?php
/** Real HTTP form/upload/authorization regression suite. Local WordPress only. */
if (PHP_SAPI !== 'cli') { exit; }
$root = dirname(__DIR__);
require_once $root . '/runtime/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
$base = 'http://localhost:8080'; $cookies = tempnam(sys_get_temp_dir(), 'tj-cookie-');
$login = 'tj_http_' . bin2hex(random_bytes(5)); $password = bin2hex(random_bytes(16));
$checks = 0; $file_path = tempnam(sys_get_temp_dir(), 'tj-cv-'); $fake_path = tempnam(sys_get_temp_dir(), 'tj-fake-');
function request(string $path, ?array $data = null): array {
    global $base, $cookies;
    $curl = curl_init(str_starts_with($path, 'http') ? $path : $base . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4, CURLOPT_COOKIEJAR => $cookies, CURLOPT_COOKIEFILE => $cookies, CURLOPT_TIMEOUT => 20]);
    if ($data !== null) { curl_setopt($curl, CURLOPT_POST, true); curl_setopt($curl, CURLOPT_POSTFIELDS, $data); }
    $body = curl_exec($curl);
    if ($body === false) { throw new RuntimeException(curl_error($curl)); }
    $response = ['body' => $body, 'status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE), 'url' => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL)]; curl_close($curl); return $response;
}
function verify(bool $condition, string $message): void { global $checks; if (!$condition) { throw new RuntimeException($message); } $checks++; }
function nonce(array $response): string { preg_match('/name="_tj_nonce" value="([^"]+)"/', $response['body'], $match); if (empty($match[1])) { throw new RuntimeException('Form nonce missing'); } return $match[1]; }
function submit(string $action, string $nonce, array $values = []): array { return request('/wp-admin/admin-post.php', $values + ['action' => 'tj_action', 'tj_action' => $action, '_tj_nonce' => $nonce]); }
try {
    $home = request('/?lang=vi'); verify($home['status'] === 200 && str_contains($home['body'], 'Tìm kiếm công việc mơ ước'), 'Homepage renders');
    $asset = request('/wp-content/plugins/techjob-ai-core/assets/techjob.css'); verify(str_contains($asset['body'], '.tj-button'), 'Migration stylesheet is served');
    $english = request('/?lang=en'); verify(str_contains($english['body'], 'Find your next IT opportunity'), 'English homepage');
    $blocked = request('/quan-tri/'); verify(str_contains($blocked['url'], '/dang-nhap/'), 'Guest admin redirects to sign in');
    $register = request('/dang-ky/?lang=vi');
    $registered = submit('register', nonce($register), ['full_name' => 'HTTP Test Candidate', 'username' => $login, 'email' => $login . '@techjob.test', 'password' => $password]);
    verify(str_contains($registered['url'], '/tai-khoan/ho-so/'), 'Registration signs candidate in');
    $profile = request('/tai-khoan/ho-so/?lang=vi');
    $saved = submit('profile', nonce($profile), ['full_name' => 'HTTP Test Candidate', 'email' => $login . '@techjob.test', 'phone' => '0900000000']);
    verify(str_contains($saved['body'], 'Đã lưu thành công.'), 'Candidate can save profile');
    $forbidden = request('/quan-tri/'); verify($forbidden['status'] === 403, 'Candidate admin URL is forbidden');
    $other_cv = request('/tai-cv/1/'); verify($other_cv['status'] === 403, 'Cannot download another candidate CV by ID');
    $other_application = request('/tai-khoan/ung-tuyen/?application=1'); verify($other_application['status'] === 403, 'Cannot view another candidate application by ID');
    $csrf = submit('profile', 'invalid', ['full_name' => 'tampered']); verify($csrf['status'] === 403, 'Invalid nonce rejected');
    $page = request('/tai-khoan/ho-so/?lang=vi');
    preg_match('/name="tj_action" value="upload_cv".*?name="_tj_nonce" value="([^"]+)"/s', $page['body'], $upload_nonce);
    file_put_contents($fake_path, '<?php echo "not a pdf";');
    $fake = submit('upload_cv', $upload_nonce[1], ['cv' => new CURLFile($fake_path, 'application/pdf', 'fake.pdf')]);
    verify(str_contains(urldecode($fake['url']), 'CV phải là'), 'Fake PDF upload rejected');
    $sample = $GLOBALS['wpdb']->get_row('SELECT * FROM ' . tj_table('files') . ' ORDER BY id LIMIT 1', ARRAY_A);
    copy(tj_private_dir() . '/' . $sample['stored_name'], $file_path);
    $uploaded = submit('upload_cv', $upload_nonce[1], ['cv' => new CURLFile($file_path, 'application/pdf', 'http-test-cv.pdf')]);
    verify(str_contains($uploaded['body'], 'http-test-cv.pdf'), 'Real PDF upload persists');
    $test_user = get_user_by('login', $login);
    $file = $GLOBALS['wpdb']->get_row($GLOBALS['wpdb']->prepare('SELECT * FROM ' . tj_table('files') . ' WHERE user_id=%d ORDER BY id DESC LIMIT 1', $test_user->ID), ARRAY_A);
    $download = request('/tai-cv/' . $file['id'] . '/'); verify(str_starts_with($download['body'], '%PDF-'), 'Owner can download CV');
    $direct = request('/wp-content/uploads/' . $file['stored_name']); verify(!str_starts_with($direct['body'], '%PDF-'), 'Private CV is not in public uploads');
    preg_match('~href="([^" ]+/viec-lam/[^/"?]+/\?lang=vi)"~', $home['body'], $job_link);
    $job_page = request(html_entity_decode($job_link[1]));
    verify(str_contains($job_page['body'], 'Nộp hồ sơ'), 'Job detail renders application form');
    preg_match('/name="job_id" value="([0-9]+)"/', $job_page['body'], $job_id);
    $applied = submit('apply', nonce($job_page), ['job_id' => $job_id[1], 'file_id' => $file['id'], 'consent' => '1', 'letter' => 'HTTP integration test']);
    verify(str_contains($applied['url'], '/tai-khoan/ung-tuyen/') && str_contains($applied['body'], 'Mới nộp'), 'HTTP application succeeds');
    $again = submit('apply', nonce($job_page), ['job_id' => $job_id[1], 'file_id' => $file['id'], 'consent' => '1']);
    verify(str_contains(urldecode($again['url']), 'đã ứng tuyển'), 'HTTP duplicate is rejected');
    $config_page = request('/tai-khoan/?lang=vi');
    preg_match('~<script id="tj-config" type="application/json">(.*?)</script>~s', $config_page['body'], $config_match);
    $config = json_decode($config_match[1], true);
    verify(!str_contains($config_match[1], 'GEMINI_API_KEY'), 'AI config does not publish API key');
    $curl = curl_init($base . '/wp-json/techjob/v1/ai/start');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_COOKIEFILE => $cookies, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-WP-Nonce: ' . $config['nonce']], CURLOPT_POSTFIELDS => json_encode(['mode' => 'demo', 'skill' => 'PHP', 'level' => 'Junior', 'language' => 'vi'])]);
    $reply = json_decode(curl_exec($curl), true); $http_status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
    verify($http_status === 200 && ($reply['mode'] ?? '') === 'demo' && !empty($reply['message']), 'Real REST request starts demo interview');
    // Exercise authorization with a valid nonce for this candidate's real HTTP session.
    wp_set_current_user($test_user->ID);
    foreach (file($cookies, FILE_IGNORE_NEW_LINES) as $cookie_line) {
        $parts = explode("\t", $cookie_line);
        if (count($parts) === 7 && $parts[5] === LOGGED_IN_COOKIE) { $_COOKIE[LOGGED_IN_COOKIE] = rawurldecode($parts[6]); }
    }
    $admin_attempt = submit('save_settings', wp_create_nonce('tj_save_settings'), ['model' => 'unauthorized-model']);
    verify($admin_attempt['status'] === 403, 'Valid candidate nonce cannot authorize an admin action');
    $bad_profile = submit('profile', wp_create_nonce('tj_profile'), ['full_name' => 'Bad data', 'email' => 'invalid', 'phone' => '<script>bad</script>']);
    verify(str_contains(urldecode($bad_profile['url']), 'Kiểm tra'), 'Invalid profile input rejected');
    verify(get_user_by('id', $test_user->ID)->user_email === $login . '@techjob.test', 'Invalid profile does not change persisted email');
    $invalid_id = request('/tai-cv/999999999/'); verify($invalid_id['status'] === 403, 'Unknown CV ID discloses no file');
    $new_job = 0;
    foreach (get_posts(['post_type' => 'tj_job', 'numberposts' => -1]) as $available) { if ($available->ID !== (int)$job_id[1] && tj_job_open($available->ID)) { $new_job = $available->ID; break; } }
    verify($new_job > 0, 'Second open job exists for inline CV upload');
    submit('apply', wp_create_nonce('tj_apply'), ['job_id' => $new_job, 'consent' => '1', 'cv' => new CURLFile($file_path, 'application/pdf', 'inline-upload-cv.pdf')]);
    $inline = $wpdb->get_row($wpdb->prepare('SELECT a.file_id,f.original_name FROM ' . tj_table('applications') . ' a JOIN ' . tj_table('files') . ' f ON a.file_id=f.id WHERE a.user_id=%d AND a.job_id=%d', $test_user->ID, $new_job), ARRAY_A);
    verify($inline && $inline['original_name'] === 'inline-upload-cv.pdf', 'Inline application uses the newly uploaded CV ID');
    update_user_meta($test_user->ID, 'tj_disabled', 1);
    WP_Session_Tokens::get_instance($test_user->ID)->destroy_all();
    $revoked = request('/tai-khoan/ho-so/'); verify(str_contains($revoked['url'], '/dang-nhap/'), 'Revoked session cannot open protected page');
    $disabled_login = request('/dang-nhap/?lang=vi');
    $disabled_result = submit('login', nonce($disabled_login), ['username' => $login, 'password' => $password]);
    verify(str_contains($disabled_result['url'], 'error='), 'Disabled account cannot sign in with correct password');
    update_user_meta($test_user->ID, 'tj_disabled', 0);
    $login_page = request('/dang-nhap/?lang=vi');
    $wrong_password = submit('login', nonce($login_page), ['username' => $login, 'password' => 'WrongPassword!']);
    verify(str_contains($wrong_password['url'], 'error='), 'Wrong password rejected');
    $login_page = request('/dang-nhap/?lang=vi');
    $logged_in = submit('login', nonce($login_page), ['username' => $login, 'password' => $password]);
    verify(str_contains($logged_in['url'], '/tai-khoan/'), 'Enabled candidate can sign in again');
    echo "PASS $checks HTTP checks\n";
} finally {
    global $wpdb;
    $user = get_user_by('login', $login);
    if ($user) {
        $apps = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . tj_table('applications') . ' WHERE user_id=%d', $user->ID));
        foreach ($apps as $id) { $wpdb->delete(tj_table('history'), ['application_id' => $id]); $wpdb->delete(tj_table('interviews'), ['application_id' => $id]); $wpdb->delete(tj_table('notifications'), ['target' => 'quan-tri/ho-so?application=' . $id]); }
        foreach (tj_rows('files', 100, $user->ID) as $file) { $path = tj_private_dir() . '/' . basename($file['stored_name']); if (is_file($path)) { unlink($path); } }
        foreach (['applications', 'files', 'saved', 'notifications', 'ai_sessions'] as $table) { $wpdb->delete(tj_table($table), ['user_id' => $user->ID]); }
        $wpdb->delete(tj_table('audit'), ['actor_id' => $user->ID]);
        wp_delete_user($user->ID);
    }
    foreach ([$cookies, $file_path, $fake_path] as $path) { if (is_file($path)) { unlink($path); } }
}

<?php
/** Restores only into a disposable database, never overwrites techjob. */
if (PHP_SAPI !== 'cli') { exit; }
$root = dirname(__DIR__);
$folders = array_values(array_filter(glob($root . '/backups/*', GLOB_ONLYDIR), fn($folder) => is_dir($folder . '/private'))); rsort($folders);
$backup = $folders[0] ?? '';
if (!$backup || !is_file($backup . '/database.sql')) { throw new RuntimeException('No portable backup found. Create one with backup.ps1 -Local.'); }
$settings = json_decode(file_get_contents($root . '/runtime/db-local.json'), true);
$db = new mysqli('127.0.0.1', 'root', $settings['root_password'], '', 3308);
$database = 'techjob_restore_test_' . bin2hex(random_bytes(5));
$db->query("CREATE DATABASE `$database` CHARACTER SET utf8mb4");
try {
    $db->select_db($database);
    $sql = file_get_contents($backup . '/database.sql');
    if (preg_match('/^\s*(?:CREATE DATABASE|USE\s)/mi', $sql)) { throw new RuntimeException('Backup must not switch databases'); }
    $db->multi_query($sql);
    do { if ($result = $db->store_result()) { $result->free(); } } while ($db->more_results() && $db->next_result());
    $jobs = (int)$db->query("SELECT COUNT(*) FROM wp_posts WHERE post_type='tj_job' AND post_status='publish'")->fetch_row()[0];
    $candidates = (int)$db->query("SELECT COUNT(*) FROM wp_usermeta WHERE meta_key='wp_capabilities' AND meta_value LIKE '%tj_candidate%'")->fetch_row()[0];
    $files = $db->query('SELECT stored_name FROM wp_tj_files'); $count = 0;
    while ($file = $files->fetch_assoc()) { if (!is_file($backup . '/private/' . basename($file['stored_name']))) { throw new RuntimeException('Missing CV in backup'); } $count++; }
    if ($jobs !== 24 || $candidates !== 8 || $count !== 8) { throw new RuntimeException('Unexpected demo data counts in backup'); }
    echo "PASS backup restore: $jobs jobs, $candidates candidates, $count CVs\n";
} finally {
    $db->query("DROP DATABASE `$database`");
}

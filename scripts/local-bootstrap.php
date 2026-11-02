<?php
/** Isolated Windows fallback when Docker Desktop cannot start. Never touches existing databases. */
if (PHP_SAPI !== 'cli') { exit; }
$root = dirname(__DIR__);
$config = $root . '/runtime/wordpress/wp-config.php';
if (file_exists($config)) { echo "Local configuration already exists.\n"; exit; }
$db = new mysqli('127.0.0.1', 'root', '', '', 3308);
$actual_dir = $db->query('SELECT @@datadir')->fetch_row()[0];
if (strtolower(str_replace('\\', '/', rtrim($actual_dir, '/\\'))) !== strtolower(str_replace('\\', '/', $root . '/runtime/mysql'))) {
    throw new RuntimeException('Refusing to configure a database outside this project runtime.');
}
$password = bin2hex(random_bytes(24));
$db->query("CREATE DATABASE IF NOT EXISTS techjob CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->query("CREATE USER IF NOT EXISTS 'techjob'@'localhost' IDENTIFIED BY '$password'");
$db->query("GRANT ALL ON techjob.* TO 'techjob'@'localhost'");
$root_password = bin2hex(random_bytes(24));
$db->query("ALTER USER 'root'@'localhost' IDENTIFIED BY '$root_password'");
file_put_contents($root . '/runtime/db-local.json', json_encode(['user' => 'techjob', 'password' => $password, 'root_password' => $root_password, 'port' => 3308]));
$php = "<?php\n";
foreach (['DB_NAME' => 'techjob', 'DB_USER' => 'techjob', 'DB_PASSWORD' => $password, 'DB_HOST' => '127.0.0.1:3308', 'DB_CHARSET' => 'utf8mb4', 'DB_COLLATE' => '', 'TECHJOB_PRIVATE_DIR' => str_replace('\\', '/', $root) . '/runtime/private', 'WP_HOME' => 'http://localhost:8080', 'WP_SITEURL' => 'http://localhost:8080', 'WP_ENVIRONMENT_TYPE' => 'local', 'DISALLOW_FILE_EDIT' => true] as $key => $value) { $php .= 'define(' . var_export($key, true) . ', ' . var_export($value, true) . ");\n"; }
foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'] as $key) { $php .= 'define(' . var_export($key, true) . ', ' . var_export(bin2hex(random_bytes(48)), true) . ");\n"; }
$php .= "\$table_prefix = 'wp_';\nif (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }\nrequire_once ABSPATH . 'wp-settings.php';\n";
file_put_contents($config, $php);
echo "Isolated local configuration created.\n";

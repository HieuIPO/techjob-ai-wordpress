<?php
if (PHP_SAPI !== 'cli') { exit; }
$settings = json_decode(file_get_contents(dirname(__DIR__) . '/runtime/db-local.json'), true);
try {
    $db = new mysqli('127.0.0.1', 'root', $settings['root_password'], '', 3308);
    $db->query('SHUTDOWN');
    echo "MariaDB stopped cleanly.\n";
} catch (mysqli_sql_exception $error) { fwrite(STDERR, "MariaDB is not running or could not be stopped.\n"); exit(1); }

<?php
/** PHP development server only. */
if (PHP_SAPI !== 'cli-server') { exit; }
$root = dirname(__DIR__) . '/runtime/wordpress';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = realpath($root . $path);
// Junction-backed project assets live outside the downloaded WordPress root.
$assets = realpath(dirname(__DIR__) . '/wp-content/plugins/techjob-ai-core/assets');
if ($file && $assets && str_starts_with($file, $assets . DIRECTORY_SEPARATOR) && in_array(pathinfo($file, PATHINFO_EXTENSION), ['css', 'js', 'png', 'jpg', 'svg'], true)) {
    header('Content-Type: ' . (['css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml'][pathinfo($file, PATHINFO_EXTENSION)]));
    readfile($file); return true;
}
if ($file && str_starts_with($file, realpath($root) . DIRECTORY_SEPARATOR) && is_file($file)) { return false; }
require $root . '/index.php';

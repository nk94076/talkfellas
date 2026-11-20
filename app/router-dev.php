<?php // Local testing only: php -S localhost:8000 router-dev.php (emulates nginx try_files)
$f = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($f !== __DIR__ . '/' && file_exists($f) && !is_dir($f)) return false;
if (is_dir($f) && file_exists($f . '/index.php')) { require $f . '/index.php'; return; }
require __DIR__ . '/index.php';

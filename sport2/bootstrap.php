<?php
/**
 * Sport2 Bootstrap - Autoloader & Initialization
 */
define('SPORT2_ROOT', __DIR__);

$config = require SPORT2_ROOT . '/config/config.php';
date_default_timezone_set($config['app']['timezone']);

// PSR-4 Autoloader
spl_autoload_register(function (string $class) {
    $prefix = 'Sport2\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = SPORT2_ROOT . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

// Global config accessor
function sport2_config(): array {
    static $cfg;
    return $cfg ??= require SPORT2_ROOT . '/config/config.php';
}

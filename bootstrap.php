<?php
/**
 * Bootstrap / Autoloader
 */
define('BASE_PATH', __DIR__);

spl_autoload_register(function (string $class) {
    // App\Core\Database -> src/Core/Database.php
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = BASE_PATH . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load config
$config = require BASE_PATH . '/config/config.php';

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

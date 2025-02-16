<?php
// Define root path
define('ROOT_PATH', dirname(__DIR__));

// Autoloader function
spl_autoload_register(function ($class) {
    // Convert namespace to full file path
    $prefix = 'App\\';
    $base_dir = ROOT_PATH . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// PhpSpreadsheet autoloader
require_once ROOT_PATH . '/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';

// Initialize core components
require_once ROOT_PATH . '/app/models/Config.php';
require_once ROOT_PATH . '/app/models/ErrorHandler.php';
require_once ROOT_PATH . '/app/models/Logger.php';
require_once ROOT_PATH . '/app/models/Security.php';
require_once ROOT_PATH . '/app/models/Performance.php';

// Initialize components
Config::init();
ErrorHandler::init();
Logger::init();
Security::init();
Performance::start('request');
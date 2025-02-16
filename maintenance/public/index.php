<?php
session_start();

// Load essential files
require_once '../app/models/ErrorHandler.php';
require_once '../app/models/Logger.php';
require_once '../app/models/Config.php';
require_once '../app/models/Security.php';
require_once '../app/models/Performance.php';

// Initialize error handling and logging
ErrorHandler::init();
Logger::init();

// Start performance monitoring
Performance::start('request');

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('Cross-Origin-Opener-Policy: same-origin');
header('Cross-Origin-Resource-Policy: same-origin');
header('Cross-Origin-Embedder-Policy: require-corp');

// Set caching headers for static resources
$uri = $_SERVER['REQUEST_URI'];
if (preg_match('/\.(css|js|jpg|jpeg|png|gif|ico)$/', $uri)) {
    header('Cache-Control: public, max-age=31536000'); // 1 year
    header('Pragma: public');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
}

// Initialize configuration and security
Config::init();
Security::init();

// Handle routing
$request = $_SERVER['REQUEST_URI'];
$basePath = str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
$path = str_replace($basePath, '', $request);
$path = parse_url($path, PHP_URL_PATH);

// Routes configuration
$routes = [
    '/' => ['MainController', 'index'],
    '/login' => ['AuthController', 'login'],
    '/register' => ['AuthController', 'register'],
    '/logout' => ['AuthController', 'logout'],
    '/upload' => ['UploadController', 'index'],
    '/upload/circuit' => ['UploadController', 'uploadCircuit'],
    '/upload/maintenance' => ['UploadController', 'uploadMaintenance'],
    '/maintenance' => ['MaintenanceController', 'index'],
    '/maintenance/view' => ['MaintenanceController', 'view'],
    '/maintenance/edit' => ['MaintenanceController', 'edit'],
    '/maintenance/delete' => ['MaintenanceController', 'delete']
];

try {
    // Check system health periodically (1% chance)
    if (rand(1, 100) === 1) {
        Performance::checkSystemHealth();
    }

    // Route handling
    if (array_key_exists($path, $routes)) {
        [$controller, $method] = $routes[$path];
        require_once "../app/controllers/{$controller}.php";
        $controllerInstance = new $controller();
        $controllerInstance->$method();
    } else {
        header("HTTP/1.0 404 Not Found");
        require_once '../app/views/errors/404.php';
    }
} catch (Exception $e) {
    Logger::error('Application error: ' . $e->getMessage());
    header("HTTP/1.0 500 Internal Server Error");
    require_once '../app/views/errors/500.php';
} finally {
    // End performance monitoring
    Performance::end('request');
    
    // Log request metrics
    $metrics = Performance::getSummary();
    Logger::debug('Request completed', $metrics);
}
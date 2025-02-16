<?php
namespace App\Controllers;

use App\Models\Performance;
use App\Models\Security;
use App\Models\Logger;
use App\Models\Config;
use App\Models\Utility;
use App\Models\ValidationHelper;
use Exception;

class Controller {
    protected $performance;
    protected $logger;

    public function __construct() {
        $this->performance = new Performance();
        $this->logger = new Logger();
        
        // Start measuring overall request performance
        Performance::start('request');
        
        // Initialize security measures
        Security::init();
        
        // Session security checks
        $this->validateSession();
        
        // Check system health periodically (1% chance per request)
        if (rand(1, 100) === 1) {
            Performance::checkSystemHealth();
        }
    }

    protected function validateSession() {
        if (isset($_SESSION['user_id'])) {
            // Check session timeout
            $timeout = Config::get('session.timeout', 7200); // 2 hours default
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
                $this->sessionTimeout();
            }

            // Validate IP and User Agent
            if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                $this->sessionTimeout();
            }
            if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                $this->sessionTimeout();
            }

            // Update last activity
            $_SESSION['last_activity'] = time();
        }
    }

    protected function sessionTimeout() {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 3600,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        $this->setFlash('error', 'Session expired. Please login again.');
        $this->redirect('login');
    }

    // Load model with performance tracking
    protected function model($model) {
        Performance::start('model_load_' . $model);
        require_once '../app/models/' . $model . '.php';
        $instance = new $model();
        Performance::end('model_load_' . $model);
        return $instance;
    }

    // Load view with performance tracking
    protected function view($view, $data = []) {
        Performance::start('view_render_' . $view);
        
        try {
            if (file_exists('../app/views/' . $view . '.php')) {
                // Extract data for view
                extract($data);
                
                // Start output buffering
                ob_start();
                require_once '../app/views/' . $view . '.php';
                $content = ob_get_clean();
                
                // Add performance metrics if in debug mode
                if (Config::get('app.debug')) {
                    $metrics = Performance::getSummary();
                    $debugInfo = "<!-- Performance Metrics:\n";
                    $debugInfo .= "Memory Peak: {$metrics['memory_peak']}\n";
                    $debugInfo .= "Query Count: {$metrics['query_count']}\n";
                    $debugInfo .= "Slow Queries: {$metrics['slow_queries']}\n";
                    $debugInfo .= "-->";
                    $content .= $debugInfo;
                }
                
                echo $content;
            } else {
                throw new \Exception("View $view not found");
            }
        } catch (\Exception $e) {
            Logger::error('View rendering failed', [
                'view' => $view,
                'error' => $e->getMessage()
            ]);
            $this->showError(500);
        }
        
        Performance::end('view_render_' . $view);
    }

    // Enhanced redirect with logging
    protected function redirect($url) {
        Logger::info('Redirecting user', [
            'from' => $_SERVER['REQUEST_URI'],
            'to' => $url
        ]);
        header('Location: ' . URL_ROOT . '/' . $url);
        exit();
    }

    // Flash message system with session handling
    protected function setFlash($name, $message, $type = 'info') {
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        $_SESSION['flash'][$name] = [
            'message' => $message,
            'type' => $type
        ];
    }

    // Get and clear flash message
    protected function getFlash($name) {
        if (isset($_SESSION['flash'][$name])) {
            $flash = $_SESSION['flash'][$name];
            unset($_SESSION['flash'][$name]);
            return $flash;
        }
        return null;
    }

    // Error handling with custom error pages
    protected function showError($code) {
        http_response_code($code);
        $this->view("errors/$code");
    }

    // Clean up and log performance metrics
    public function __destruct() {
        Performance::end('request');
        $metrics = Performance::getSummary();
        Logger::debug('Request completed', $metrics);
    }

    // Validate CSRF token
    protected function validateCsrf() {
        $token = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrfToken($token)) {
            Logger::warning('CSRF validation failed', [
                'ip' => $_SERVER['REMOTE_ADDR'],
                'uri' => $_SERVER['REQUEST_URI']
            ]);
            $this->showError(403);
            exit();
        }
    }

    // Generate and include CSRF token in forms
    protected function getCsrfToken() {
        return Security::generateCsrfToken();
    }

    // Handle file uploads with validation and logging
    protected function handleFileUpload($file, $allowedTypes = ['xlsx'], $maxSize = null) {
        try {
            Performance::start('file_upload');
            
            $validation = ValidationHelper::validateFileUpload($file, 'excel');
            if (!empty($validation)) {
                throw new \Exception(implode(', ', $validation));
            }

            $fileName = time() . '_' . Security::sanitizeFilePath($file['name']);
            $uploadPath = UPLOAD_DIR . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                Performance::monitorFileUpload($file['size']);
                Logger::info('File uploaded successfully', [
                    'name' => $fileName,
                    'size' => Utility::formatFileSize($file['size'])
                ]);
                return $uploadPath;
            }

            throw new \Exception('Failed to move uploaded file');
        } catch (\Exception $e) {
            Logger::error('File upload failed', [
                'error' => $e->getMessage(),
                'file' => $file['name']
            ]);
            throw $e;
        } finally {
            Performance::end('file_upload');
        }
    }
}
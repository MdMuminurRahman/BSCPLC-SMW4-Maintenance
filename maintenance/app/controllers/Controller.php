<?php
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
        
        // Check system health periodically (1% chance per request)
        if (rand(1, 100) === 1) {
            Performance::checkSystemHealth();
        }
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
                throw new Exception("View $view not found");
            }
        } catch (Exception $e) {
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
                throw new Exception(implode(', ', $validation));
            }

            $fileName = time() . '_' . Security::sanitizeFilePath($file['name']);
            $uploadPath = UPLOAD_DIR . '/' . $fileName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                Performance::monitorFileUpload($file['size']);
                Logger::info('File uploaded successfully', [
                    'name' => $fileName,
                    'size' => Performance::formatBytes($file['size'])
                ]);
                return $uploadPath;
            }

            throw new Exception('Failed to move uploaded file');
        } catch (Exception $e) {
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
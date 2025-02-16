<?php
class Security {
    /**
     * Initialize security measures
     */
    public static function init() {
        // Set secure session cookie parameters
        session_set_cookie_params([
            'lifetime' => 7200, // 2 hours
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set security headers
        self::setSecurityHeaders();
    }

    /**
     * Verify user authentication
     */
    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new Exception('CSRF token validation failed');
        }
        return true;
    }

    /**
     * Set security headers
     */
    private static function setSecurityHeaders() {
        $headers = [
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://kit.fontawesome.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;"
        ];

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
    }

    /**
     * Sanitize input data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = ['xlsx'], $maxSize = 10485760) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }

        $fileInfo = pathinfo($file['name']);
        if (!in_array(strtolower($fileInfo['extension']), $allowedTypes)) {
            throw new Exception('Invalid file type');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds limit');
        }

        return true;
    }

    /**
     * Generate secure random string
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Clean potentially dangerous file paths
     */
    public static function sanitizeFilePath($path) {
        $path = str_replace(['../', '..\\'], '', $path);
        return preg_replace('/[^a-zA-Z0-9\/\-._]/', '', $path);
    }

    /**
     * Rate limiting for API requests
     */
    public static function checkRateLimit($key, $limit = 60, $period = 60) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        
        $current = $redis->incr($key);
        if ($current === 1) {
            $redis->expire($key, $period);
        }
        
        if ($current > $limit) {
            throw new Exception('Rate limit exceeded');
        }
        
        return true;
    }
}
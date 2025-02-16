<?php
namespace App\Models;

use Exception;

class Security {
    private static $cache;

    public static function init() {
        session_set_cookie_params([
            'lifetime' => 7200,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Try Redis first, fallback to session cache
        try {
            self::$cache = new RedisCache('127.0.0.1', 6379, 'bsccl_maintenance:');
        } catch (\Throwable $e) {
            Logger::warning('Redis unavailable, using session cache', [
                'error' => $e->getMessage()
            ]);
            self::$cache = new SessionCache();
        }

        self::setSecurityHeaders();
    }

    public static function checkRateLimit($key, $limit = 60, $period = 60) {
        $attempts = (int)self::$cache->get("rate_limit:$key") ?: 0;
        
        if ($attempts >= $limit) {
            throw new \Exception('Rate limit exceeded');
        }

        self::$cache->set("rate_limit:$key", $attempts + 1, $period);
        return true;
    }

    public static function generateCsrfToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public static function verifyCsrfToken($token) {
        if (empty($token) || empty($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $token)) {
            throw new \Exception('CSRF token validation failed');
        }
        return true;
    }

    private static function setSecurityHeaders() {
        $headers = [
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';",
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
        ];

        foreach ($headers as $header => $value) {
            if (!headers_sent()) {
                header("$header: $value");
            }
        }
    }

    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }
    }

    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    public static function validateFileUpload($file, $allowedTypes = ['xlsx'], $maxSize = 10485760) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('File upload failed');
        }

        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileType, ['xlsx', 'xls'])) {
            throw new \Exception('Invalid file type');
        }

        if ($file['size'] > Config::get('upload.max_size', 10 * 1024 * 1024)) {
            throw new \Exception('File size exceeds limit');
        }

        return true;
    }

    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }

    public static function sanitizeFilePath($path) {
        $path = str_replace(['../', '..\\'], '', $path);
        return preg_replace('/[^a-zA-Z0-9\/\-._]/', '', $path);
    }
}
<?php
namespace App\Models;

class Config {
    private static $config = [];
    private static $initialized = false;

    /**
     * Initialize configuration
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }

        // Load environment variables
        self::loadEnv();

        // Set default configurations
        self::$config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'BSCCL Maintenance System',
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => isset($_ENV['APP_DEBUG']) ? filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN) : false,
                'url' => $_ENV['APP_URL'] ?? 'http://localhost'
            ],
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? '3306',
                'name' => $_ENV['DB_DATABASE'] ?? 'bsccl_maintenance',
                'user' => $_ENV['DB_USERNAME'] ?? 'root',
                'pass' => $_ENV['DB_PASSWORD'] ?? ''
            ],
            'upload' => [
                'max_size' => (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10) * 1024 * 1024, // Convert MB to bytes
                'allowed_extensions' => explode(',', $_ENV['ALLOWED_EXTENSIONS'] ?? 'xlsx'),
                'dir' => dirname(dirname(__DIR__)) . '/uploads'
            ],
            'timezone' => $_ENV['DEFAULT_TIMEZONE'] ?? 'UTC',
            'log' => [
                'level' => $_ENV['LOG_LEVEL'] ?? 'error',
                'path' => $_ENV['LOG_PATH'] ?? '../logs/'
            ]
        ];

        // Ensure upload directory exists
        if (!is_dir(self::$config['upload']['dir'])) {
            mkdir(self::$config['upload']['dir'], 0777, true);
        }

        // Set timezone
        date_default_timezone_set(self::$config['timezone']);

        self::$initialized = true;
    }

    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        if (!self::$initialized) {
            self::init();
        }

        $keys = explode('.', $key);
        $config = self::$config;

        foreach ($keys as $segment) {
            if (!isset($config[$segment])) {
                return $default;
            }
            $config = $config[$segment];
        }

        return $config;
    }

    /**
     * Set configuration value
     */
    public static function set($key, $value) {
        if (!self::$initialized) {
            self::init();
        }

        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $segment) {
            if (!isset($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }

        $config = $value;
    }

    /**
     * Load environment variables from .env file
     */
    private static function loadEnv() {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (!array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                }
            }
        }
    }

    /**
     * Create required upload directories
     */
    private static function createUploadDirectories() {
        $dirs = [
            self::$config['upload']['circuit_dir'],
            self::$config['upload']['maintenance_dir']
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Check if application is in debug mode
     */
    public static function isDebug() {
        return self::get('app.debug', false);
    }

    /**
     * Get application environment
     */
    public static function getEnvironment() {
        return self::get('app.env', 'production');
    }
}
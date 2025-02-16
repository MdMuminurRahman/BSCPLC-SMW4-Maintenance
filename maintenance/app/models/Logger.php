<?php
class Logger {
    private static $logPath = '../logs/';
    
    public static function init() {
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0777, true);
        }
    }

    public static function log($message, $type = 'INFO') {
        self::init();
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
        $logFile = self::$logPath . date('Y-m-d') . '.log';
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    public static function error($message, $exception = null) {
        $logMessage = $message;
        if ($exception) {
            $logMessage .= "\nException: " . $exception->getMessage();
            $logMessage .= "\nStack Trace: " . $exception->getTraceAsString();
        }
        self::log($logMessage, 'ERROR');
    }

    public static function info($message) {
        self::log($message, 'INFO');
    }

    public static function warning($message) {
        self::log($message, 'WARNING');
    }

    public static function debug($message) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            self::log($message, 'DEBUG');
        }
    }
}
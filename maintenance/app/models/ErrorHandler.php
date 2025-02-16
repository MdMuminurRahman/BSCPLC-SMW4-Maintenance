<?php
class ErrorHandler {
    /**
     * Initialize error and exception handlers
     */
    public static function init() {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }

    /**
     * Handle regular PHP errors
     */
    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::getErrorType($errno);
        $message = "$errorType: $errstr in $errfile on line $errline";
        
        Logger::error($message);
        
        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<div style='background-color: #ffebee; border: 1px solid #ef5350; padding: 15px; margin: 10px;'>";
            echo "<h3 style='color: #c62828; margin: 0;'>$errorType</h3>";
            echo "<p style='margin: 10px 0;'>$errstr</p>";
            echo "<p style='margin: 0; color: #666;'>File: $errfile</p>";
            echo "<p style='margin: 0; color: #666;'>Line: $errline</p>";
            echo "</div>";
        } else {
            // In production, show a user-friendly error message
            self::showUserFriendlyError();
        }

        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception) {
        $message = "Uncaught Exception: " . $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();

        Logger::error($message . "\nFile: $file\nLine: $line\nTrace:\n$trace");

        if (defined('APP_DEBUG') && APP_DEBUG) {
            echo "<div style='background-color: #fff3e0; border: 1px solid #ff9800; padding: 15px; margin: 10px;'>";
            echo "<h3 style='color: #e65100; margin: 0;'>Uncaught Exception</h3>";
            echo "<p style='margin: 10px 0;'>" . htmlspecialchars($message) . "</p>";
            echo "<p style='margin: 0; color: #666;'>File: $file</p>";
            echo "<p style='margin: 0; color: #666;'>Line: $line</p>";
            echo "<pre style='background: #f5f5f5; padding: 10px; margin-top: 10px;'>" . htmlspecialchars($trace) . "</pre>";
            echo "</div>";
        } else {
            self::showUserFriendlyError();
        }
    }

    /**
     * Handle fatal errors
     */
    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $message = "Fatal Error: {$error['message']}";
            $file = $error['file'];
            $line = $error['line'];

            Logger::error($message . "\nFile: $file\nLine: $line");

            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo "<div style='background-color: #fbe9e7; border: 1px solid #ff5722; padding: 15px; margin: 10px;'>";
                echo "<h3 style='color: #bf360c; margin: 0;'>Fatal Error</h3>";
                echo "<p style='margin: 10px 0;'>" . htmlspecialchars($message) . "</p>";
                echo "<p style='margin: 0; color: #666;'>File: $file</p>";
                echo "<p style='margin: 0; color: #666;'>Line: $line</p>";
                echo "</div>";
            } else {
                self::showUserFriendlyError();
            }
        }
    }

    /**
     * Get error type string from error number
     */
    private static function getErrorType($errno) {
        switch ($errno) {
            case E_ERROR:
                return 'Fatal Error';
            case E_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse Error';
            case E_NOTICE:
                return 'Notice';
            case E_CORE_ERROR:
                return 'Core Error';
            case E_CORE_WARNING:
                return 'Core Warning';
            case E_COMPILE_ERROR:
                return 'Compile Error';
            case E_COMPILE_WARNING:
                return 'Compile Warning';
            case E_USER_ERROR:
                return 'User Error';
            case E_USER_WARNING:
                return 'User Warning';
            case E_USER_NOTICE:
                return 'User Notice';
            case E_STRICT:
                return 'Strict Notice';
            case E_RECOVERABLE_ERROR:
                return 'Recoverable Error';
            case E_DEPRECATED:
                return 'Deprecated';
            case E_USER_DEPRECATED:
                return 'User Deprecated';
            default:
                return 'Unknown Error';
        }
    }

    /**
     * Show user-friendly error page
     */
    private static function showUserFriendlyError() {
        http_response_code(500);
        include '../app/views/errors/500.php';
        exit;
    }
}
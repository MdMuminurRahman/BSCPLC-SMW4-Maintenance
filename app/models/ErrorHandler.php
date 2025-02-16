<?php
namespace App\Models;

class ErrorHandler {
    private static $initialized = false;

    public static function init() {
        if (self::$initialized) {
            return;
        }

        // Set appropriate error reporting level based on environment
        $errorLevel = Config::get('app.debug', false) 
            ? E_ALL 
            : E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED;
        
        error_reporting($errorLevel);
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        
        self::$initialized = true;
    }

    public static function handleError($errno, $errstr, $errfile, $errline) {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorType = self::getErrorType($errno);
        $message = "$errorType: $errstr in $errfile on line $errline";
        
        Logger::error($message);
        
        if (Config::get('app.debug', false)) {
            self::renderError([
                'type' => $errorType,
                'message' => $errstr,
                'file' => $errfile,
                'line' => $errline
            ], 'error');
        } else {
            self::showUserFriendlyError();
        }

        return true;
    }

    public static function handleException($exception) {
        $message = "Uncaught Exception: " . $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();

        Logger::error($message . "\nFile: $file\nLine: $line\nTrace:\n$trace");

        if (Config::get('app.debug', false)) {
            self::renderError([
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $file,
                'line' => $line,
                'trace' => $trace
            ], 'exception');
        } else {
            self::showUserFriendlyError();
        }
    }

    public static function handleFatalError() {
        $error = error_get_last();
        if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $message = "Fatal Error: {$error['message']}";
            Logger::error($message . "\nFile: {$error['file']}\nLine: {$error['line']}");

            if (Config::get('app.debug', false)) {
                self::renderError([
                    'type' => 'Fatal Error',
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line']
                ], 'fatal');
            } else {
                self::showUserFriendlyError();
            }
        }
    }

    private static function renderError($error, $type) {
        $colors = [
            'error' => '#ffebee',
            'exception' => '#fff3e0',
            'fatal' => '#fbe9e7'
        ];
        
        $headerColors = [
            'error' => '#c62828',
            'exception' => '#e65100',
            'fatal' => '#bf360c'
        ];

        $backgroundColor = $colors[$type] ?? '#ffebee';
        $headerColor = $headerColors[$type] ?? '#c62828';

        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo "<div style='background-color: {$backgroundColor}; border: 1px solid {$headerColor}; padding: 15px; margin: 10px; font-family: monospace;'>";
        echo "<h3 style='color: {$headerColor}; margin: 0;'>{$error['type']}</h3>";
        echo "<p style='margin: 10px 0;'>" . htmlspecialchars($error['message'], ENT_QUOTES, 'UTF-8') . "</p>";
        echo "<p style='margin: 0; color: #666;'>File: " . htmlspecialchars($error['file'], ENT_QUOTES, 'UTF-8') . "</p>";
        echo "<p style='margin: 0; color: #666;'>Line: {$error['line']}</p>";
        
        if (isset($error['trace'])) {
            echo "<pre style='background: #f5f5f5; padding: 10px; margin-top: 10px; overflow: auto;'>" 
                . htmlspecialchars($error['trace'], ENT_QUOTES, 'UTF-8') 
                . "</pre>";
        }
        
        echo "</div>";
    }

    private static function getErrorType($errno) {
        return match ($errno) {
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
            E_ALL => 'All Errors',
            default => 'Unknown Error'
        };
    }

    private static function showUserFriendlyError() {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        include dirname(__DIR__) . '/views/errors/500.php';
        exit;
    }
}
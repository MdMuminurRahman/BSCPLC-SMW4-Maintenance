<?php
class Utility {
    // Bandwidth conversion map
    private static $bandwidthMap = [
        'VC4-64C' => 'STM64',
        'VC4-16C' => 'STM16',
        'VC4-4C' => 'STM4',
        'VC4' => 'STM1',
        '10G' => '10G',
        '100G' => '100G'
    ];

    /**
     * Convert bandwidth to standardized format
     */
    public static function standardizeBandwidth($bandwidth) {
        $bandwidth = strtoupper(trim($bandwidth));
        
        foreach (self::$bandwidthMap as $original => $standardized) {
            if (strpos($bandwidth, $original) !== false) {
                return $standardized;
            }
        }
        return $bandwidth;
    }

    /**
     * Format datetime to UTC string
     */
    public static function formatUTCDateTime($dateTime) {
        $dt = new DateTime($dateTime);
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate date range
     */
    public static function validateDateRange($startDate, $endDate) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        
        if ($start >= $end) {
            throw new Exception('End time must be after start time');
        }
        
        return true;
    }

    /**
     * Generate unique maintenance ID
     */
    public static function generateMaintenanceId() {
        return 'M' . date('Ymd') . '_' . substr(uniqid(), -6);
    }

    /**
     * Check if a string contains BSCCL (case insensitive)
     */
    public static function isBSCCLRelated($string) {
        return stripos($string, 'BSCCL') !== false;
    }

    /**
     * Format file size for display
     */
    public static function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Create backup of uploaded files
     */
    public static function createBackup($filePath) {
        $backupDir = dirname($filePath) . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        
        $fileName = basename($filePath);
        $backupPath = $backupDir . '/' . date('Y-m-d_H-i-s_') . $fileName;
        
        return copy($filePath, $backupPath);
    }

    /**
     * Validate circuit ID format
     */
    public static function validateCircuitId($circuitId) {
        // Add your circuit ID validation rules here
        return !empty(trim($circuitId)) && strlen($circuitId) <= 50;
    }

    /**
     * Log application errors
     */
    public static function logError($message, $context = []) {
        $logMessage = date('Y-m-d H:i:s') . " ERROR: " . $message;
        if (!empty($context)) {
            $logMessage .= " Context: " . json_encode($context);
        }
        error_log($logMessage . PHP_EOL, 3, '../logs/error.log');
    }
}
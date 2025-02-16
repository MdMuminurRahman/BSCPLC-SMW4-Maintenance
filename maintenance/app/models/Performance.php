<?php
class Performance {
    private static $timers = [];
    private static $queries = [];
    private static $memoryPeaks = [];

    /**
     * Start measuring performance for a specific operation
     */
    public static function start($operation) {
        self::$timers[$operation] = [
            'start' => microtime(true),
            'memory_start' => memory_get_usage()
        ];
    }

    /**
     * End measuring performance and log results
     */
    public static function end($operation) {
        if (!isset(self::$timers[$operation])) {
            return;
        }

        $end = microtime(true);
        $memoryEnd = memory_get_usage();
        
        $duration = ($end - self::$timers[$operation]['start']) * 1000; // Convert to milliseconds
        $memoryUsed = $memoryEnd - self::$timers[$operation]['memory_start'];

        self::$memoryPeaks[] = memory_get_peak_usage(true);

        Logger::debug("Performance: $operation", [
            'duration' => round($duration, 2) . 'ms',
            'memory' => self::formatBytes($memoryUsed),
            'peak_memory' => self::formatBytes(max(self::$memoryPeaks))
        ]);

        unset(self::$timers[$operation]);
    }

    /**
     * Track database query performance
     */
    public static function trackQuery($query, $params = [], $duration) {
        self::$queries[] = [
            'query' => $query,
            'params' => $params,
            'duration' => $duration
        ];

        if (count(self::$queries) > 100) {
            self::analyzeQueryPerformance();
        }
    }

    /**
     * Analyze query performance and log slow queries
     */
    private static function analyzeQueryPerformance() {
        $slowQueries = array_filter(self::$queries, function($q) {
            return $q['duration'] > 1000; // Queries taking more than 1 second
        });

        if (!empty($slowQueries)) {
            Logger::warning('Slow queries detected', [
                'count' => count($slowQueries),
                'queries' => array_map(function($q) {
                    return [
                        'query' => $q['query'],
                        'duration' => round($q['duration'], 2) . 'ms'
                    ];
                }, $slowQueries)
            ]);
        }

        self::$queries = []; // Reset queries array
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    /**
     * Get performance summary
     */
    public static function getSummary() {
        return [
            'memory_peak' => self::formatBytes(memory_get_peak_usage(true)),
            'query_count' => count(self::$queries),
            'slow_queries' => count(array_filter(self::$queries, function($q) {
                return $q['duration'] > 1000;
            }))
        ];
    }

    /**
     * Monitor file upload performance
     */
    public static function monitorFileUpload($fileSize) {
        $uploadSpeed = $fileSize / ini_get('max_execution_time');
        Logger::info('File upload performance', [
            'file_size' => self::formatBytes($fileSize),
            'upload_speed' => self::formatBytes($uploadSpeed) . '/s'
        ]);
    }

    /**
     * Check system health
     */
    public static function checkSystemHealth() {
        $health = [
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'disk_free_space' => disk_free_space('.')
        ];

        Logger::info('System health check', $health);
        return $health;
    }

    /**
     * Monitor real-time application metrics
     */
    public static function monitorRealtime() {
        $metrics = [
            'timestamp' => time(),
            'memory' => [
                'current' => memory_get_usage(),
                'peak' => memory_get_peak_usage(),
                'limit' => ini_get('memory_limit')
            ],
            'database' => [
                'connections' => count(self::$queries),
                'slow_queries' => count(array_filter(self::$queries, fn($q) => $q['duration'] > 1000))
            ],
            'files' => [
                'upload_dir_size' => self::getDirectorySize(UPLOAD_DIR),
                'log_dir_size' => self::getDirectorySize(Logger::getLogPath())
            ],
            'sessions' => [
                'active' => self::getActiveSessions()
            ]
        ];

        // Store metrics for analysis
        self::storeMetrics($metrics);

        // Alert if thresholds are exceeded
        self::checkThresholds($metrics);

        return $metrics;
    }

    /**
     * Get directory size recursively
     */
    private static function getDirectorySize($path) {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    /**
     * Get count of active sessions
     */
    private static function getActiveSessions() {
        $sessionPath = session_save_path() ?: sys_get_temp_dir();
        $count = 0;
        foreach (glob($sessionPath . "/sess_*") as $file) {
            if (time() - fileatime($file) < ini_get('session.gc_maxlifetime')) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Store metrics for analysis
     */
    private static function storeMetrics($metrics) {
        $db = new Database();
        $db->query("INSERT INTO performance_logs (operation_type, operation_details, duration_ms, memory_usage) 
                   VALUES (:type, :details, :duration, :memory)");
        
        $db->bind(':type', 'system_metrics');
        $db->bind(':details', json_encode($metrics));
        $db->bind(':duration', 0); // Not applicable for system metrics
        $db->bind(':memory', $metrics['memory']['current']);
        
        $db->execute();
    }

    /**
     * Check performance thresholds and alert if necessary
     */
    private static function checkThresholds($metrics) {
        $alerts = [];

        // Memory threshold (80% of limit)
        $memoryLimit = self::parseMemoryLimit(ini_get('memory_limit'));
        if ($metrics['memory']['current'] > $memoryLimit * 0.8) {
            $alerts[] = 'High memory usage detected';
        }

        // Database connection threshold
        if (count(self::$queries) > 100) {
            $alerts[] = 'High number of database queries detected';
        }

        // Disk space threshold (90% full)
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        if ($diskFree / $diskTotal < 0.1) {
            $alerts[] = 'Low disk space warning';
        }

        if (!empty($alerts)) {
            foreach ($alerts as $alert) {
                Logger::warning($alert, $metrics);
            }
        }
    }

    /**
     * Parse PHP memory limit to bytes
     */
    private static function parseMemoryLimit($limit) {
        $value = (int) $limit;
        
        switch (strtoupper(substr($limit, -1))) {
            case 'G':
                $value *= 1024;
            case 'M':
                $value *= 1024;
            case 'K':
                $value *= 1024;
        }
        
        return $value;
    }

    /**
     * Generate performance report
     */
    public static function generateReport($startDate = null, $endDate = null) {
        if (!$startDate) {
            $startDate = date('Y-m-d', strtotime('-7 days'));
        }
        if (!$endDate) {
            $endDate = date('Y-m-d');
        }

        $db = new Database();
        $db->query("SELECT 
                     DATE(created_at) as date,
                     AVG(duration_ms) as avg_duration,
                     MAX(duration_ms) as max_duration,
                     AVG(memory_usage) as avg_memory,
                     COUNT(*) as operation_count
                   FROM performance_logs
                   WHERE created_at BETWEEN :start AND :end
                   GROUP BY DATE(created_at)
                   ORDER BY date");
        
        $db->bind(':start', $startDate . ' 00:00:00');
        $db->bind(':end', $endDate . ' 23:59:59');
        
        return $db->resultSet();
    }
}
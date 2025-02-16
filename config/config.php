<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bsccl_maintenance');

// Application configuration
define('APP_ROOT', dirname(dirname(__FILE__)));
define('URL_ROOT', 'http://localhost/maintenance');
define('SITE_NAME', 'BSCCL Maintenance System');

// Upload directories
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('CIRCUIT_LIST_DIR', UPLOAD_DIR . '/circuit_lists');
define('MAINTENANCE_LIST_DIR', UPLOAD_DIR . '/maintenance_lists');

// Session configuration
define('SESSION_LIFETIME', 7200); // 2 hours
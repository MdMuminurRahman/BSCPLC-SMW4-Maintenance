<?php
/**
 * BSCCL Maintenance System Installation Script
 */

class Setup {
    private $requirements = [
        'php' => '7.4.0',
        'extensions' => [
            'pdo',
            'pdo_mysql',
            'json',
            'fileinfo',
            'zip'
        ],
        'writable_dirs' => [
            'uploads',
            'uploads/circuits',
            'uploads/maintenance',
            'logs'
        ]
    ];

    private $env_template = <<<EOT
APP_NAME=BSCCL Maintenance System
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=bsccl_maintenance
DB_USERNAME=root
DB_PASSWORD=

UPLOAD_MAX_SIZE=10
ALLOWED_EXTENSIONS=xlsx
MAX_EXECUTION_TIME=300

DEFAULT_TIMEZONE=UTC

LOG_LEVEL=error
LOG_PATH=../logs/
EOT;

    public function run() {
        $this->checkPHPVersion();
        $this->checkExtensions();
        $this->checkDirectories();
        $this->createEnvironmentFile();
        $this->initializeDatabase();
        $this->createDefaultUser();
        $this->displayCompletionMessage();
    }

    private function checkPHPVersion() {
        echo "Checking PHP version... ";
        if (version_compare(PHP_VERSION, $this->requirements['php'], '<')) {
            die("Error: PHP version {$this->requirements['php']} or higher is required. Current version: " . PHP_VERSION);
        }
        echo "OK (". PHP_VERSION .")\n";
    }

    private function checkExtensions() {
        echo "\nChecking PHP extensions...\n";
        foreach ($this->requirements['extensions'] as $ext) {
            echo "Checking $ext... ";
            if (!extension_loaded($ext)) {
                die("Error: PHP extension '$ext' is required but not installed.");
            }
            echo "OK\n";
        }
    }

    private function checkDirectories() {
        echo "\nChecking directory permissions...\n";
        foreach ($this->requirements['writable_dirs'] as $dir) {
            echo "Checking $dir... ";
            $path = __DIR__ . '/' . $dir;
            if (!file_exists($path)) {
                if (!mkdir($path, 0755, true)) {
                    die("Error: Unable to create directory '$dir'");
                }
            }
            if (!is_writable($path)) {
                die("Error: Directory '$dir' must be writable");
            }
            echo "OK\n";
        }
    }

    private function createEnvironmentFile() {
        echo "\nCreating environment file... ";
        if (!file_exists('.env')) {
            if (!file_put_contents('.env', $this->env_template)) {
                die("Error: Unable to create .env file");
            }
        }
        echo "OK\n";
    }

    private function initializeDatabase() {
        echo "\nInitializing database...\n";
        
        require_once 'config/config.php';
        
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST,
                DB_USER,
                DB_PASS
            );
            
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
            echo "Database created successfully\n";
            
            // Select database
            $pdo->exec("USE " . DB_NAME);
            
            // Import SQL schema
            $sql = file_get_contents('database/init.sql');
            $pdo->exec($sql);
            echo "Database schema imported successfully\n";
            
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }

    private function createDefaultUser() {
        echo "\nCreating default admin user... ";
        require_once 'app/models/User.php';
        require_once 'app/models/Database.php';
        
        $user = new User();
        $data = [
            'name' => 'Admin',
            'email' => 'admin@bsccl.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT)
        ];
        
        try {
            $user->createUser($data);
            echo "OK\n";
            echo "\nDefault admin credentials:\n";
            echo "Email: admin@bsccl.com\n";
            echo "Password: admin123\n";
        } catch (Exception $e) {
            echo "Warning: Default user may already exist\n";
        }
    }

    private function displayCompletionMessage() {
        echo "\n=================================\n";
        echo "Installation completed successfully\n";
        echo "=================================\n\n";
        echo "Please:\n";
        echo "1. Update the .env file with your database credentials\n";
        echo "2. Set up your web server to point to the 'public' directory\n";
        echo "3. Change the default admin password after first login\n";
        echo "4. Set appropriate file permissions\n";
        echo "\nThank you for installing BSCCL Maintenance System!\n";
    }
}

// Run installation
$setup = new Setup();
$setup->run();
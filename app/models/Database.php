<?php
namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Database {
    private $host;
    private $user;
    private $pass;
    private $dbname;
    private $dbh;
    private $stmt;
    private $error;

    public function __construct() {
        $this->host = Config::get('database.host');
        $this->user = Config::get('database.user');
        $this->pass = Config::get('database.pass');
        $this->dbname = Config::get('database.name');
        $this->connect();
    }

    private function connect() {
        try {
            if (!$this->dbh) {
                $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
                $options = array(
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                );

                $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
                Logger::info('Database connection established successfully');
            }
        } catch(PDOException $e) {
            Logger::error('Database connection failed: ' . $e->getMessage(), [
                'host' => $this->host,
                'database' => $this->dbname,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Database connection failed. Please check your configuration.');
        }
    }

    // Prepare statement with query
    public function query($sql) {
        try {
            $this->connect();
            $this->stmt = $this->dbh->prepare($sql);
            Logger::debug('SQL Query prepared: ' . $sql);
        } catch(PDOException $e) {
            Logger::error('Query preparation failed: ' . $e->getMessage(), [
                'query' => $sql,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to prepare database query.');
        }
    }

    // Bind values
    public function bind($param, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    // Execute the prepared statement
    public function execute() {
        try {
            $start = microtime(true);
            $result = $this->stmt->execute();
            $duration = microtime(true) - $start;
            
            Logger::debug('Query executed', [
                'duration' => round($duration * 1000, 2) . 'ms',
                'affected_rows' => $this->stmt->rowCount()
            ]);
            
            return $result;
        } catch(PDOException $e) {
            Logger::error('Query execution failed: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'query' => $this->stmt->queryString
            ]);
            throw new Exception('Database query execution failed.');
        }
    }

    // Get result set as array of objects
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Get single record as object
    public function single() {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    // Get row count
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    // Begin Transaction
    public function beginTransaction() {
        try {
            $this->connect();
            Logger::debug('Beginning database transaction');
            return $this->dbh->beginTransaction();
        } catch(PDOException $e) {
            Logger::error('Failed to begin transaction: ' . $e->getMessage());
            throw new Exception('Failed to begin database transaction.');
        }
    }

    // End Transaction
    public function endTransaction() {
        try {
            Logger::debug('Committing database transaction');
            return $this->dbh->commit();
        } catch(PDOException $e) {
            Logger::error('Failed to commit transaction: ' . $e->getMessage());
            throw new Exception('Failed to commit database transaction.');
        }
    }

    // Cancel Transaction
    public function cancelTransaction() {
        try {
            Logger::debug('Rolling back database transaction');
            return $this->dbh->rollBack();
        } catch(PDOException $e) {
            Logger::error('Failed to rollback transaction: ' . $e->getMessage());
            throw new Exception('Failed to rollback database transaction.');
        }
    }

    public function lastInsertId() {
        return $this->dbh->lastInsertId();
    }
}
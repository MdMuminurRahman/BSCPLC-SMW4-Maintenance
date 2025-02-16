<?php
namespace App\Models;

interface CacheInterface {
    public function get($key);
    public function set($key, $value, $ttl = 3600);
    public function delete($key);
    public function exists($key);
}

class SessionCache implements CacheInterface {
    public function get($key) {
        return $_SESSION['cache'][$key] ?? null;
    }

    public function set($key, $value, $ttl = 3600) {
        $_SESSION['cache'][$key] = $value;
        $_SESSION['cache_ttl'][$key] = time() + $ttl;
        return true;
    }

    public function delete($key) {
        unset($_SESSION['cache'][$key], $_SESSION['cache_ttl'][$key]);
        return true;
    }

    public function exists($key) {
        if (!isset($_SESSION['cache'][$key], $_SESSION['cache_ttl'][$key])) {
            return false;
        }
        if (time() > $_SESSION['cache_ttl'][$key]) {
            $this->delete($key);
            return false;
        }
        return true;
    }
}

class RedisCache implements CacheInterface {
    private $redis;
    private $prefix;

    public function __construct($host = '127.0.0.1', $port = 6379, $prefix = '') {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension not loaded');
        }
        
        try {
            // Use dynamic instantiation to avoid compile-time checking
            $redisClass = '\Redis';
            $this->redis = new $redisClass();
            
            if (!@$this->redis->connect($host, $port)) {
                throw new \RuntimeException('Could not connect to Redis server');
            }
            $this->prefix = $prefix;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Redis connection failed: ' . $e->getMessage());
        }
    }

    public function get($key) {
        try {
            return @$this->redis->get($this->prefix . $key);
        } catch (\Throwable $e) {
            Logger::error('Redis get failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function set($key, $value, $ttl = 3600) {
        try {
            return @$this->redis->setex($this->prefix . $key, $ttl, $value);
        } catch (\Throwable $e) {
            Logger::error('Redis set failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function delete($key) {
        try {
            return @$this->redis->del($this->prefix . $key);
        } catch (\Throwable $e) {
            Logger::error('Redis delete failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function exists($key) {
        try {
            return @$this->redis->exists($this->prefix . $key);
        } catch (\Throwable $e) {
            Logger::error('Redis exists check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
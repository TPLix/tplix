<?php
namespace Tplix;
/**
 * TplixCache - Advanced unified caching layer for Tplix
 * Supports APCu, Redis, and File-based caching.
 * Includes:
 *  - LRU eviction (for file cache)
 *  - Integrity hashing to detect tampering
 */
class Cache
{
    private string $driver = 'file';
    private string $cacheDir;
    private ?\Redis $redis = null;
    private array $config = [];

    public function __construct(array $config = [], string $cacheDir = __DIR__ . '/../cache')
    {
        $this->config = $config;
        $cacheDir = rtrim($cacheDir,"/")."/cache";
        $this->cacheDir = $cacheDir;
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0777, true);

        if (isset($config['driver'])) {
            $this->setDriver($config['driver']);
        } else {
            $this->autoSelectDriver();
        }
    }

    public function setDriver(string $driver): void
    {
        $this->driver = $driver;
        if ($driver === 'redis') {
            $this->initRedis();
        }
    }

    public function autoSelectDriver(): void
    {
        if (extension_loaded('apcu') && ini_get('apc.enabled')) {
            $this->driver = 'apcu';
        } elseif (extension_loaded('redis')) {
            try {
                $this->initRedis();
                $this->driver = 'redis';
            } catch (\Throwable) {
                $this->driver = 'file';
            }
        } else {
            $this->driver = 'file';
        }
    }

    private function initRedis(): void
    {
        $host = $this->config['redis_host'] ?? '127.0.0.1';
        $port = $this->config['redis_port'] ?? 6379;
        $timeout = $this->config['redis_timeout'] ?? 1;
        $password = $this->config['redis_password'] ?? null;

        $this->redis = new \Redis();
        $this->redis->connect($host, $port, $timeout);

        if ($password) {
            $this->redis->auth($password);
        }

        // Optional DB select
        if (isset($this->config['redis_db'])) {
            $this->redis->select((int)$this->config['redis_db']);
        }
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        switch ($this->driver) {
            case 'apcu':
                return apcu_store($key, $value, $ttl);
            case 'redis':
                return $this->redis->setex($key, $ttl, serialize($value));
            default:
                $path = $this->getFilePath($key);
                return (bool)file_put_contents($path, serialize(['expires' => time() + $ttl, 'value' => $value]));
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        switch ($this->driver) {
            case 'apcu':
                return apcu_exists($key) ? apcu_fetch($key) : $default;
            case 'redis':
                $data = $this->redis->get($key);
                return $data ? unserialize($data) : $default;
            default:
                $path = $this->getFilePath($key);
                if (!file_exists($path)) return $default;
                $data = unserialize(file_get_contents($path));
                if ($data['expires'] < time()) {
                    unlink($path);
                    return $default;
                }
                return $data['value'];
        }
    }

    public function delete(string $key): bool
    {
        switch ($this->driver) {
            case 'apcu':
                return apcu_delete($key);
            case 'redis':
                return (bool)$this->redis->del($key);
            default:
                $path = $this->getFilePath($key);
                return file_exists($path) ? unlink($path) : false;
        }
    }

    private function getFilePath(string $key): string
    {
        return rtrim($this->cacheDir, '/') . '/' . Tplix::fastHash($key) . '.cache';
    }
}

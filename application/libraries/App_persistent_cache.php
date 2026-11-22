<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * FennecDash Universal Persistent Cache Library
 *
 * Provides runtime capability detection and graceful fallback across all hosting environments:
 *   1. APCu (in-memory shared memory cache when extension is loaded and enabled)
 *   2. Redis (when extension is loaded and FENNEC_REDIS_HOST is configured)
 *   3. Memcached (when extension is loaded and FENNEC_MEMCACHED_HOST is configured)
 *   4. Safe Atomic File Cache (in application/cache/fennec_cache/ when writable)
 *   5. In-Memory Request Fallback (no-op persistence when filesystem is read-only)
 *
 * Security rule: Only non-sensitive, system-wide metadata (autoloaded options, module manifests,
 * directory file lists) may be stored in persistent cache. Never store session data, user permissions,
 * or private tenant records here.
 */
class App_persistent_cache
{
    /**
     * Singleton instance for static helper access
     * @var App_persistent_cache|null
     */
    private static $instance = null;

    /**
     * Active driver: 'apcu', 'redis', 'memcached', 'file', or 'memory'
     * @var string
     */
    private $driver = 'memory';

    /**
     * Key prefix isolating this FennecDash installation
     * @var string
     */
    private $prefix = 'fd54_';

    /**
     * Directory for file-based cache fallback
     * @var string
     */
    private $cacheDir = '';

    /**
     * Per-request L1 memory cache to avoid repeated deserialization or I/O within the same request
     * @var array
     */
    private $localCache = [];

    /**
     * Optional Redis client instance
     * @var object|null
     */
    private $redis = null;

    /**
     * Optional Memcached client instance
     * @var object|null
     */
    private $memcached = null;

    public function __construct()
    {
        $installHash    = substr(md5(defined('FCPATH') ? FCPATH : __DIR__), 0, 8);
        $this->prefix   = 'fd54_' . $installHash . '_';
        $this->cacheDir = (defined('APPPATH') ? APPPATH : dirname(__DIR__) . DIRECTORY_SEPARATOR) . 'cache' . DIRECTORY_SEPARATOR . 'fennec_cache' . DIRECTORY_SEPARATOR;

        $this->detectDriver();
        self::$instance = $this;
    }

    /**
     * Return singleton instance
     * @return App_persistent_cache
     */
    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Detect best available cache driver at runtime with graceful fallback
     * @return void
     */
    private function detectDriver()
    {
        if (defined('FENNEC_CACHE_DISABLE') && FENNEC_CACHE_DISABLE) {
            $this->driver = 'memory';
            return;
        }

        $preferred = defined('FENNEC_CACHE_DRIVER') ? strtolower((string) FENNEC_CACHE_DRIVER) : 'auto';

        // 1. Try Redis if explicitly configured or auto with FENNEC_REDIS_HOST
        if (($preferred === 'redis' || $preferred === 'auto') && defined('FENNEC_REDIS_HOST') && extension_loaded('redis') && class_exists('Redis', false)) {
            try {
                $redis = new Redis();
                $host  = (string) FENNEC_REDIS_HOST;
                $port  = defined('FENNEC_REDIS_PORT') ? (int) FENNEC_REDIS_PORT : 6379;
                $timeout = defined('FENNEC_REDIS_TIMEOUT') ? (float) FENNEC_REDIS_TIMEOUT : 0.5;
                if (@$redis->connect($host, $port, $timeout)) {
                    if (defined('FENNEC_REDIS_PASSWORD') && FENNEC_REDIS_PASSWORD !== '') {
                        @$redis->auth((string) FENNEC_REDIS_PASSWORD);
                    }
                    if (defined('FENNEC_REDIS_DB')) {
                        @$redis->select((int) FENNEC_REDIS_DB);
                    }
                    $this->redis  = $redis;
                    $this->driver = 'redis';
                    return;
                }
            } catch ( Throwable $e ) {
                // Degrade gracefully
            }
        }

        // 2. Try Memcached if explicitly configured or auto with FENNEC_MEMCACHED_HOST
        if (($preferred === 'memcached' || $preferred === 'auto') && defined('FENNEC_MEMCACHED_HOST') && extension_loaded('memcached') && class_exists('Memcached', false)) {
            try {
                $mc   = new Memcached();
                $host = (string) FENNEC_MEMCACHED_HOST;
                $port = defined('FENNEC_MEMCACHED_PORT') ? (int) FENNEC_MEMCACHED_PORT : 11211;
                $mc->addServer($host, $port);
                $stats = @$mc->getStats();
                if (is_array($stats) && !empty($stats)) {
                    $this->memcached = $mc;
                    $this->driver    = 'memcached';
                    return;
                }
            } catch ( Throwable $e ) {
                // Degrade gracefully
            }
        }

        // 3. Try APCu if extension is loaded and enabled for current SAPI
        if ($preferred === 'apcu' || $preferred === 'auto') {
            if (extension_loaded('apcu') && function_exists('apcu_enabled') && @apcu_enabled()) {
                $this->driver = 'apcu';
                return;
            }
        }

        // 4. Safe Atomic File Cache fallback
        if ($preferred === 'file' || $preferred === 'auto' || $preferred === 'apcu' || $preferred === 'redis' || $preferred === 'memcached') {
            if ($this->ensureCacheDirectory()) {
                $this->driver = 'file';
                return;
            }
        }

        // 5. Ultimate fallback: in-memory per-request array
        $this->driver = 'memory';
    }

    /**
     * Ensure file cache directory exists, is writable, and is protected against direct web access
     * @return bool
     */
    private function ensureCacheDirectory()
    {
        if (!is_dir($this->cacheDir)) {
            if (!@mkdir($this->cacheDir, 0755, true) && !is_dir($this->cacheDir)) {
                return false;
            }
        }

        if (!is_writable($this->cacheDir)) {
            return false;
        }

        $htaccess = $this->cacheDir . '.htaccess';
        if (!is_file($htaccess)) {
            $rules = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";
            @file_put_contents($htaccess, $rules);
        }

        $indexHtml = $this->cacheDir . 'index.html';
        if (!is_file($indexHtml)) {
            @file_put_contents($indexHtml, "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>\n");
        }

        return true;
    }

    /**
     * Return active driver name
     * @return string
     */
    public function get_driver()
    {
        return $this->driver;
    }

    /**
     * Normalize key name
     * @param string $key
     * @return string
     */
    private function normalizeKey($key)
    {
        return $this->prefix . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', (string) $key);
    }

    /**
     * Retrieve an item from cache
     *
     * @param string $key
     * @param bool   &$found Set to true if key was found and valid
     * @return mixed
     */
    public function get($key, &$found = false)
    {
        $normKey = $this->normalizeKey($key);

        if (array_key_exists($normKey, $this->localCache)) {
            $entry = $this->localCache[$normKey];
            if (is_array($entry) && isset($entry['expires_at']) && array_key_exists('data', $entry)) {
                if ($entry['expires_at'] === 0 || $entry['expires_at'] >= time()) {
                    $found = true;
                    return $entry['data'];
                }
                unset($this->localCache[$normKey]);
            }
        }

        $found = false;

        if ($this->driver === 'apcu') {
            $success = false;
            $val     = @apcu_fetch($normKey, $success);
            if ($success) {
                $found                      = true;
                $this->localCache[$normKey] = ['expires_at' => time() + 3600, 'data' => $val];
                return $val;
            }
            return null;
        }

        if ($this->driver === 'redis' && $this->redis) {
            try {
                $raw = @$this->redis->get($normKey);
                if ($raw !== false && is_string($raw)) {
                    $val                        = @unserialize($raw, ['allowed_classes' => ['stdClass']]);
                    $found                      = true;
                    $this->localCache[$normKey] = ['expires_at' => time() + 3600, 'data' => $val];
                    return $val;
                }
            } catch ( Throwable $e ) {
                return null;
            }
            return null;
        }

        if ($this->driver === 'memcached' && $this->memcached) {
            try {
                $val = @$this->memcached->get($normKey);
                if ($this->memcached->getResultCode() === Memcached::RES_SUCCESS) {
                    $found                      = true;
                    $this->localCache[$normKey] = ['expires_at' => time() + 3600, 'data' => $val];
                    return $val;
                }
            } catch ( Throwable $e ) {
                return null;
            }
            return null;
        }

        if ($this->driver === 'file') {
            $filePath = $this->cacheDir . $normKey . '.cache';
            if (!is_file($filePath)) {
                return null;
            }

            $raw = @file_get_contents($filePath);
            if ($raw === false || $raw === '') {
                return null;
            }

            $payload = @unserialize($raw, ['allowed_classes' => ['stdClass']]);
            if (!is_array($payload) || !isset($payload['expires_at']) || !array_key_exists('data', $payload)) {
                @unlink($filePath);
                return null;
            }

            if ($payload['expires_at'] > 0 && $payload['expires_at'] < time()) {
                @unlink($filePath);
                return null;
            }

            $found                      = true;
            $this->localCache[$normKey] = ['expires_at' => (int) $payload['expires_at'], 'data' => $payload['data']];
            return $payload['data'];
        }

        return null;
    }

    /**
     * Store an item in cache
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl Time-to-live in seconds (default 3600)
     * @return bool
     */
    public function set($key, $value, $ttl = 3600)
    {
        $normKey = $this->normalizeKey($key);
        $ttl     = max(1, (int) $ttl);
        $this->localCache[$normKey] = [
            'expires_at' => time() + $ttl,
            'data'       => $value,
        ];

        if ($this->driver === 'apcu') {
            return (bool) @apcu_store($normKey, $value, $ttl);
        }

        if ($this->driver === 'redis' && $this->redis) {
            try {
                return (bool) @$this->redis->setex($normKey, $ttl, serialize($value));
            } catch ( Throwable $e ) {
                return false;
            }
        }

        if ($this->driver === 'memcached' && $this->memcached) {
            try {
                return (bool) @$this->memcached->set($normKey, $value, $ttl);
            } catch ( Throwable $e ) {
                return false;
            }
        }

        if ($this->driver === 'file') {
            $filePath = $this->cacheDir . $normKey . '.cache';
            $payload  = [
                'expires_at' => time() + $ttl,
                'created_at' => time(),
                'data'       => $value,
            ];
            $serialized = serialize($payload);
            $tmpFile    = $this->cacheDir . $normKey . '.' . uniqid('', true) . '.tmp';

            if (@file_put_contents($tmpFile, $serialized, LOCK_EX) !== false) {
                if (@rename($tmpFile, $filePath)) {
                    return true;
                }
                // Windows fallback if target exists and rename fails under concurrency
                @unlink($filePath);
                if (@rename($tmpFile, $filePath)) {
                    return true;
                }
                @unlink($tmpFile);
            }
            return false;
        }

        return true;
    }

    /**
     * Delete a specific key from cache
     *
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $normKey = $this->normalizeKey($key);
        unset($this->localCache[$normKey]);

        if ($this->driver === 'apcu') {
            return (bool) @apcu_delete($normKey);
        }

        if ($this->driver === 'redis' && $this->redis) {
            try {
                return (bool) @$this->redis->del($normKey);
            } catch ( Throwable $e ) {
                return false;
            }
        }

        if ($this->driver === 'memcached' && $this->memcached) {
            try {
                return (bool) @$this->memcached->delete($normKey);
            } catch ( Throwable $e ) {
                return false;
            }
        }

        if ($this->driver === 'file') {
            $filePath = $this->cacheDir . $normKey . '.cache';
            if (is_file($filePath)) {
                return (bool) @unlink($filePath);
            }
            return true;
        }

        return true;
    }

    /**
     * Flush all FennecDash persistent cache entries (or entries matching prefix)
     *
     * @param string $keyPrefix Optional prefix filter
     * @return bool
     */
    public function flush($keyPrefix = '')
    {
        $fullPrefix = $this->normalizeKey($keyPrefix);

        if ($keyPrefix === '') {
            $this->localCache = [];
        } else {
            foreach (array_keys($this->localCache) as $k) {
                if (strpos($k, $fullPrefix) === 0) {
                    unset($this->localCache[$k]);
                }
            }
        }

        if ($this->driver === 'apcu' && function_exists('apcu_clear_cache')) {
            @apcu_clear_cache();
            return true;
        }

        if ($this->driver === 'file' && is_dir($this->cacheDir)) {
            $pattern = $this->cacheDir . ($keyPrefix !== '' ? $fullPrefix . '*.cache' : $this->prefix . '*.cache');
            $files   = @glob($pattern);
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
            return true;
        }

        return true;
    }

    /**
     * Return diagnostic report of runtime hosting capabilities
     *
     * @return array
     */
    public function get_capabilities_report()
    {
        $opcacheStatus = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
        return [
            'active_driver'      => $this->driver,
            'php_version'        => PHP_VERSION,
            'sapi'               => PHP_SAPI,
            'opcache_enabled'    => is_array($opcacheStatus) && !empty($opcacheStatus['opcache_enabled']),
            'apcu_available'     => extension_loaded('apcu') && function_exists('apcu_enabled') && @apcu_enabled(),
            'redis_extension'    => extension_loaded('redis'),
            'memcached_ext'      => extension_loaded('memcached'),
            'file_cache_writable'=> is_dir($this->cacheDir) && is_writable($this->cacheDir),
            'zlib_enabled'       => extension_loaded('zlib'),
            'mbstring_enabled'   => extension_loaded('mbstring'),
        ];
    }
}

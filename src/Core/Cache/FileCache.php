<?php

namespace App\Core\Cache;

use App\Core\DebugCollector;

class FileCache implements CacheInterface {
    private string $cachePath;

    public function __construct(string $cachePath) {
        $this->cachePath = rtrim($cachePath, '/') . '/';
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed {
        $file = $this->getFileName($key);
        if (!file_exists($file)) {
            DebugCollector::getInstance()->logCache('get', $key, false);
            return $default;
        }

        $content = file_get_contents($file);
        $data = unserialize($content);

        if ($data['expires'] !== 0 && time() > $data['expires']) {
            $this->delete($key);
            DebugCollector::getInstance()->logCache('get', $key, false);
            return $default;
        }

        DebugCollector::getInstance()->logCache('get', $key, true);
        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool {
        $file = $this->getFileName($key);
        $data = [
            'expires' => $ttl === 0 ? 0 : time() + $ttl,
            'value'   => $value
        ];

        $success = file_put_contents($file, serialize($data)) !== false;
        DebugCollector::getInstance()->logCache('set', $key, $success);
        return $success;
    }

    public function delete(string $key): bool {
        $file = $this->getFileName($key);
        if (file_exists($file)) {
            $success = unlink($file);
            DebugCollector::getInstance()->logCache('delete', $key, $success);
            return $success;
        }
        DebugCollector::getInstance()->logCache('delete', $key, true);
        return true;
    }

    public function clear(): bool {
        $files = glob($this->cachePath . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    public function has(string $key): bool {
        return $this->get($key, $this) !== $this;
    }

    private function getFileName(string $key): string {
        return $this->cachePath . md5($key) . '.cache';
    }
}

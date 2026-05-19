<?php

namespace Tests;

use App\Core\Cache\CacheInterface;

class NullCache implements CacheInterface {
    public function get(string $key, mixed $default = null): mixed { return $default; }
    public function set(string $key, mixed $value, int $ttl = 3600): bool { return true; }
    public function delete(string $key): bool { return true; }
    public function clear(): bool { return true; }
    public function has(string $key): bool { return false; }
}

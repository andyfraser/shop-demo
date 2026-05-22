<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Commands\CacheClearCommand;
use App\Core\Cache\CacheInterface;

class CacheClearCommandTest extends TestCase {
    public function testExecuteSuccessful() {
        $cache = new class implements CacheInterface {
            public bool $cleared = false;
            public function get(string $key, mixed $default = null): mixed { return null; }
            public function set(string $key, mixed $value, int $ttl = 3600): bool { return true; }
            public function delete(string $key): bool { return true; }
            public function clear(): bool { $this->cleared = true; return true; }
            public function has(string $key): bool { return false; }
        };

        $command = new CacheClearCommand($cache);
        
        ob_start();
        $exitCode = $command->execute();
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Cache cleared successfully', $output);
        $this->assertTrue($cache->cleared);
    }

    public function testExecuteFailure() {
        $cache = new class implements CacheInterface {
            public function get(string $key, mixed $default = null): mixed { return null; }
            public function set(string $key, mixed $value, int $ttl = 3600): bool { return true; }
            public function delete(string $key): bool { return true; }
            public function clear(): bool { return false; }
            public function has(string $key): bool { return false; }
        };

        $command = new CacheClearCommand($cache);
        
        ob_start();
        $exitCode = $command->execute();
        $output = ob_get_clean();
        
        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('Failed to clear cache', $output);
    }
}

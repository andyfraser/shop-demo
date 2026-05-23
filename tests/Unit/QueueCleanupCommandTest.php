<?php

namespace Tests\Unit;

use App\Commands\QueueCleanupCommand;
use App\Repositories\JobRepositoryInterface;
use App\Services\SettingsServiceInterface;
use App\Models\Settings;
use Tests\TestCase;
use Tests\NullLogger;

class QueueCleanupCommandTest extends TestCase {
    public function testExecuteDeletesJobsBasedOnSettings() {
        $repo = new class implements JobRepositoryInterface {
            public $calls = [];
            public function create(array $data): int { return 0; }
            public function findPending(int $limit = 10): array { return []; }
            public function update(int $id, array $data): bool { return true; }
            public function claim(int $id, string $startedAt, int $attempts): bool { return true; }
            public function deleteByStatusAndAge(string $status, int $hours): int {
                $this->calls[] = ['status' => $status, 'hours' => $hours];
                return $status === 'completed' ? 5 : 2;
            }
        };

        $logger = new NullLogger();

        $settingsService = new class($logger) implements SettingsServiceInterface {
            public function __construct(private $logger) {}
            public function getSettings(): Settings {
                $s = new Settings($this->logger);
                $s->queue_cleanup_completed_hours = 12;
                $s->queue_cleanup_failed_days = 3;
                return $s;
            }
            public function get(string $key): mixed { return null; }
            public function all(): array { return []; }
            public function set(string $key, mixed $value): void {}
        };

        $command = new QueueCleanupCommand($repo, $settingsService, $logger);
        
        // Suppress echo output during test
        ob_start();
        $exitCode = $command->execute();
        $output = ob_get_clean();

        $this->assertEquals(0, $exitCode);
        $this->assertCount(2, $repo->calls);
        
        $this->assertEquals('completed', $repo->calls[0]['status']);
        $this->assertEquals(12, $repo->calls[0]['hours']);
        
        $this->assertEquals('failed', $repo->calls[1]['status']);
        $this->assertEquals(72, $repo->calls[1]['hours']); // 3 days * 24
        
        $this->assertStringContainsString('Deleted 5 completed jobs', $output);
        $this->assertStringContainsString('Deleted 2 failed jobs', $output);
    }
}

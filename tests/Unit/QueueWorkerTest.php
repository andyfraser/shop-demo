<?php

namespace Tests\Unit;

use App\Commands\QueueWorkCommand;
use App\Core\Container;
use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Core\Events\ShouldQueue;
use App\Repositories\JobRepositoryInterface;
use Tests\TestCase;

class QueueWorkerTest extends TestCase {
    public function testFailingJobUsesPendingStatusForRetry() {
        $job = [
            'id' => 1,
            'handler_class' => FailingJobForTest::class,
            'payload' => serialize(new TestEventForQueue()),
            'attempts' => 0
        ];

        $repo = new class($job) implements JobRepositoryInterface {
            public $updatedData = null;
            public function __construct(private array $job) {}
            public function create(array $data): int { return 0; }
            public function findPending(int $limit = 10): array { return [$this->job]; }
            public function update(int $id, array $data): bool {
                if ($data['status'] !== 'running') {
                    $this->updatedData = $data;
                }
                return true;
            }
            public function claim(int $id, string $startedAt, int $attempts): bool {
                return true;
            }
            public function deleteByStatusAndAge(string $status, int $hours): int { return 0; }
        };

        $container = new Container();
        $command = new QueueWorkCommand($repo, $container);
        
        ob_start();
        $command->execute();
        ob_end_clean();

        $this->assertEquals('pending', $repo->updatedData['status']);
        $this->assertNotNull($repo->updatedData['available_at']);
        $this->assertGreaterThan(date('Y-m-d H:i:s'), $repo->updatedData['available_at']);
    }

    public function testFailingJobUsesFailedStatusWhenMaxTriesReached() {
        $job = [
            'id' => 1,
            'handler_class' => FailingJobForTest::class,
            'payload' => serialize(new TestEventForQueue()),
            'attempts' => 2 // 3rd attempt
        ];

        $repo = new class($job) implements JobRepositoryInterface {
            public $updatedData = null;
            public function __construct(private array $job) {}
            public function create(array $data): int { return 0; }
            public function findPending(int $limit = 10): array { return [$this->job]; }
            public function update(int $id, array $data): bool {
                if ($data['status'] !== 'running') {
                    $this->updatedData = $data;
                }
                return true;
            }
            public function claim(int $id, string $startedAt, int $attempts): bool {
                return true;
            }
            public function deleteByStatusAndAge(string $status, int $hours): int { return 0; }
        };

        $container = new Container();
        $command = new QueueWorkCommand($repo, $container);
        
        ob_start();
        $command->execute();
        ob_end_clean();

        $this->assertEquals('failed', $repo->updatedData['status']);
        // available_at should be "now"
        $this->assertNotNull($repo->updatedData['available_at']);
    }

    public function testJobSkippedIfClaimFails() {
        $job = [
            'id' => 1,
            'handler_class' => FailingJobForTest::class,
            'payload' => serialize(new TestEventForQueue()),
            'attempts' => 0
        ];

        $repo = new class($job) implements JobRepositoryInterface {
            public $updatedData = null;
            public function __construct(private array $job) {}
            public function create(array $data): int { return 0; }
            public function findPending(int $limit = 10): array { return [$this->job]; }
            public function update(int $id, array $data): bool {
                $this->updatedData = $data;
                return true;
            }
            public function claim(int $id, string $startedAt, int $attempts): bool {
                return false; // Simulation of failed claim (another worker grabbed it)
            }
            public function deleteByStatusAndAge(string $status, int $hours): int { return 0; }
        };

        $container = new Container();
        $command = new QueueWorkCommand($repo, $container);
        
        ob_start();
        $command->execute();
        $output = ob_get_clean();

        // The job should be skipped and not updated or completed/failed by this worker
        $this->assertNull($repo->updatedData);
        $this->assertStringContainsString('Already processed or claimed by another worker. Skipping.', $output);
    }
}

class TestEventForQueue extends Event {}

class FailingJobForTest implements ListenerInterface, ShouldQueue {
    public function handle(Event $event): void {
        throw new \Exception("Fail!");
    }
    public function getTries(): int { return 3; }
    public function getRetryDelay(): int { return 10; }
    public function useExponentialBackoff(): bool { return false; }
}

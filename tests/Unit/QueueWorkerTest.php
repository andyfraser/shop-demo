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
        };

        $container = new Container();
        $command = new QueueWorkCommand($repo, $container);
        
        $command->execute();

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
        };

        $container = new Container();
        $command = new QueueWorkCommand($repo, $container);
        
        $command->execute();

        $this->assertEquals('failed', $repo->updatedData['status']);
        // available_at should be "now"
        $this->assertNotNull($repo->updatedData['available_at']);
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

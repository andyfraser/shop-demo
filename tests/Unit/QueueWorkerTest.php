<?php

namespace Tests\Unit {

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
            private bool $served = false;
            public function claimNextPending(string $startedAt): ?array {
                if ($this->served) return null;
                $this->served = true;
                $this->job['attempts']++;
                return $this->job;
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
            private bool $served = false;
            public function claimNextPending(string $startedAt): ?array {
                if ($this->served) return null;
                $this->served = true;
                $this->job['attempts']++;
                return $this->job;
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
                return false;
            }
            public function claimNextPending(string $startedAt): ?array {
                return null; // Simulation of failed claim (another worker grabbed it)
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
    }

    public function testCorruptPayloadIsMarkedAsFailed() {
        $job = [
            'id' => 2,
            'handler_class' => FailingJobForTest::class,
            'payload' => 'corrupted_serialization_string_data',
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
            public function claim(int $id, string $startedAt, int $attempts): bool { return true; }
            private bool $served = false;
            public function claimNextPending(string $startedAt): ?array {
                if ($this->served) return null;
                $this->served = true;
                $this->job['attempts']++;
                return $this->job;
            }
            public function deleteByStatusAndAge(string $status, int $hours): int { return 0; }
        };

        $container = new Container();
        $command = new QueueWorkCommand($repo, $container);

        ob_start();
        $command->execute();
        ob_end_clean();

        // Since it's a corrupt payload, it should fail immediately (using default maxTries=1 because resolving might be skipped/fail or we just fail)
        $this->assertEquals('failed', $repo->updatedData['status']);
        $this->assertStringContainsString('Corrupt job payload', $repo->updatedData['error']);
    }

    public function testMissingHandlerClassIsMarkedAsFailed() {
        $job = [
            'id' => 3,
            'handler_class' => 'NonExistentHandlerClassForTestingQueueWorker',
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
            public function claim(int $id, string $startedAt, int $attempts): bool { return true; }
            private bool $served = false;
            public function claimNextPending(string $startedAt): ?array {
                if ($this->served) return null;
                $this->served = true;
                $this->job['attempts']++;
                return $this->job;
            }
            public function deleteByStatusAndAge(string $status, int $hours): int { return 0; }
        };

        $container = new Container();
        $command = new QueueWorkCommand($repo, $container);

        ob_start();
        $command->execute();
        ob_end_clean();

        // Should mark the job as failed with default settings rather than throwing exception out of loop
        $this->assertEquals('failed', $repo->updatedData['status']);
        $this->assertStringContainsString('does not exist', $repo->updatedData['error']);
    }

    public function testClaimNextPendingConcreteRepository() {
        $db = \App\Core\Database::getConnection();
        $db->exec("DELETE FROM jobs");
        
        $repo = new \App\Repositories\JobRepository($db);
        
        $jobId = $repo->create([
            'handler_class' => FailingJobForTest::class,
            'payload' => serialize(new TestEventForQueue()),
            'status' => 'pending',
            'attempts' => 0
        ]);
        
        $this->assertGreaterThan(0, $jobId);
        
        $claimedJob = $repo->claimNextPending(date('Y-m-d H:i:s'));
        $this->assertNotNull($claimedJob);
        $this->assertEquals($jobId, $claimedJob['id']);
        $this->assertEquals('running', $claimedJob['status']);
        $this->assertEquals(1, $claimedJob['attempts']);
        
        // Trying to claim again should return null
        $secondClaim = $repo->claimNextPending(date('Y-m-d H:i:s'));
        $this->assertNull($secondClaim);
    }

    public function testWorkerTimeoutExitsLoop() {
        $job = [
            'id' => 99,
            'handler_class' => FailingJobForTest::class,
            'payload' => serialize(new TestEventForQueue()),
            'attempts' => 0
        ];

        $repo = new class($job) implements JobRepositoryInterface {
            public int $claimedCount = 0;
            public function __construct(private array $job) {}
            public function create(array $data): int { return 0; }
            public function findPending(int $limit = 10): array { return [$this->job]; }
            public function update(int $id, array $data): bool { return true; }
            public function claim(int $id, string $startedAt, int $attempts): bool { return true; }
            public function claimNextPending(string $startedAt): ?array {
                $this->claimedCount++;
                return $this->job;
            }
            public function deleteByStatusAndAge(string $status, int $hours): int { return 0; }
        };

        // Enable mock time starting at 1000
        $GLOBALS['mock_time'] = 1000;

        try {
            $container = new Container();
            $command = new QueueWorkCommand($repo, $container);

            ob_start();
            $command->execute();
            $output = ob_get_clean();

            // The loop should terminate after the first job execution because time advances by 55 seconds
            $this->assertEquals(1, $repo->claimedCount);
            $this->assertStringContainsString('Worker timeout threshold reached. Exiting worker loop.', $output);
        } finally {
            unset($GLOBALS['mock_time']);
        }
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
}

namespace App\Commands {
    function time() {
        if (isset($GLOBALS['mock_time'])) {
            $val = $GLOBALS['mock_time'];
            $GLOBALS['mock_time'] += 30;
            return $val;
        }
        return \time();
    }
}

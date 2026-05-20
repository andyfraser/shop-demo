<?php

namespace App\Commands;

use App\Repositories\JobRepositoryInterface;
use App\Services\SettingsServiceInterface;
use Psr\Log\LoggerInterface;

class QueueCleanupCommand implements CommandInterface {
    public function __construct(
        private JobRepositoryInterface $jobRepository,
        private SettingsServiceInterface $settingsService,
        private ?LoggerInterface $logger = null
    ) {}

    public function getName(): string {
        return 'queue:cleanup';
    }

    public function getDescription(): string {
        return 'Cleans up completed and failed jobs from the queue.';
    }

    public function getSchedule(): ?string {
        return 'daily';
    }

    public function execute(): int {
        $settings = $this->settingsService->getSettings();
        
        $completedHours = $settings->queue_cleanup_completed_hours;
        $failedDays = $settings->queue_cleanup_failed_days;
        $failedHours = $failedDays * 24;

        echo "Cleaning up completed jobs older than {$completedHours} hours...\n";
        $completedDeleted = $this->jobRepository->deleteByStatusAndAge('completed', $completedHours);
        echo "Deleted {$completedDeleted} completed jobs.\n";

        echo "Cleaning up failed jobs older than {$failedDays} days...\n";
        $failedDeleted = $this->jobRepository->deleteByStatusAndAge('failed', $failedHours);
        echo "Deleted {$failedDeleted} failed jobs.\n";

        if ($this->logger) {
            $this->logger->info("Queue cleanup finished. Deleted {completed} completed and {failed} failed jobs.", [
                'completed' => $completedDeleted,
                'failed' => $failedDeleted
            ]);
        }

        return 0;
    }
}

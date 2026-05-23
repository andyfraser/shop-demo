<?php

namespace App\Commands;

use App\Core\Container;
use App\Repositories\JobRepositoryInterface;
use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use Psr\Log\LoggerInterface;

class QueueWorkCommand implements CommandInterface {
    public function __construct(
        private JobRepositoryInterface $jobRepository,
        private Container $container,
        private ?LoggerInterface $logger = null
    ) {}

    public function getName(): string {
        return 'queue:work';
    }

    public function getDescription(): string {
        return 'Processes pending background jobs.';
    }

    public function getSchedule(): ?string {
        return 'everyMinute';
    }

    public function execute(): int {
        $lockDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        $lockFilePath = $lockDir . '/queue_work.lock';
        
        $lockFile = @fopen($lockFilePath, 'c');
        if (!$lockFile) {
            echo "Error: Could not open or create lock file at {$lockFilePath}\n";
            return 1;
        }

        $acquired = flock($lockFile, LOCK_EX | LOCK_NB);
        if (!$acquired) {
            echo "Another instance of queue:work is already running. Exiting.\n";
            if ($this->logger) {
                $this->logger->info("Another queue:work instance is active. Exiting early.");
            }
            fclose($lockFile);
            return 0;
        }

        try {
            $jobs = $this->jobRepository->findPending(10);
            $count = count($jobs);

            if ($count === 0) {
                return 0;
            }

            echo "Processing {$count} jobs...\n";

            foreach ($jobs as $job) {
                $this->processJob($job);
            }
        } finally {
            flock($lockFile, LOCK_UN);
            fclose($lockFile);
        }

        return 0;
    }

    private function processJob(array $job): void {
        $id = $job['id'];
        $handlerClass = $job['handler_class'];
        
        echo "Processing job #{$id} ({$handlerClass})... ";

        try {
            $claimed = $this->jobRepository->claim(
                $id,
                date('Y-m-d H:i:s'),
                $job['attempts'] + 1
            );

            if (!$claimed) {
                echo "Already processed or claimed by another worker. Skipping.\n";
                return;
            }

            /** @var Event $event */
            $event = unserialize($job['payload']);
            
            /** @var ListenerInterface|ShouldQueue $handler */
            $handler = $this->container->get($handlerClass);
            
            $handler->handle($event);

            $this->jobRepository->update($id, [
                'status' => 'completed',
                'finished_at' => date('Y-m-d H:i:s')
            ]);

            echo "Done.\n";
        } catch (\Throwable $e) {
            echo "Failed!\n";
            
            $maxTries = 1;
            $delayMinutes = 0;
            $useBackoff = false;

            // Resolve handler settings if it implements ShouldQueue (it should)
            if (is_subclass_of($handlerClass, \App\Core\Events\ShouldQueue::class)) {
                $handler = $this->container->get($handlerClass);
                $maxTries = $handler->getTries();
                $delayMinutes = $handler->getRetryDelay();
                $useBackoff = $handler->useExponentialBackoff();
            }

            $attempts = $job['attempts'] + 1;
            $canRetry = $attempts < $maxTries;

            $updateData = [
                'status' => $canRetry ? 'pending' : 'failed',
                'error' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                'finished_at' => date('Y-m-d H:i:s'),
                'attempts' => $attempts,
                'available_at' => date('Y-m-d H:i:s') // Default to now if not retrying
            ];

            if ($canRetry) {
                $delay = $delayMinutes;
                if ($useBackoff && $attempts > 1) {
                    $delay = $delayMinutes * pow(2, $attempts - 1);
                }
                
                $updateData['available_at'] = date('Y-m-d H:i:s', strtotime("+{$delay} minutes"));
                echo "Scheduled retry in {$delay} minutes (Attempt {$attempts}/{$maxTries}).\n";
            } else {
                echo "Max attempts reached ({$maxTries}).\n";
            }

            try {
                $this->jobRepository->update($id, $updateData);
            } catch (\Throwable $te) {
                echo "Error updating job status: " . $te->getMessage() . "\n";
            }

            if ($this->logger) {
                $this->logger->error("Job #{$id} failed: {message}. Retry scheduled: {retry}", [
                    'id' => $id,
                    'handler' => $handlerClass,
                    'message' => $e->getMessage(),
                    'retry' => $canRetry ? 'yes' : 'no',
                    'exception' => $e
                ]);
            }
        }
    }
}

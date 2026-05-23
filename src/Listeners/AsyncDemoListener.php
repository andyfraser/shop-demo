<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Core\Events\ShouldQueue;
use Psr\Log\LoggerInterface;

/**
 * A permanent listener to demonstrate and test asynchronous background processing.
 */
class AsyncDemoListener implements ListenerInterface, ShouldQueue {
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        $this->logger->info("AsyncDemoListener: Beginning 10-second simulation...");
        
        // Simulate a heavy task (e.g., third-party API sync)
        sleep(100);
        //throw new \Exception('Test exception from async listener');
        
        $this->logger->info("AsyncDemoListener: Simulation complete.");
    }

    public function getTries(): int {
        return 3;
    }

    public function getRetryDelay(): int {
        return 1;
    }

    public function useExponentialBackoff(): bool {
        return true;
    }
}

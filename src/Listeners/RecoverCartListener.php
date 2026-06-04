<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Core\Events\ShouldQueue;
use App\Events\AbandonCartDetected;
use App\Services\EmailServiceInterface;
use Psr\Log\LoggerInterface;

class RecoverCartListener implements ListenerInterface, ShouldQueue {
    public function __construct(
        private EmailServiceInterface $emailService,
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        if ($event instanceof AbandonCartDetected) {
            $this->logger->info("RecoverCartListener: Processing cart recovery email for {$event->email}...");
            
            $success = $this->emailService->sendAbandonedCartEmail($event->email, $event->name);
            
            if (!$success) {
                throw new \Exception("Failed to send abandoned cart email to {$event->email}");
            }
            
            $this->logger->info("RecoverCartListener: Successfully sent recovery email to {$event->email}.");
        }
    }

    public function getTries(): int {
        return 3;
    }

    public function getRetryDelay(): int {
        return 5;
    }

    public function useExponentialBackoff(): bool {
        return true;
    }
}

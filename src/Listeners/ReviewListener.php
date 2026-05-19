<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\ReviewSubmitted;
use App\Events\ReviewApproved;
use Psr\Log\LoggerInterface;

class ReviewListener implements ListenerInterface {
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        if ($event instanceof ReviewSubmitted) {
            $this->logger->info("New review submitted and pending approval", [
                'product_id' => $event->reviewData['product_id'] ?? null,
                'rating' => $event->reviewData['rating'] ?? null
            ]);
        } elseif ($event instanceof ReviewApproved) {
            $this->logger->info("Review approved", [
                'review_id' => $event->reviewId
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Core\Events\Event;
use App\Repositories\JobRepositoryInterface;

class QueueService implements QueueServiceInterface {
    public function __construct(
        private JobRepositoryInterface $jobRepository
    ) {}

    public function push(string $handlerClass, Event $event): int {
        return $this->jobRepository->create([
            'handler_class' => $handlerClass,
            'payload' => serialize($event),
            'status' => 'pending'
        ]);
    }
}

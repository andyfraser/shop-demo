<?php

namespace App\Services;

use App\Core\Events\Event;

interface QueueServiceInterface {
    public function push(string $handlerClass, Event $event): int;
}

<?php

namespace App\Events;

use App\Core\Events\Event;

class AbandonCartDetected extends Event {
    public function __construct(
        public readonly int $cartId,
        public readonly string $email,
        public readonly string $name
    ) {}
}

<?php

namespace App\Events;

use App\Core\Events\Event;
use App\Models\Order;

class OrderStatusUpdated extends Event {
    public function __construct(
        public readonly Order $order,
        public readonly string $oldStatus,
        public readonly string $newStatus
    ) {}
}

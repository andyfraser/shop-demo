<?php

namespace App\Events;

use App\Core\Events\Event;
use App\Models\Order;

class OrderPlaced extends Event {
    public function __construct(
        public readonly Order $order,
        public readonly array $items
    ) {}
}

<?php

namespace App\Events;

use App\Core\Events\Event;
use App\Models\Order;

class RefundProcessed extends Event {
    public function __construct(
        public readonly Order $order,
        public readonly float $amount
    ) {}
}

<?php

namespace App\Events;

use App\Core\Events\Event;

class StockUpdated extends Event {
    public function __construct(
        public readonly int $variantId,
        public readonly int $oldStock,
        public readonly int $newStock
    ) {}
}

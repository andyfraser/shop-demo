<?php

namespace App\Events;

use App\Core\Events\Event;

class ReviewSubmitted extends Event {
    public function __construct(
        public readonly array $reviewData
    ) {}
}

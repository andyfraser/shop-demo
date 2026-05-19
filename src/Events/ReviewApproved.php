<?php

namespace App\Events;

use App\Core\Events\Event;

class ReviewApproved extends Event {
    public function __construct(
        public readonly int $reviewId
    ) {}
}

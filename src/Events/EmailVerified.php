<?php

namespace App\Events;

use App\Core\Events\Event;
use App\Models\User;

class EmailVerified extends Event {
    public function __construct(
        public readonly User $user
    ) {}
}

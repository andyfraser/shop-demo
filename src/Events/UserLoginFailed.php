<?php

namespace App\Events;

use App\Core\Events\Event;

class UserLoginFailed extends Event {
    public function __construct(
        public readonly string $email,
        public readonly string $ip
    ) {}
}

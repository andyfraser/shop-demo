<?php

namespace App\Events;

use App\Core\Events\Event;

class SettingUpdated extends Event {
    public function __construct(
        public readonly string $key,
        public readonly mixed $oldValue,
        public readonly mixed $newValue
    ) {}
}

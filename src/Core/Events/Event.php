<?php

namespace App\Core\Events;

abstract class Event {
    private bool $stopPropagation = false;

    public function isPropagationStopped(): bool {
        return $this->stopPropagation;
    }

    public function stopPropagation(): void {
        $this->stopPropagation = true;
    }

    public function getName(): string {
        return static::class;
    }
}

<?php

namespace App\Core\Events;

interface ListenerInterface {
    public function handle(Event $event): void;
}

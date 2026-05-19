<?php

namespace Tests;

use App\Core\Events\Event;
use App\Core\Events\EventDispatcherInterface;
use App\Core\Events\ListenerInterface;

class NullEventDispatcher implements EventDispatcherInterface {
    public function dispatch(Event $event): Event { return $event; }
    public function addListener(string $eventName, callable|ListenerInterface $listener, int $priority = 0): void {}
}

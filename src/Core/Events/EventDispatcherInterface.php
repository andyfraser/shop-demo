<?php

namespace App\Core\Events;

interface EventDispatcherInterface {
    public function dispatch(Event $event): Event;
    public function addListener(string $eventName, callable|ListenerInterface $listener, int $priority = 0): void;
}

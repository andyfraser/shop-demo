<?php

namespace App\Core\Events;

class EventDispatcher implements EventDispatcherInterface {
    private array $listeners = [];

    public function dispatch(Event $event): Event {
        $eventName = $event->getName();
        
        if (!isset($this->listeners[$eventName])) {
            return $event;
        }

        // Sort by priority (descending)
        krsort($this->listeners[$eventName]);

        foreach ($this->listeners[$eventName] as $priority => $listeners) {
            foreach ($listeners as $listener) {
                if ($event->isPropagationStopped()) {
                    break 2;
                }

                if ($listener instanceof ListenerInterface) {
                    $listener->handle($event);
                } else {
                    $listener($event);
                }
            }
        }

        return $event;
    }

    public function addListener(string $eventName, callable|ListenerInterface $listener, int $priority = 0): void {
        $this->listeners[$eventName][$priority][] = $listener;
    }
}

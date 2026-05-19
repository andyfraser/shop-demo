<?php

namespace App\Core\Events;

use Psr\Log\LoggerInterface;

class EventDispatcher implements EventDispatcherInterface {
    private array $listeners = [];

    public function __construct(
        private ?LoggerInterface $logger = null
    ) {}

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

                try {
                    if ($listener instanceof ListenerInterface) {
                        $listener->handle($event);
                    } else {
                        $listener($event);
                    }
                } catch (\Throwable $e) {
                    if ($this->logger) {
                        $this->logger->error("Listener failed for event {event}: {message}", [
                            'event' => $eventName,
                            'message' => $e->getMessage(),
                            'exception' => $e
                        ]);
                    }
                }
            }
        }

        return $event;
    }

    public function addListener(string $eventName, callable|ListenerInterface $listener, int $priority = 0): void {
        $this->listeners[$eventName][$priority][] = $listener;
    }
}

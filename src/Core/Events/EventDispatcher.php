<?php

namespace App\Core\Events;

use Psr\Log\LoggerInterface;
use App\Services\QueueServiceInterface;

class EventDispatcher implements EventDispatcherInterface {
    private array $listeners = [];

    public function __construct(
        private ?LoggerInterface $logger = null,
        private ?QueueServiceInterface $queueService = null
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
                    // Check if the listener should be queued
                    if ($this->shouldQueue($listener)) {
                        $this->queueListener($listener, $event);
                        continue;
                    }

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

    private function shouldQueue(mixed $listener): bool {
        if (!$this->queueService) {
            return false;
        }

        if (is_string($listener) && class_exists($listener)) {
            return is_subclass_of($listener, ShouldQueue::class);
        }

        if ($listener instanceof ShouldQueue) {
            return true;
        }

        return false;
    }

    private function queueListener(mixed $listener, Event $event): void {
        $handlerClass = is_string($listener) ? $listener : get_class($listener);
        $this->queueService->push($handlerClass, $event);
        
        if ($this->logger) {
            $this->logger->info("Queued listener {listener} for event {event}", [
                'listener' => $handlerClass,
                'event' => $event->getName()
            ]);
        }
    }

    public function addListener(string $eventName, callable|ListenerInterface|string $listener, int $priority = 0): void {
        $this->listeners[$eventName][$priority][] = $listener;
    }
}

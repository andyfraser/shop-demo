<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Events\Event;
use App\Core\Events\EventDispatcher;
use App\Core\Events\ListenerInterface;

class TestEvent extends Event {
    public bool $handled = false;
}

class TestListener implements ListenerInterface {
    public function handle(Event $event): void {
        if ($event instanceof TestEvent) {
            $event->handled = true;
        }
    }
}

class EventDispatcherTest extends TestCase {
    private EventDispatcher $dispatcher;

    public function setUp() {
        $this->dispatcher = new EventDispatcher();
    }

    public function testDispatchExecutesListener() {
        $event = new TestEvent();
        $listener = new TestListener();
        
        $this->dispatcher->addListener(TestEvent::class, $listener);
        $this->dispatcher->dispatch($event);
        
        $this->assertTrue($event->handled, "Event should have been marked as handled by the listener.");
    }

    public function testDispatchExecutesCallable() {
        $event = new TestEvent();
        $handled = false;
        
        $this->dispatcher->addListener(TestEvent::class, function(TestEvent $e) use (&$handled) {
            $handled = true;
        });
        
        $this->dispatcher->dispatch($event);
        $this->assertTrue($handled, "Event should have been handled by the closure.");
    }

    public function testPriority() {
        $event = new TestEvent();
        $order = [];
        
        $this->dispatcher->addListener(TestEvent::class, function() use (&$order) {
            $order[] = 'low';
        }, 0);
        
        $this->dispatcher->addListener(TestEvent::class, function() use (&$order) {
            $order[] = 'high';
        }, 100);
        
        $this->dispatcher->dispatch($event);
        
        $this->assertEquals(['high', 'low'], $order, "Listeners should execute in priority order (highest first).");
    }

    public function testStopPropagation() {
        $event = new TestEvent();
        $count = 0;
        
        $this->dispatcher->addListener(TestEvent::class, function(TestEvent $e) use (&$count) {
            $count++;
            $e->stopPropagation();
        }, 100);
        
        $this->dispatcher->addListener(TestEvent::class, function(TestEvent $e) use (&$count) {
            $count++;
        }, 0);
        
        $this->dispatcher->dispatch($event);
        
        $this->assertEquals(1, $count, "Propagation should stop after the first listener.");
    }
}

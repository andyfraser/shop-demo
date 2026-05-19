<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Container;
use App\Core\Events\EventDispatcherInterface;
use App\Events\OrderPlaced;
use App\Events\UserLoggedIn;
use App\Events\UserLoginFailed;

class EventRegistrationTest extends TestCase {
    private Container $container;

    public function setUp() {
        $this->container = new Container();
        
        // Mock config
        $config = [
            'app' => [
                'debug' => false,
                'log_path' => __DIR__ . '/../../logs/test.log',
                'cache_path' => __DIR__ . '/../../storage/cache'
            ],
            'db' => [
                'driver' => 'sqlite',
                'path' => ':memory:'
            ]
        ];

        // Register all services as they would be in the app
        $servicesFactory = require __DIR__ . '/../../config/services.php';
        $services = $servicesFactory($config);
        foreach ($services as $id => $factory) {
            $this->container->set($id, $factory);
        }
    }

    public function testEventDispatcherIsResolved() {
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    }

    public function testEventListenersAreRegistered() {
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        
        // Use reflection to check listeners as EventDispatcher doesn't have a getListeners() method
        $reflection = new \ReflectionClass($dispatcher);
        $property = $reflection->getProperty('listeners');
        $listeners = $property->getValue($dispatcher);

        $this->assertTrue(isset($listeners[OrderPlaced::class]), "OrderPlaced event should have listeners.");
        $this->assertTrue(isset($listeners[UserLoggedIn::class]), "UserLoggedIn event should have listeners.");
        $this->assertTrue(isset($listeners[UserLoginFailed::class]), "UserLoginFailed event should have listeners.");
    }
}

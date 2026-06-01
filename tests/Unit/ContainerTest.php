<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Container;
use Exception;

class ContainerTest extends TestCase {
    private Container $container;

    public function setUp() {
        $this->container = new Container();
    }

    public function testSetAndGet() {
        $this->container->set('test_service', function() {
            return (object)['name' => 'test'];
        });

        $service = $this->container->get('test_service');
        $this->assertEquals('test', $service->name);
    }

    public function testSingletonBehavior() {
        $this->container->set('singleton', function() {
            return new \stdClass();
        });

        $inst1 = $this->container->get('singleton');
        $inst2 = $this->container->get('singleton');

        $this->assertSame($inst1, $inst2);
    }

    public function testAutowiringNoConstructor() {
        $instance = $this->container->get(DummyClassNoConstructor::class);
        $this->assertTrue($instance instanceof DummyClassNoConstructor);
    }

    public function testAutowiringWithDependencies() {
        $instance = $this->container->get(DummyClassWithDependency::class);
        $this->assertTrue($instance instanceof DummyClassWithDependency);
        $this->assertTrue($instance->dep instanceof DummyClassNoConstructor);
    }

    public function testInterfaceMapping() {
        $this->container->set(DummyInterface::class, function() {
            return new DummyImplementation();
        });

        $instance = $this->container->get(DummyInterface::class);
        $this->assertTrue($instance instanceof DummyImplementation);
        $this->assertTrue($instance instanceof DummyInterface);
    }

    public function testResolutionException() {
        $this->expectException(Exception::class);
        $this->container->get('NonExistentClass');
    }

    public function testCircularDependencyDetection() {
        $thrown = false;
        try {
            $this->container->get(CircularClassA::class);
        } catch (Exception $e) {
            $thrown = true;
            $this->assertStringContainsString("Circular dependency detected", $e->getMessage());
        }
        $this->assertTrue($thrown, "Expected exception was not thrown.");
    }
}

interface DummyInterface {}
class DummyImplementation implements DummyInterface {}
class DummyClassNoConstructor {}
class DummyClassWithDependency {
    public function __construct(public DummyClassNoConstructor $dep) {}
}
class CircularClassA {
    public function __construct(CircularClassB $b) {}
}
class CircularClassB {
    public function __construct(CircularClassA $a) {}
}

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

    public function testResolutionException() {
        $this->expectException(Exception::class);
        $this->container->get('NonExistentClass');
    }
}

class DummyClassNoConstructor {}
class DummyClassWithDependency {
    public function __construct(public DummyClassNoConstructor $dep) {}
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Router;
use App\Core\Container;

class RoutesTest extends TestCase {
    public function testRoutesFileExists() {
        $this->assertTrue(file_exists(__DIR__ . '/../../config/routes.php'));
    }

    public function testRoutesFileReturnsArray() {
        $routes = require __DIR__ . '/../../config/routes.php';
        $this->assertTrue(is_array($routes));
        $this->assertTrue(count($routes) > 0);
    }

    public function testAllRoutesHaveRequiredKeys() {
        $routes = require __DIR__ . '/../../config/routes.php';
        foreach ($routes as $route) {
            $this->assertTrue(isset($route['method']), "Route missing method: " . var_export($route, true));
            $this->assertTrue(isset($route['path']), "Route missing path: " . var_export($route, true));
            $this->assertTrue(isset($route['handler']), "Route missing handler: " . var_export($route, true));
            $this->assertTrue(is_array($route['handler']), "Handler must be an array: " . var_export($route, true));
            $this->assertCount(2, $route['handler'], "Handler must have [Class, Method]: " . var_export($route, true));
        }
    }

    public function testAllHandlersExist() {
        $routes = require __DIR__ . '/../../config/routes.php';
        foreach ($routes as $route) {
            [$controller, $method] = $route['handler'];
            $this->assertTrue(class_exists($controller), "Controller class $controller does not exist");
            $this->assertTrue(method_exists($controller, $method), "Method $method does not exist in $controller");
        }
    }

    public function testAllMiddlewaresExist() {
        $routes = require __DIR__ . '/../../config/routes.php';
        foreach ($routes as $route) {
            if (isset($route['middlewares'])) {
                foreach ($route['middlewares'] as $middleware) {
                    $this->assertTrue(class_exists($middleware), "Middleware class $middleware does not exist");
                    $this->assertTrue(method_exists($middleware, 'handle'), "Middleware $middleware must have a handle() method");
                }
            }
        }
    }
}

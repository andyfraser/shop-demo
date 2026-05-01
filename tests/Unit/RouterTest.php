<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Router;

class RouterTest extends TestCase {
    private Router $router;

    public function setUp() {
        $this->router = new Router();
        $this->router->get('/', ['HomeController', 'index']);
        $this->router->get('/products/:slug', ['ProductController', 'show']);
        $this->router->post('/cart/add', ['CartController', 'add']);
        $this->router->get('/account/orders/:id', ['AccountController', 'orderDetail']);
    }

    public function testNumericDynamicRouteMatch() {
        $route = $this->router->match('/account/orders/123', 'GET');
        $this->assertNotNull($route);
        $this->assertEquals(['AccountController', 'orderDetail'], $route['handler']);
        $this->assertEquals(['123'], $route['params']);
    }

    public function testStaticRouteMatch() {
        $route = $this->router->match('/', 'GET');
        $this->assertTrue($route !== null);
        $this->assertEquals(['HomeController', 'index'], $route['handler']);
        $this->assertEquals([], $route['params']);
    }

    public function testDynamicRouteMatch() {
        $route = $this->router->match('/products/iphone-13', 'GET');
        $this->assertTrue($route !== null);
        $this->assertEquals(['ProductController', 'show'], $route['handler']);
        $this->assertEquals(['iphone-13'], $route['params']);
    }

    public function testPostRouteMatch() {
        $route = $this->router->match('/cart/add', 'POST');
        $this->assertTrue($route !== null);
        $this->assertEquals(['CartController', 'add'], $route['handler']);
    }

    public function testNoMatch() {
        $route = $this->router->match('/non-existent', 'GET');
        $this->assertTrue($route === null);

        $route = $this->router->match('/', 'POST'); // Wrong method
        $this->assertTrue($route === null);
    }

    public function testTrailingSlashMatch() {
        $route = $this->router->match('/cart/add/', 'POST');
        $this->assertNotNull($route);
    }

    public function testBaseUrlStripping() {
        if (!defined('BASE_URL')) {
            define('BASE_URL', '/shop-demo');
        }
        
        // Match with BASE_URL prefix
        $route = $this->router->match('/shop-demo/cart/add', 'POST');
        $this->assertNotNull($route);
        $this->assertEquals(['CartController', 'add'], $route['handler']);

        // Match with BASE_URL and trailing slash
        $route = $this->router->match('/shop-demo/cart/add/', 'POST');
        $this->assertNotNull($route);

        // Should NOT strip if it's just a prefix but not a full segment
        $route = $this->router->match('/shop-demo-plus/cart/add', 'POST');
        $this->assertNull($route);
    }
}

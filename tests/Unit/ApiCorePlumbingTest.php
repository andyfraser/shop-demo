<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Container;
use App\Core\Responses\JsonResponse;
use App\Middleware\CorsMiddleware;
use App\Controllers\Api\ApiController;

class ApiCorePlumbingTest extends TestCase {
    private Router $router;
    private Container $container;

    public function setUp() {
        $this->container = new Container();
        
        // Register CorsMiddleware in Container
        $this->container->set(CorsMiddleware::class, function() {
            return new CorsMiddleware();
        });

        // Register ApiController in Container
        $this->container->set(ApiController::class, function() {
            return new ApiController();
        });

        $this->router = new Router($this->container);
        $this->router->get('/api/v1/ping', [ApiController::class, 'ping']);
    }

    public function testApiPingRouteSuccess() {
        $server = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/ping'];
        $request = new Request([], [], $server, [], [], []);

        $response = $this->router->dispatch($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('pong', $data['data']['ping']);
        $this->assertEquals('GET', $data['data']['method']);
        
        // Assert CORS headers are attached
        $headers = $response->getHeaders();
        $this->assertTrue(isset($headers['Access-Control-Allow-Origin']));
    }

    public function testApiPreflightOptionsRequest() {
        $server = ['REQUEST_METHOD' => 'OPTIONS', 'REQUEST_URI' => '/api/v1/ping'];
        $request = new Request([], [], $server, [], [], []);

        $response = $this->router->dispatch($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());

        // Assert CORS headers are present
        $headers = $response->getHeaders();
        $this->assertTrue(isset($headers['Access-Control-Allow-Origin']));
        $this->assertTrue(isset($headers['Access-Control-Allow-Methods']));
    }

    public function testApiRouteNotFoundReturnsJson() {
        $server = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/invalid-route-name'];
        $request = new Request([], [], $server, [], [], []);

        $response = $this->router->dispatch($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('NOT_FOUND', $data['error']['code']);
    }

    public function testRequestJsonParser() {
        // We simulate $_SERVER and Content-Type payload
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // In this execution context, file_get_contents('php://input') is empty, 
        // but we can check that createFromGlobals completes without throwing error.
        $request = Request::createFromGlobals();
        $this->assertInstanceOf(Request::class, $request);
        
        unset($_SERVER['CONTENT_TYPE']);
        unset($_SERVER['REQUEST_METHOD']);
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Container;
use App\Core\Database;
use App\Core\Responses\JsonResponse;
use App\Services\AuthServiceInterface;
use App\Services\ProductServiceInterface;
use App\Middleware\ApiAuthMiddleware;
use App\Middleware\CorsMiddleware;

class ApiCatalogTest extends TestCase {
    private Router $router;
    private Container $container;
    private \PDO $db;
    private ProductServiceInterface $productService;
    private AuthServiceInterface $authService;

    public function setUp() {
        $_SESSION = [];
        $this->db = Database::getConnection();
        
        $this->container = new Container();
        
        $config = require __DIR__ . '/../../config/config.php';
        $servicesFactory = require __DIR__ . '/../../config/services.php';
        $services = $servicesFactory($config);
        
        $this->container->set(\PDO::class, fn() => $this->db);
        
        foreach ($services as $id => $factory) {
            if ($id !== \PDO::class) {
                $this->container->set($id, $factory);
            }
        }
        
        $request = new Request([], [], [], [], [], []);
        $this->container->set(Request::class, fn() => $request);

        $this->container->set(ApiAuthMiddleware::class, function($c) {
            return new ApiAuthMiddleware($c->get(AuthServiceInterface::class));
        });
        $this->container->set(CorsMiddleware::class, function() {
            return new CorsMiddleware();
        });

        $this->router = new Router($this->container);
        $routes = require __DIR__ . '/../../config/routes.php';
        foreach ($routes as $route) {
            $this->router->add($route['method'], $route['path'], $route['handler'], $route['middlewares'] ?? []);
        }

        $this->productService = $this->container->get(ProductServiceInterface::class);
        $this->authService = $this->container->get(AuthServiceInterface::class);
    }

    public function testApiGetProductsList() {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/products'], [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertTrue(isset($body['data']['products']));
        $this->assertTrue(isset($body['data']['pagination']));
    }

    public function testApiGetProductDetails() {
        // Find an active product to test
        $criteria = new \App\Core\QueryCriteria();
        $products = $this->productService->getAllActive($criteria);
        $this->assertNotEmpty($products);
        $product = $products[0];

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/products/' . $product->slug], [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertEquals($product->slug, $body['data']['slug']);
        $this->assertTrue(isset($body['data']['variants']));
        $this->assertTrue(isset($body['data']['attributes']));
        $this->assertTrue(isset($body['data']['reviews']));
    }

    public function testApiGetRelatedProducts() {
        $criteria = new \App\Core\QueryCriteria();
        $products = $this->productService->getAllActive($criteria);
        $this->assertNotEmpty($products);
        $product = $products[0];

        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/products/' . $product->slug . '/related'], [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
    }

    public function testApiGetCategories() {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/categories'], [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
    }

    public function testApiSubmitReviewAuthenticationAndSuccess() {
        $criteria = new \App\Core\QueryCriteria();
        $products = $this->productService->getAllActive($criteria);
        $this->assertNotEmpty($products);
        $product = $products[0];

        // 1. Rejects unauthenticated
        $postData = ['rating' => 5, 'comment' => 'Cool!'];
        $requestUnauth = new Request([], $postData, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/products/' . $product->slug . '/review'], [], [], []);
        $responseUnauth = $this->router->dispatch($requestUnauth);
        $this->assertEquals(401, $responseUnauth->getStatusCode());

        // Create user
        $email = 'review_user_' . rand(1000, 9999) . '@example.com';
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $this->db->prepare("INSERT INTO users (name, email, password_hash, role, is_verified) VALUES ('Reviewer', ?, ?, 'customer', 1)")
                 ->execute([$email, $hash]);
                 
        $userService = $this->container->get(\App\Services\UserServiceInterface::class);
        $user = $userService->findByEmail($email);
        $token = $this->authService->generateApiTokenForUser($user);

        // 2. Succeeds with Bearer token
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        $requestAuth = new Request([], $postData, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/products/' . $product->slug . '/review'], [], [], []);
        $responseAuth = $this->router->dispatch($requestAuth);
        
        $this->assertEquals(201, $responseAuth->getStatusCode());
        $bodyAuth = json_decode($responseAuth->getContent(), true);
        $this->assertTrue($bodyAuth['success']);

        unset($_SERVER['HTTP_AUTHORIZATION']);
    }
}

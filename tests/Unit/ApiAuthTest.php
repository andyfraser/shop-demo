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
use App\Services\UserServiceInterface;
use App\Middleware\ApiAuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Controllers\Api\ApiAuthController;

class ApiAuthTest extends TestCase {
    private Router $router;
    private Container $container;
    private \PDO $db;
    private AuthServiceInterface $authService;
    private UserServiceInterface $userService;

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

        $this->authService = $this->container->get(AuthServiceInterface::class);
        $this->userService = $this->container->get(UserServiceInterface::class);
    }

    public function tearDown() {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        unset($_SERVER['HTTP_X_CART_UUID']);
        unset($_SERVER['HTTP_CART_TOKEN']);
        unset($_SERVER['X_GENERATED_CART_UUID']);
    }

    public function testApiRegisterAndLoginFlow() {
        $email = 'api_user_' . rand(1000, 9999) . '@example.com';
        
        // 1. Test registration
        $postData = [
            'name' => 'API Customer',
            'email' => $email,
            'password' => 'secure123',
            'password_confirmation' => 'secure123'
        ];
        
        $request = new Request([], $postData, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/auth/register'], [], [], []);
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(201, $response->getStatusCode());
        
        $resBody = json_decode($response->getContent(), true);
        $this->assertTrue($resBody['success']);
        $this->assertTrue(isset($resBody['data']['token']));
        $this->assertEquals($email, $resBody['data']['user']['email']);

        // 2. Test login
        $loginData = [
            'email' => $email,
            'password' => 'secure123'
        ];
        
        $request = new Request([], $loginData, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/auth/login'], [], [], []);
        $response = $this->router->dispatch($request);
        
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $loginBody = json_decode($response->getContent(), true);
        $this->assertTrue($loginBody['success']);
        $this->assertTrue(isset($loginBody['data']['token']));
        
        // 3. Test wrong login credentials
        $wrongData = [
            'email' => $email,
            'password' => 'wrong_password'
        ];
        
        $request = new Request([], $wrongData, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/auth/login'], [], [], []);
        $response = $this->router->dispatch($request);
        
        $this->assertEquals(400, $response->getStatusCode());
        $errBody = json_decode($response->getContent(), true);
        $this->assertFalse($errBody['success']);
        $this->assertEquals('INVALID_CREDENTIALS', $errBody['error']['code']);
    }

    public function testMeEndpointRequiresAuth() {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/auth/me'], [], [], []);
        $response = $this->router->dispatch($request);
        
        $this->assertEquals(401, $response->getStatusCode());
        $resBody = json_decode($response->getContent(), true);
        $this->assertFalse($resBody['success']);
        $this->assertEquals('UNAUTHORIZED', $resBody['error']['code']);
    }

    public function testMeEndpointWithValidBearerToken() {
        $email = 'me_user_' . rand(1000, 9999) . '@example.com';
        
        // Create user
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $this->db->prepare("INSERT INTO users (name, email, password_hash, role, is_verified) VALUES ('Me User', ?, ?, 'customer', 1)")
                 ->execute([$email, $hash]);
                 
        $user = $this->userService->findByEmail($email);
        $token = $this->authService->generateApiTokenForUser($user);
        
        // Set request with Bearer token header
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/auth/me'], [], [], []);
        $response = $this->router->dispatch($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $resBody = json_decode($response->getContent(), true);
        $this->assertTrue($resBody['success']);
        $this->assertEquals($email, $resBody['data']['user']['email']);
        
        // Clean up global server array
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testLogoutInvalidatesToken() {
        $email = 'logout_user_' . rand(1000, 9999) . '@example.com';
        
        // Create user
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $this->db->prepare("INSERT INTO users (name, email, password_hash, role, is_verified) VALUES ('Logout User', ?, ?, 'customer', 1)")
                 ->execute([$email, $hash]);
                 
        $user = $this->userService->findByEmail($email);
        $token = $this->authService->generateApiTokenForUser($user);
        
        // 1. Logout
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        $request = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/auth/logout'], [], [], []);
        $response = $this->router->dispatch($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // 2. Attempt to view /auth/me with the same token
        $requestMe = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/auth/me'], [], [], []);
        $responseMe = $this->router->dispatch($requestMe);
        
        $this->assertEquals(401, $responseMe->getStatusCode());
        
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }
}

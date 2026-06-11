<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Container;
use App\Core\Database;
use App\Core\Responses\JsonResponse;
use App\Services\CartServiceInterface;
use App\Services\ProductServiceInterface;
use App\Services\AuthServiceInterface;
use App\Middleware\ApiAuthMiddleware;
use App\Middleware\CorsMiddleware;

class ApiCartTest extends TestCase {
    private Router $router;
    private Container $container;
    private \PDO $db;
    private CartServiceInterface $cartService;
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

        $this->cartService = $this->container->get(CartServiceInterface::class);
        $this->productService = $this->container->get(ProductServiceInterface::class);
        $this->authService = $this->container->get(AuthServiceInterface::class);

        // Reset server global headers before each test
        unset($_SERVER['HTTP_X_CART_UUID']);
        unset($_SERVER['HTTP_CART_TOKEN']);
        unset($_SERVER['X_GENERATED_CART_UUID']);
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function tearDown() {
        unset($_SERVER['HTTP_X_CART_UUID']);
        unset($_SERVER['HTTP_CART_TOKEN']);
        unset($_SERVER['X_GENERATED_CART_UUID']);
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    public function testApiGuestCartGeneration() {
        $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/cart'], [], [], []);
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $headers = $response->getHeaders();
        // Check that a new UUID is generated and exposed
        $this->assertTrue(isset($headers['X-Cart-UUID']));
        $this->assertTrue(isset($headers['Access-Control-Expose-Headers']));
        $this->assertEquals('X-Cart-UUID', $headers['Access-Control-Expose-Headers']);
    }

    public function testApiCartAddAndUpdateFlow() {
        $criteria = new \App\Core\QueryCriteria();
        $products = $this->productService->getAllActive($criteria);
        $this->assertNotEmpty($products);
        $product = $products[0];

        $uuid = 'test-cart-uuid-' . rand(1000, 9999);
        $_SERVER['HTTP_X_CART_UUID'] = $uuid;

        // 1. Add product to guest cart
        $postData = ['product_id' => $product->id, 'quantity' => 2];
        $requestAdd = new Request([], $postData, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/cart/add'], [], [], []);
        $responseAdd = $this->router->dispatch($requestAdd);

        $this->assertEquals(200, $responseAdd->getStatusCode());
        $bodyAdd = json_decode($responseAdd->getContent(), true);
        $this->assertTrue($bodyAdd['success']);
        
        // Assert item was added
        $items = $bodyAdd['data']['items'];
        $this->assertCount(1, $items);
        $this->assertEquals($product->id, $items[0]['product_id']);
        $this->assertEquals(2, $items[0]['quantity']);
        $itemKey = $items[0]['key'];

        // 2. Update item quantity
        $updateData = ['key' => $itemKey, 'quantity' => 5];
        $requestUpdate = new Request([], $updateData, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/cart/update'], [], [], []);
        $responseUpdate = $this->router->dispatch($requestUpdate);

        $this->assertEquals(200, $responseUpdate->getStatusCode());
        $bodyUpdate = json_decode($responseUpdate->getContent(), true);
        $itemsUpdate = $bodyUpdate['data']['items'];
        $this->assertEquals(5, $itemsUpdate[0]['quantity']);

        // 3. Remove item by setting qty to 0
        $deleteData = ['key' => $itemKey, 'quantity' => 0];
        $requestDelete = new Request([], $deleteData, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/cart/update'], [], [], []);
        $responseDelete = $this->router->dispatch($requestDelete);

        $bodyDelete = json_decode($responseDelete->getContent(), true);
        $this->assertCount(0, $bodyDelete['data']['items']);

        unset($_SERVER['HTTP_X_CART_UUID']);
    }

    public function testApiWishlistSecurityAndToggles() {
        $criteria = new \App\Core\QueryCriteria();
        $products = $this->productService->getAllActive($criteria);
        $this->assertNotEmpty($products);
        $product = $products[0];

        // 1. Unauthenticated rejects wishlist access
        $requestMe = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/wishlist'], [], [], []);
        $responseMe = $this->router->dispatch($requestMe);
        $this->assertEquals(401, $responseMe->getStatusCode());

        // Authenticate
        $email = 'wishlist_user_' . rand(1000, 9999) . '@example.com';
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $this->db->prepare("INSERT INTO users (name, email, password_hash, role, is_verified) VALUES ('Wishlister', ?, ?, 'customer', 1)")
                 ->execute([$email, $hash]);
                 
        $userService = $this->container->get(\App\Services\UserServiceInterface::class);
        $user = $userService->findByEmail($email);
        $token = $this->authService->generateApiTokenForUser($user);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        // 2. Add to wishlist
        $requestAdd = new Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/wishlist/add/' . $product->id], [], [], []);
        $responseAdd = $this->router->dispatch($requestAdd);
        $this->assertEquals(201, $responseAdd->getStatusCode());

        // 3. Retrieve wishlist
        $requestGet = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/wishlist'], [], [], []);
        $responseGet = $this->router->dispatch($requestGet);
        $this->assertEquals(200, $responseGet->getStatusCode());
        
        $bodyGet = json_decode($responseGet->getContent(), true);
        $this->assertCount(1, $bodyGet['data']['items']);
        $this->assertEquals($product->id, $bodyGet['data']['items'][0]['id']);

        // 4. Toggle privacy settings
        $postPrivacy = ['is_public' => true];
        $requestPrivacy = new Request([], $postPrivacy, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/wishlist/toggle-privacy'], [], [], []);
        $responsePrivacy = $this->router->dispatch($requestPrivacy);
        $this->assertEquals(200, $responsePrivacy->getStatusCode());
        
        $bodyPrivacy = json_decode($responsePrivacy->getContent(), true);
        $this->assertTrue($bodyPrivacy['data']['is_public']);
        $this->assertTrue(isset($bodyPrivacy['data']['share_url']));

        unset($_SERVER['HTTP_AUTHORIZATION']);
    }
}

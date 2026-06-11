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
use App\Services\OrderServiceInterface;
use App\Services\AddressServiceInterface;
use App\Middleware\ApiAuthMiddleware;
use App\Middleware\CorsMiddleware;

class ApiCheckoutTest extends TestCase {
    private Router $router;
    private Container $container;
    private \PDO $db;
    private CartServiceInterface $cartService;
    private ProductServiceInterface $productService;
    private AuthServiceInterface $authService;
    private OrderServiceInterface $orderService;
    private AddressServiceInterface $addressService;

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
        
        // Ensure the Container has itself registered as Container::class so static lookups work
        $this->container->set(Container::class, fn() => $this->container);
        
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
        $this->orderService = $this->container->get(OrderServiceInterface::class);
        $this->addressService = $this->container->get(AddressServiceInterface::class);

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

    public function testAddressManagement() {
        // Authenticate a test user
        $email = 'address_user_' . rand(1000, 9999) . '@example.com';
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $this->db->prepare("INSERT INTO users (name, email, password_hash, role, is_verified) VALUES ('Address Tester', ?, ?, 'customer', 1)")
                 ->execute([$email, $hash]);
                 
        $userService = $this->container->get(\App\Services\UserServiceInterface::class);
        $user = $userService->findByEmail($email);
        $token = $this->authService->generateApiTokenForUser($user);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        // 1. Get saved addresses (should be empty initially)
        $reqGet = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/account/addresses'], [], [], []);
        $resGet = $this->router->dispatch($reqGet);
        $this->assertEquals(200, $resGet->getStatusCode());
        $bodyGet = json_decode($resGet->getContent(), true);
        $this->assertCount(0, $bodyGet['data']);

        // 2. Save a new address
        $addressPayload = [
            'name' => 'John Address',
            'address' => '123 Main St',
            'city' => 'Metropolis',
            'postcode' => '12345',
            'country' => 'US',
            'label' => 'Home',
            'is_default' => 1
        ];
        $reqSave = new Request([], $addressPayload, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/account/addresses/save'], [], [], []);
        $resSave = $this->router->dispatch($reqSave);
        $this->assertEquals(201, $resSave->getStatusCode());
        $bodySave = json_decode($resSave->getContent(), true);
        $this->assertTrue($bodySave['success']);
        $this->assertTrue(isset($bodySave['data']['id']));
        $savedId = $bodySave['data']['id'];

        // 3. Get saved addresses again (should contain 1)
        $reqGet2 = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/account/addresses'], [], [], []);
        $resGet2 = $this->router->dispatch($reqGet2);
        $bodyGet2 = json_decode($resGet2->getContent(), true);
        $this->assertCount(1, $bodyGet2['data']);
        $this->assertEquals('123 Main St', $bodyGet2['data'][0]['address']);
        $this->assertTrue($bodyGet2['data'][0]['is_default']);

        // 4. Update the saved address
        $updatePayload = array_merge($addressPayload, ['id' => $savedId, 'city' => 'Gotham']);
        $reqUpdate = new Request([], $updatePayload, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/account/addresses/save'], [], [], []);
        $resUpdate = $this->router->dispatch($reqUpdate);
        $this->assertEquals(200, $resUpdate->getStatusCode());

        $reqGet3 = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/account/addresses'], [], [], []);
        $resGet3 = $this->router->dispatch($reqGet3);
        $bodyGet3 = json_decode($resGet3->getContent(), true);
        $this->assertEquals('Gotham', $bodyGet3['data'][0]['city']);
    }

    public function testCheckoutProcessingFlow() {
        // 1. Setup user & token
        $email = 'buyer_' . rand(1000, 9999) . '@example.com';
        $hash = password_hash('password', PASSWORD_DEFAULT);
        $this->db->prepare("INSERT INTO users (name, email, password_hash, role, is_verified) VALUES ('Buyer', ?, ?, 'customer', 1)")
                 ->execute([$email, $hash]);
        $userService = $this->container->get(\App\Services\UserServiceInterface::class);
        $user = $userService->findByEmail($email);
        $token = $this->authService->generateApiTokenForUser($user);

        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

        // 2. Set up guest Cart UUID header and add item
        $uuid = 'checkout-cart-uuid-' . rand(1000, 9999);
        $_SERVER['HTTP_X_CART_UUID'] = $uuid;

        $products = $this->productService->getAllActive(new \App\Core\QueryCriteria());
        $this->assertNotEmpty($products);
        $product = $products[0];
        
        // Update product to have NULL category_id to prevent category foreign key violations during order placement
        $this->db->prepare("UPDATE products SET category_id = NULL WHERE id = ?")->execute([$product->id]);

        $this->db->exec("INSERT OR IGNORE INTO delivery_options (id, name, price, active) VALUES (1, 'Standard Shipping', 5.0, 1)");

        $postAdd = ['product_id' => $product->id, 'quantity' => 1];
        $reqAdd = new Request([], $postAdd, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/cart/add'], [], [], []);
        $resAdd = $this->router->dispatch($reqAdd);
        $this->assertEquals(200, $resAdd->getStatusCode());

        // 3. Run checkout validation failure
        $checkoutPayload = [
            'name' => 'Buyer',
            'email' => $email,
            // missing other fields
        ];
        $reqCheckout1 = new Request([], $checkoutPayload, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/checkout'], [], [], []);
        $resCheckout1 = $this->router->dispatch($reqCheckout1);
        $this->assertEquals(400, $resCheckout1->getStatusCode());
        $bodyCheckout1 = json_decode($resCheckout1->getContent(), true);
        $this->assertFalse($bodyCheckout1['success']);
        $this->assertEquals('VALIDATION_ERROR', $bodyCheckout1['error']['code']);

        // 4. Run successful checkout
        $checkoutPayloadComplete = [
            'name' => 'Buyer',
            'email' => $email,
            'address' => '100 Road St',
            'city' => 'Cityville',
            'postcode' => '90210',
            'country' => 'US',
            'delivery_option_id' => 1,
            'card_number' => '4111111111111111',
            'card_expiry' => '12/28',
            'card_cvc' => '123'
        ];
        $reqCheckout2 = new Request([], $checkoutPayloadComplete, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/checkout'], [], [], []);
        $resCheckout2 = $this->router->dispatch($reqCheckout2);
        
        $this->assertEquals(201, $resCheckout2->getStatusCode());
        $bodyCheckout2 = json_decode($resCheckout2->getContent(), true);
        $this->assertTrue($bodyCheckout2['success']);
        $this->assertTrue(isset($bodyCheckout2['data']['order_reference']));
        $this->assertEquals('paid', $bodyCheckout2['data']['status']);
        $orderId = $bodyCheckout2['data']['order_id'];
        $orderRef = $bodyCheckout2['data']['order_reference'];

        // 5. Query user orders
        $reqOrders = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/orders'], [], [], []);
        $resOrders = $this->router->dispatch($reqOrders);
        $this->assertEquals(200, $resOrders->getStatusCode());
        $bodyOrders = json_decode($resOrders->getContent(), true);
        $this->assertCount(1, $bodyOrders['data']);
        $this->assertEquals($orderRef, $bodyOrders['data'][0]['order_reference']);

        // 6. Query specific order details
        $reqOrderDetail = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/orders/' . $orderId], [], [], []);
        $resOrderDetail = $this->router->dispatch($reqOrderDetail);
        $this->assertEquals(200, $resOrderDetail->getStatusCode());
        $bodyDetail = json_decode($resOrderDetail->getContent(), true);
        $this->assertEquals('paid', $bodyDetail['data']['status']);
        $this->assertCount(1, $bodyDetail['data']['items']);

        // 7. Test Guest Lookup (authenticated or unauthenticated, doesn't matter)
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $lookupPayload = [
            'order_reference' => $orderRef,
            'email' => $email
        ];
        $reqLookup = new Request([], $lookupPayload, ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/orders/lookup'], [], [], []);
        $resLookup = $this->router->dispatch($reqLookup);
        $this->assertEquals(200, $resLookup->getStatusCode());
        $bodyLookup = json_decode($resLookup->getContent(), true);
        $this->assertTrue($bodyLookup['success']);
        $this->assertEquals($orderRef, $bodyLookup['data']['order_reference']);
    }
}

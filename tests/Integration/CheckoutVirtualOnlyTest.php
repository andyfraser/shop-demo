<?php

namespace Tests\Integration;

use Tests\TestCase;
use Tests\RequestSimulation;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;

class CheckoutVirtualOnlyTest extends TestCase {
    use RequestSimulation;

    private \PDO $db;

    public function setUp() {
        $this->setupApp();
        $this->db = $this->container->get(\PDO::class);
        
        // Clean up tables to avoid interference
        $this->db->exec("DELETE FROM cart_items");
        $this->db->exec("DELETE FROM carts");
        $this->db->exec("DELETE FROM order_items");
        $this->db->exec("DELETE FROM order_promotions");
        $this->db->exec("DELETE FROM orders");
        $this->db->exec("DELETE FROM promotions");
        $this->db->exec("DELETE FROM products WHERE id >= 20000");
    }

    public function testCheckoutVirtualOnlyDoesNotShowDeliveryOptions() {
        // 1. Insert a virtual product
        $productId = 20001;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, stock) VALUES (?, 'Digital License Key', 'digital-license', 'DIGILIC', 10.00, 1, 1, 'license', 0)")
                 ->execute([$productId]);

        // 2. Create a cart for user 2 (Jane Smith) and add the virtual product
        $this->db->prepare("INSERT INTO carts (user_id, session_id) VALUES (?, ?)")
                 ->execute([2, 'test_session_id_123']);
        $cartId = $this->db->lastInsertId();

        $this->db->prepare("INSERT INTO cart_items (cart_id, product_id, qty, variant_id) VALUES (?, ?, 1, NULL)")
                 ->execute([$cartId, $productId]);

        $session = [
            'user' => ['id' => 2]
        ];

        // 3. Simulating GET /checkout
        $response = $this->get('/checkout', $session);
        $this->assertInstanceOf(HtmlResponse::class, $response);
        
        $body = $response->getContent();

        // 4. Assertions on the view variables / DOM output
        $this->assertStringContainsString('data-is-virtual-only="1"', $body);
        $this->assertStringContainsString('id="delivery-method-card" style="margin-top:1.5rem; display: none;"', $body);
        $this->assertStringContainsString('Virtual Order Bypass:', $body);
        
        // Since delivery_options is empty, it should not render any delivery options inputs (like standard or express)
        $this->assertStringNotContainsString('Standard', $body);
        $this->assertStringNotContainsString('Express', $body);
    }

    public function testCheckoutVirtualOnlyProcessing() {
        // 1. Insert a virtual product
        $productId = 20002;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, stock) VALUES (?, 'Ebook PDF', 'ebook-pdf', 'EBKPDF', 15.00, 1, 1, 'download', 0)")
                 ->execute([$productId]);

        // 2. Create a cart for user 2 (Jane Smith) and add the virtual product
        $this->db->prepare("INSERT INTO carts (user_id, session_id) VALUES (?, ?)")
                 ->execute([2, 'test_session_id_456']);
        $cartId = $this->db->lastInsertId();

        $this->db->prepare("INSERT INTO cart_items (cart_id, product_id, qty, variant_id) VALUES (?, ?, 1, NULL)")
                 ->execute([$cartId, $productId]);

        $session = [
            'user' => ['id' => 2],
            'csrf_token' => 'my_test_csrf_token'
        ];

        // 3. Simulating POST /checkout
        $postData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'csrf_token' => 'my_test_csrf_token'
        ];

        $response = $this->post('/checkout', $postData, $session);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaders()['Location'] ?? '';
        $this->assertStringContainsString('/order/confirm?id=', $location);

        // Parse order ID from redirect URL
        parse_str(parse_url($location, PHP_URL_QUERY), $queryParams);
        $orderId = (int)($queryParams['id'] ?? 0);
        $this->assertGreaterThan(0, $orderId);

        // Verify order details in DB
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotEmpty($order);
        $this->assertEquals(0.0, (float)$order['delivery_cost']);
        $this->assertEquals('Digital Delivery', $order['delivery_method']);
        $this->assertEquals('Digital Delivery', $order['shipping_address']);
        $this->assertEquals(15.00, (float)$order['total']);
    }
}

<?php

namespace Tests\Integration;

use Tests\TestCase;
use Tests\RequestSimulation;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\JsonResponse;
use App\Core\Responses\RedirectResponse;

class GiftCardMetadataTest extends TestCase {
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

    public function testAddGiftCardValidation() {
        // 1. Insert a gift card product
        $giftCardId = 20101;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, stock) VALUES (?, 'Super Gift Card', 'super-gift-card', 'GC100', 100.00, 1, 1, 'giftcard', 0)")
                 ->execute([$giftCardId]);

        $session = [
            'csrf_token' => 'test_csrf'
        ];

        // 2. Try to add without recipient email (AJAX simulation)
        $postData = [
            'product_id' => $giftCardId,
            'qty' => 1,
            'slug' => 'super-gift-card',
            'csrf_token' => 'test_csrf',
            'recipient_email' => ''
        ];

        $response = $this->simulateRequest('POST', '/product/super-gift-card', $postData, $session, [], [], [
            'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest'
        ]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['ok']);
        $this->assertEquals('Recipient email is required for gift cards.', $data['message']);

        // 3. Try to add with an invalid recipient email (AJAX simulation)
        $postData['recipient_email'] = 'not-an-email';
        $response = $this->simulateRequest('POST', '/product/super-gift-card', $postData, $session, [], [], [
            'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest'
        ]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['ok']);
        $this->assertEquals('Please enter a valid recipient email address.', $data['message']);

        // 4. Add with correct details
        $postData['recipient_email'] = 'recipient@example.com';
        $postData['sender_name'] = 'Alice';
        $postData['message'] = 'Congrats!';
        
        $response = $this->simulateRequest('POST', '/product/super-gift-card', $postData, $session, [], [], [
            'HTTP_X_REQUESTED_WITH' => 'xmlhttprequest'
        ]);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['ok']);

        // Verify it was saved in cart_items database
        $stmt = $this->db->query("SELECT * FROM cart_items LIMIT 1");
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotEmpty($item);
        
        $metadata = json_decode($item['metadata'], true);
        $this->assertEquals('recipient@example.com', $metadata['recipient_email']);
        $this->assertEquals('Alice', $metadata['sender_name']);
        $this->assertEquals('Congrats!', $metadata['message']);
    }

    public function testCheckoutWithGiftCardAndPhysicalProduct() {
        // 1. Insert a gift card product and a physical product
        $giftCardId = 20102;
        $physicalId = 20103;

        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, stock) VALUES (?, 'Holiday Gift Card', 'holiday-gift-card', 'GC50', 50.00, 1, 1, 'giftcard', 0)")
                 ->execute([$giftCardId]);
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, stock) VALUES (?, 'Physical Mug', 'physical-mug', 'MUG01', 12.00, 1, 0, NULL, 10)")
                 ->execute([$physicalId]);

        // 2. Insert standard delivery option if not exists
        $this->db->exec("INSERT OR IGNORE INTO delivery_options (id, name, price, active) VALUES (1, 'Standard Delivery', 4.50, 1)");

        // 3. Create a cart and add both items
        $this->db->prepare("INSERT INTO carts (user_id, session_id) VALUES (?, ?)")
                 ->execute([2, 'test_session_gc_phy']);
        $cartId = $this->db->lastInsertId();

        $metadata = json_encode([
            'recipient_email' => 'lucky@example.com',
            'sender_name' => 'Generous Sender',
            'message' => 'Happy holidays!'
        ]);

        $this->db->prepare("INSERT INTO cart_items (cart_id, product_id, qty, variant_id, metadata) VALUES (?, ?, 1, NULL, ?)")
                 ->execute([$cartId, $giftCardId, $metadata]);
        $this->db->prepare("INSERT INTO cart_items (cart_id, product_id, qty, variant_id, metadata) VALUES (?, ?, 1, NULL, NULL)")
                 ->execute([$cartId, $physicalId]);

        // 4. Perform checkout post
        $session = [
            'user' => ['id' => 2],
            'csrf_token' => 'gc_csrf'
        ];

        $postData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'address' => '123 Main St',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'country' => 'United Kingdom',
            'delivery_option_id' => 1,
            'csrf_token' => 'gc_csrf'
        ];

        $response = $this->post('/checkout', $postData, $session);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaders()['Location'] ?? '';
        $this->assertStringContainsString('/order/confirm?id=', $location);

        // Parse order ID from redirect URL
        parse_str(parse_url($location, PHP_URL_QUERY), $queryParams);
        $orderId = (int)($queryParams['id'] ?? 0);
        $this->assertGreaterThan(0, $orderId);

        // 5. Verify the order metadata
        $stmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY product_id ASC");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertCount(2, $items);
        
        // Product 20102 is the Holiday Gift Card (since IDs are 20102 and 20103, Holiday Gift Card is first in ASC order)
        $giftCardItem = $items[0];
        $this->assertEquals($giftCardId, $giftCardItem['product_id']);
        $this->assertNotNull($giftCardItem['metadata']);
        
        $savedMeta = json_decode($giftCardItem['metadata'], true);
        $this->assertEquals('lucky@example.com', $savedMeta['recipient_email']);
        $this->assertEquals('Generous Sender', $savedMeta['sender_name']);
        $this->assertEquals('Happy holidays!', $savedMeta['message']);

        // Physical Mug item should not have metadata
        $physicalItem = $items[1];
        $this->assertEquals($physicalId, $physicalItem['product_id']);
        $this->assertNull($physicalItem['metadata']);
    }
}

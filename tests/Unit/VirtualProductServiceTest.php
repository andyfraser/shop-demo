<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VirtualProductService;
use App\Repositories\ProductRepository;
use App\Repositories\VirtualProductRepository;
use App\Repositories\UserRepository;
use App\Models\Order;
use App\Models\OrderItem;
use App\Core\Database;
use Tests\NullLogger;

class MockEmailService implements \App\Services\EmailServiceInterface {
    public array $sentEmails = [];

    public function sendVerificationEmail(string $to, string $name, string $token): bool { return true; }
    public function sendOrderConfirmation(\App\Models\Order $order, array $items): bool { return true; }
    public function sendStatusUpdateEmail(string $to, \App\Models\Order $order, string $status): bool { return true; }
    public function sendReturnRequestedEmail(\App\Models\ReturnOrder $return, string $to): bool { return true; }
    public function sendReturnUpdateEmail(\App\Models\ReturnOrder $return, string $to): bool { return true; }
    public function sendAbandonedCartEmail(string $to, string $name): bool { return true; }
    
    public function sendDigitalDownloadsEmail(string $toEmail, string $customerName, array $downloads): bool {
        $this->sentEmails[] = [
            'type' => 'downloads',
            'to' => $toEmail,
            'name' => $customerName,
            'downloads' => $downloads
        ];
        return true;
    }
    public function sendGiftCardEmail(string $toEmail, string $recipientName, string $senderName, string $code, float $amount, ?string $message): bool {
        $this->sentEmails[] = [
            'type' => 'giftcard',
            'to' => $toEmail,
            'recipientName' => $recipientName,
            'senderName' => $senderName,
            'code' => $code,
            'amount' => $amount,
            'message' => $message
        ];
        return true;
    }
    public function sendLicenseKeyEmail(string $toEmail, string $customerName, array $licenses): bool {
        $this->sentEmails[] = [
            'type' => 'licenses',
            'to' => $toEmail,
            'name' => $customerName,
            'licenses' => $licenses
        ];
        return true;
    }
    public function sendEventTicketEmail(string $toEmail, string $customerName, array $tickets): bool {
        $this->sentEmails[] = [
            'type' => 'tickets',
            'to' => $toEmail,
            'name' => $customerName,
            'tickets' => $tickets
        ];
        return true;
    }
}

class VirtualProductServiceTest extends TestCase {
    private \PDO $db;
    private VirtualProductService $service;
    private MockEmailService $emailService;

    public function setUp() {
        $this->db = Database::getConnection();
        $logger = new NullLogger();
        $productRepository = new ProductRepository($this->db, $logger);
        $virtualProductRepository = new VirtualProductRepository($this->db, $logger);
        $userRepository = new UserRepository($this->db, $logger);
        $this->emailService = new MockEmailService();
        $this->service = new VirtualProductService(
            $virtualProductRepository,
            $userRepository,
            $productRepository,
            $this->emailService,
            $logger
        );
    }

    public function tearDown() {
        $this->db->exec("DELETE FROM customer_downloads WHERE order_item_id IN (6001)");
        $this->db->exec("DELETE FROM gift_cards WHERE order_item_id IN (6002)");
        $this->db->exec("DELETE FROM product_license_keys WHERE product_id IN (10003)");
        $this->db->exec("DELETE FROM order_item_licenses WHERE order_item_id IN (6003)");
        $this->db->exec("DELETE FROM customer_tickets WHERE order_item_id IN (6005)");
        $this->db->exec("DELETE FROM order_items WHERE id IN (6001, 6002, 6003, 6004, 6005, 6010, 6011, 6015)");
        $this->db->exec("DELETE FROM orders WHERE id IN (5001, 5002, 5003, 5004, 5005, 5010, 5011, 5015)");
        $this->db->exec("DELETE FROM products WHERE id IN (10001, 10002, 10003, 10004, 10005, 10010, 10011, 10015)");
        $this->db->exec("DELETE FROM users WHERE id IN (7001, 7015)");
    }

    public function testFulfillDigitalFileDownload() {
        // Insert digital file product
        $productId = 10001;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type) VALUES (?, 'Test Ebook', 'test-ebook', 'EBOOK1', 9.99, 1, 1, 'file')")
                 ->execute([$productId]);

        // Create mock Order
        $order = new Order(new NullLogger());
        $order->id = 5001;
        $order->customer_email = 'customer@test.com';
        $order->customer_name = 'Jane Doe';
        $order->user_id = null;

        // Create mock OrderItem
        $item = new OrderItem(new NullLogger());
        $item->id = 6001;
        $item->order_id = 5001;
        $item->product_id = $productId;
        $item->variant_id = null;
        $item->quantity = 2;
        $item->unit_price = 9.99;
        $item->metadata = null;

        // Insert into database to satisfy foreign keys
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (?, ?, 'confirmed', ?, ?, ?)")
                 ->execute([$order->id, $order->user_id, 19.98, $order->customer_email, $order->customer_name]);
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, unit_price, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$item->id, $item->order_id, $item->product_id, $item->variant_id, $item->quantity, $item->unit_price, $item->metadata]);

        // Run fulfillment
        $this->service->fulfillDigitalItems($order, [$item]);

        // Check if tokens were created in customer_downloads table
        $stmt = $this->db->prepare("SELECT * FROM customer_downloads WHERE order_item_id = ?");
        $stmt->execute([$item->id]);
        $downloads = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertEquals(2, count($downloads));
        $this->assertNotNull($downloads[0]['download_token']);

        // Verify download token works
        $token = $downloads[0]['download_token'];
        $downloadModel = $this->service->verifyDownloadToken($token);
        $this->assertNotNull($downloadModel);
        $this->assertEquals($productId, $downloadModel->product_id);

        // Check email was sent
        $this->assertCount(1, $this->emailService->sentEmails);
        $this->assertEquals('downloads', $this->emailService->sentEmails[0]['type']);
        $this->assertEquals('customer@test.com', $this->emailService->sentEmails[0]['to']);
        $this->assertEquals(2, count($this->emailService->sentEmails[0]['downloads']));
    }

    public function testFulfillGiftCard() {
        // Insert digital giftcard product
        $productId = 10002;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type) VALUES (?, 'Gift Card $50', 'gc-50', 'GC50', 50.00, 1, 1, 'giftcard')")
                 ->execute([$productId]);

        // Create mock Order
        $order = new Order(new NullLogger());
        $order->id = 5002;
        $order->customer_email = 'buyer@test.com';
        $order->customer_name = 'John Buyer';
        $order->user_id = null;

        // Create mock OrderItem with metadata
        $item = new OrderItem(new NullLogger());
        $item->id = 6002;
        $item->order_id = 5002;
        $item->product_id = $productId;
        $item->variant_id = null;
        $item->quantity = 1;
        $item->unit_price = 50.00;
        $item->metadata = json_encode([
            'recipient_email' => 'recipient@test.com',
            'sender_name' => 'John Buyer',
            'message' => 'Happy Birthday!'
        ]);

        // Insert into database to satisfy foreign keys
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (?, ?, 'confirmed', ?, ?, ?)")
                 ->execute([$order->id, $order->user_id, 50.00, $order->customer_email, $order->customer_name]);
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, unit_price, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$item->id, $item->order_id, $item->product_id, $item->variant_id, $item->quantity, $item->unit_price, $item->metadata]);

        // Run fulfillment
        $this->service->fulfillDigitalItems($order, [$item]);

        // Check if gift card was created
        $stmt = $this->db->prepare("SELECT * FROM gift_cards WHERE order_item_id = ?");
        $stmt->execute([$item->id]);
        $gc = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertNotNull($gc);
        $this->assertEquals(50.00, (float)$gc['initial_amount']);
        $this->assertEquals(50.00, (float)$gc['remaining_amount']);
        $this->assertEquals('recipient@test.com', $gc['recipient_email']);
        $this->assertEquals('John Buyer', $gc['sender_name']);
        $this->assertEquals('Happy Birthday!', $gc['message']);

        $code = $gc['code'];

        // Test active gift card retrieval
        $activeGc = $this->service->getActiveGiftCard($code);
        $this->assertNotNull($activeGc);

        // Test apply gift card code
        $res = $this->service->applyGiftCardCode($code, 30.00);
        $this->assertTrue($res['success']);
        $this->assertEquals(30.00, $res['discount']);
        $this->assertEquals(20.00, $res['remaining']);

        // Test apply gift card code exceeding remaining balance
        $resExceeds = $this->service->applyGiftCardCode($code, 60.00);
        $this->assertTrue($resExceeds['success']);
        $this->assertEquals(50.00, $resExceeds['discount']);
        $this->assertEquals(0.00, $resExceeds['remaining']);

        // Test deduct balance
        $deducted = $this->service->deductGiftCardBalance($code, 30.00);
        $this->assertTrue($deducted);

        $updatedGc = $this->service->getActiveGiftCard($code);
        $this->assertEquals(20.00, (float)$updatedGc['remaining_amount']);

        // Check email was sent to recipient
        $this->assertCount(1, $this->emailService->sentEmails);
        $this->assertEquals('giftcard', $this->emailService->sentEmails[0]['type']);
        $this->assertEquals('recipient@test.com', $this->emailService->sentEmails[0]['to']);
        $this->assertEquals($code, $this->emailService->sentEmails[0]['code']);
        $this->assertEquals(50.00, $this->emailService->sentEmails[0]['amount']);
    }

    public function testFulfillLicenseKeys() {
        // Insert digital license key product
        $productId = 10003;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type) VALUES (?, 'AntiVirus Key', 'antivirus-key', 'AVKEY', 29.99, 1, 1, 'license')")
                 ->execute([$productId]);

        // Seed some license keys in pool
        $this->db->prepare("INSERT INTO product_license_keys (product_id, license_key, is_assigned) VALUES (?, 'POOL-KEY-1111', 0)")
                 ->execute([$productId]);
        $this->db->prepare("INSERT INTO product_license_keys (product_id, license_key, is_assigned) VALUES (?, 'POOL-KEY-2222', 0)")
                 ->execute([$productId]);

        // Create mock Order
        $order = new Order(new NullLogger());
        $order->id = 5003;
        $order->customer_email = 'lic@test.com';
        $order->customer_name = 'Alex Key';
        $order->user_id = null;

        // Create mock OrderItem asking for 3 keys (2 from pool, 1 fallback generated)
        $item = new OrderItem(new NullLogger());
        $item->id = 6003;
        $item->order_id = 5003;
        $item->product_id = $productId;
        $item->variant_id = null;
        $item->quantity = 3;
        $item->unit_price = 29.99;
        $item->metadata = null;

        // Insert into database to satisfy foreign keys
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (?, ?, 'confirmed', ?, ?, ?)")
                 ->execute([$order->id, $order->user_id, 89.97, $order->customer_email, $order->customer_name]);
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, unit_price, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$item->id, $item->order_id, $item->product_id, $item->variant_id, $item->quantity, $item->unit_price, $item->metadata]);

        // Run fulfillment
        $this->service->fulfillDigitalItems($order, [$item]);

        // Check database mappings
        $stmt = $this->db->prepare("SELECT * FROM order_item_licenses WHERE order_item_id = ?");
        $stmt->execute([$item->id]);
        $allocated = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertEquals(3, count($allocated));
        $this->assertEquals('POOL-KEY-1111', $allocated[0]['license_key']);
        $this->assertEquals('POOL-KEY-2222', $allocated[1]['license_key']);
        $this->assertStringContainsString('LIC-', $allocated[2]['license_key']); // fallback auto-generated

        // Verify pool keys are marked assigned
        $stmtPool = $this->db->prepare("SELECT * FROM product_license_keys WHERE product_id = ? AND is_assigned = 1");
        $stmtPool->execute([$productId]);
        $assigned = $stmtPool->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEquals(2, count($assigned));

        // Check email was sent
        $this->assertCount(1, $this->emailService->sentEmails);
        $this->assertEquals('licenses', $this->emailService->sentEmails[0]['type']);
        $this->assertEquals('lic@test.com', $this->emailService->sentEmails[0]['to']);
        $this->assertEquals(3, count($this->emailService->sentEmails[0]['licenses']));
    }

    public function testFulfillMembershipRoleUpgrade() {
        // Insert digital membership product
        $productId = 10004;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, granted_role) VALUES (?, 'VIP Membership', 'vip-member', 'VIPMEM', 99.00, 1, 1, 'membership', 'vip')")
                 ->execute([$productId]);

        // Insert standard customer user
        $userId = 7001;
        $this->db->prepare("INSERT INTO users (id, name, email, password_hash, role) VALUES (?, 'Standard Joe', 'joe@test.com', 'pwd', 'customer')")
                 ->execute([$userId]);

        // Create mock Order
        $order = new Order(new NullLogger());
        $order->id = 5004;
        $order->customer_email = 'joe@test.com';
        $order->customer_name = 'Standard Joe';
        $order->user_id = $userId;

        // Create mock OrderItem
        $item = new OrderItem(new NullLogger());
        $item->id = 6004;
        $item->order_id = 5004;
        $item->product_id = $productId;
        $item->variant_id = null;
        $item->quantity = 1;
        $item->unit_price = 99.00;
        $item->metadata = null;

        // Insert into database to satisfy foreign keys
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (?, ?, 'confirmed', ?, ?, ?)")
                 ->execute([$order->id, $order->user_id, 99.00, $order->customer_email, $order->customer_name]);
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, unit_price, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$item->id, $item->order_id, $item->product_id, $item->variant_id, $item->quantity, $item->unit_price, $item->metadata]);

        // Run fulfillment
        $this->service->fulfillDigitalItems($order, [$item]);

        // Check user table updated
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userRole = $stmt->fetchColumn();

        $this->assertEquals('vip', $userRole);
    }

    public function testAdminRoleIsNotOverriddenByMembershipPurchase() {
        $userRepository = new UserRepository($this->db, new NullLogger());
        
        // Insert digital membership product
        $productId = 10015;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, granted_role) VALUES (?, 'VIP Membership', 'admin-test-vip', 'ADM-VIP', 99.00, 1, 1, 'membership', 'vip')")
                 ->execute([$productId]);

        // Insert admin user
        $userId = 7015;
        $this->db->prepare("INSERT INTO users (id, name, email, password_hash, role) VALUES (?, 'Admin User', 'admin-test@test.com', 'pwd', 'admin')")
                 ->execute([$userId]);

        // Create mock Order
        $order = new Order(new NullLogger());
        $order->id = 5015;
        $order->customer_email = 'admin-test@test.com';
        $order->customer_name = 'Admin User';
        $order->user_id = $userId;

        // Create mock OrderItem
        $item = new OrderItem(new NullLogger());
        $item->id = 6015;
        $item->order_id = 5015;
        $item->product_id = $productId;
        $item->variant_id = null;
        $item->quantity = 1;
        $item->unit_price = 99.00;
        $item->metadata = null;

        // Insert into database to satisfy foreign keys
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (?, ?, 'confirmed', ?, ?, ?)")
                 ->execute([$order->id, $order->user_id, 99.00, $order->customer_email, $order->customer_name]);
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, unit_price, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$item->id, $item->order_id, $item->product_id, $item->variant_id, $item->quantity, $item->unit_price, $item->metadata]);

        // Run fulfillment
        $this->service->fulfillDigitalItems($order, [$item]);

        // Verify role was NOT updated
        $user = $userRepository->findById($userId);
        $this->assertEquals('admin', $user->role, "Admin role should NOT be changed to 'vip'");
    }

    public function testFulfillEventTickets() {
        // Insert ticket product
        $productId = 10005;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type) VALUES (?, 'Concert Ticket', 'concert', 'TKTCON', 15.00, 1, 1, 'event_ticket')")
                 ->execute([$productId]);

        // Create mock Order
        $order = new Order(new NullLogger());
        $order->id = 5005;
        $order->customer_email = 'concert@test.com';
        $order->customer_name = 'Concert Goer';
        $order->user_id = null;

        // Create mock OrderItem asking for 2 tickets
        $item = new OrderItem(new NullLogger());
        $item->id = 6005;
        $item->order_id = 5005;
        $item->product_id = $productId;
        $item->variant_id = null;
        $item->quantity = 2;
        $item->unit_price = 15.00;
        $item->metadata = null;

        // Insert into database to satisfy foreign keys
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (?, ?, 'confirmed', ?, ?, ?)")
                 ->execute([$order->id, $order->user_id, 30.00, $order->customer_email, $order->customer_name]);
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, variant_id, quantity, unit_price, metadata) VALUES (?, ?, ?, ?, ?, ?, ?)")
                 ->execute([$item->id, $item->order_id, $item->product_id, $item->variant_id, $item->quantity, $item->unit_price, $item->metadata]);

        // Run fulfillment
        $this->service->fulfillDigitalItems($order, [$item]);

        // Check customer_tickets table
        $stmt = $this->db->prepare("SELECT * FROM customer_tickets WHERE order_item_id = ?");
        $stmt->execute([$item->id]);
        $tickets = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertEquals(2, count($tickets));
        $this->assertNull($tickets[0]['user_id']);
        $this->assertStringContainsString('TKT-', $tickets[0]['ticket_code']);

        // Check email was sent
        $this->assertCount(1, $this->emailService->sentEmails);
        $this->assertEquals('tickets', $this->emailService->sentEmails[0]['type']);
        $this->assertEquals('concert@test.com', $this->emailService->sentEmails[0]['to']);
        $this->assertEquals(2, count($this->emailService->sentEmails[0]['tickets']));
    }

    public function testVirtualProductStockBehavior() {
        // Insert a virtual product with 0 stock
        $productId = 10010;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, stock) VALUES (?, 'Virtual stock item', 'virtual-stock', 'VIRTSTOCK', 5.00, 1, 1, 'license', 0)")
                 ->execute([$productId]);

        $logger = new NullLogger();
        $productRepository = new ProductRepository($this->db, $logger);
        
        $product = $productRepository->findById($productId);
        $this->assertNotNull($product);
        $this->assertTrue((bool)$product->is_virtual);
        $this->assertEquals(999999, $product->getAvailableStock());
        $this->assertFalse($product->isLowStock(10));

        // Ensure it is excluded from low stock counts
        $lowStockCount = $productRepository->countLowStock(10);
        // Since we had 0 stock, standard items would count. Our virtual item should not count.
        // Let's assert that the virtual item is not in low stock list
        $lowStockItems = $productRepository->getLowStock(10);
        foreach ($lowStockItems as $item) {
            $this->assertTrue($productId !== $item->id);
        }
    }

    public function testVirtualProductStockDecrementBypass() {
        // Insert virtual product with 0 stock
        $productId = 10011;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, stock) VALUES (?, 'Virtual stock decr', 'virtual-stock-decr', 'VIRTSTOCKDECR', 5.00, 1, 1, 'license', 0)")
                 ->execute([$productId]);

        $logger = new NullLogger();
        $productRepository = new ProductRepository($this->db, $logger);
        $orderRepository = new \App\Repositories\OrderRepository($this->db, $logger);

        $product = $productRepository->findById($productId);
        
        // We use order repository's create or insert order items and execute decrement manually, 
        // or call create() directly. Let's see how create works by calling orderRepository->create.
        $items = [
            [
                'product' => $product,
                'variant' => null,
                'qty' => 2,
                'unit_price' => 5.00,
                'vat_amount' => 0.00
            ]
        ];

        $orderData = [
            'user_id' => null,
            'customer_name' => 'Test User',
            'customer_email' => 'test@test.com',
            'total' => 10.00,
            'total_vat_amount' => 0.00,
            'shipping_address' => '123 Test St',
            'notes' => 'Test Notes',
            'delivery_method' => 'Standard',
            'delivery_cost' => 0.00
        ];
        
        // Since create handles transaction/insertion, we can call it.
        $this->db->beginTransaction();
        try {
            $createdOrderId = $orderRepository->create($orderData, $items);
            
            // Check stock of the product afterwards - it should still be 0, not -2!
            $productAfter = $productRepository->findById($productId);
            $this->assertEquals(0, $productAfter->stock);
            
            // Now let's replenish stock (order cancellation / refund)
            $orderItem = new \App\Models\OrderItem($logger);
            $orderItem->product_id = $productId;
            $orderItem->variant_id = null;
            $orderItem->quantity = 2;
            
            $orderRepository->replenishStock([$orderItem]);
            
            // Stock should still be 0!
            $productAfterReplenish = $productRepository->findById($productId);
            $this->assertEquals(0, $productAfterReplenish->stock);
            
        } finally {
            $this->db->rollBack();
        }
    }
}

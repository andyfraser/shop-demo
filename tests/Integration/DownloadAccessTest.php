<?php

namespace Tests\Integration;

use Tests\TestCase;
use Tests\RequestSimulation;
use App\Core\Responses\FileResponse;
use App\Core\Responses\RedirectResponse;
use App\Models\Order;

class DownloadAccessTest extends TestCase {
    use RequestSimulation;

    private \PDO $db;

    public function setUp() {
        $this->setupApp();
        $this->db = $this->container->get(\PDO::class);
        
        $this->db->exec("DELETE FROM customer_downloads");
        $this->db->exec("DELETE FROM order_items");
        $this->db->exec("DELETE FROM orders");
        $this->db->exec("DELETE FROM users WHERE id >= 90");
        $this->db->exec("DELETE FROM products WHERE id >= 30000");
    }

    public function testAnonymousUserCannotDownload() {
        // 1. Create a dummy file to download
        $filePath = 'storage/downloads/test_file.txt';
        $fullPath = __DIR__ . '/../../' . $filePath;
        if (!is_dir(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0777, true);
        }
        file_put_contents($fullPath, 'Confidential Content');

        // 2. Insert a virtual product with the file path
        $productId = 30001;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, file_path, stock) VALUES (?, 'Digital Product', 'digital-prod', 'DIGI1', 10.00, 1, 1, 'file', ?, 0)")
                 ->execute([$productId, $filePath]);

        // 3. Create an order and order item
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (7001, 1, 'confirmed', 10.00, 'admin@example.com', 'Admin User')")
                 ->execute();
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, quantity, unit_price) VALUES (8001, 7001, 30001, 1, 10.00)")
                 ->execute();

        // 4. Generate a download token
        $token = 'test-token-123';
        $this->db->prepare("INSERT INTO customer_downloads (order_item_id, product_id, download_token, download_count) VALUES (8001, 30001, ?, 0)")
                 ->execute([$token]);

        // 5. Try to download ANONYMOUSLY
        $response = $this->get('/download/' . $token, []);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaders()['Location'] ?? '';
        $this->assertStringContainsString('/login', $location);

        // Check that redirect_after_login is set in session
        $this->assertEquals('/download/' . $token, $_SESSION['redirect_after_login']);
    }

    public function testPostLoginRedirection() {
        $productId = 30001;
        $this->db->prepare("INSERT OR IGNORE INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, file_path, stock) VALUES (?, 'Digital Product', 'digital-prod', 'DIGI1', 10.00, 1, 1, 'file', 'path', 0)")
                 ->execute([$productId]);

        $token = 'post-login-token';
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (7010, 2, 'confirmed', 10.00, 'jane@example.com', 'Jane Smith')")
                 ->execute();
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, quantity, unit_price) VALUES (8010, 7010, 30001, 1, 10.00)")
                 ->execute();
        $this->db->prepare("INSERT INTO customer_downloads (user_id, order_item_id, product_id, download_token, download_count) VALUES (2, 8010, 30001, ?, 0)")
                 ->execute([$token]);

        $session = [
            'redirect_after_login' => '/download/' . $token,
            'csrf_token' => 'test_token'
        ];

        // Mock password for Jane Smith (id=2)
        $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = 2")
                 ->execute([password_hash('password123', PASSWORD_DEFAULT)]);

        $postData = [
            'email' => 'jane@example.com',
            'password' => 'password123',
            'csrf_token' => 'test_token'
        ];

        $response = $this->post('/login', $postData, $session);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/download/' . $token, $response->getHeaders()['Location']);
    }

    public function testUserCannotDownloadOtherUsersFile() {
        // 1. Create a dummy file
        $filePath = 'storage/downloads/test_file_2.txt';
        $fullPath = __DIR__ . '/../../' . $filePath;
        file_put_contents($fullPath, 'Secret Content');

        // 2. Insert product
        $productId = 30002;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, file_path, stock) VALUES (?, 'Other Product', 'other-prod', 'DIGI2', 10.00, 1, 1, 'file', ?, 0)")
                 ->execute([$productId, $filePath]);

        // 3. Create an order for User 1
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (7002, 1, 'confirmed', 10.00, 'admin@example.com', 'Admin User')")
                 ->execute();
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, quantity, unit_price) VALUES (8002, 7002, 30002, 1, 10.00)")
                 ->execute();

        // 4. Generate token bound to User 1
        $token = 'user1-token';
        $this->db->prepare("INSERT INTO customer_downloads (user_id, order_item_id, product_id, download_token, download_count) VALUES (1, 8002, 30002, ?, 0)")
                 ->execute([$token]);

        // 5. Try to download as User 2 (Jane Smith, id=2)
        $session = [
            'user' => ['id' => 2, 'email' => 'jane@example.com']
        ];
        $response = $this->get('/download/' . $token, $session);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $location = $response->getHeaders()['Location'] ?? '';
        $this->assertStringContainsString('/account', $location);
    }

    public function testGuestCanDownloadIfEmailMatches() {
        // 1. Create a dummy file
        $filePath = 'storage/downloads/test_file_3.txt';
        $fullPath = __DIR__ . '/../../' . $filePath;
        file_put_contents($fullPath, 'Guest Content');

        // 2. Insert product
        $productId = 30003;
        $this->db->prepare("INSERT INTO products (id, name, slug, sku, price, active, is_virtual, virtual_type, file_path, stock) VALUES (?, 'Guest Product', 'guest-prod', 'DIGI3', 10.00, 1, 1, 'file', ?, 0)")
                 ->execute([$productId, $filePath]);

        // 3. Create an order for a GUEST (user_id = NULL)
        $guestEmail = 'guest@example.com';
        $this->db->prepare("INSERT INTO orders (id, user_id, status, total, customer_email, customer_name) VALUES (7003, NULL, 'confirmed', 10.00, ?, 'Guest User')")
                 ->execute([$guestEmail]);
        $this->db->prepare("INSERT INTO order_items (id, order_id, product_id, quantity, unit_price) VALUES (8003, 7003, 30003, 1, 10.00)")
                 ->execute();

        // 4. Generate token (not bound to user_id)
        $token = 'guest-token';
        $this->db->prepare("INSERT INTO customer_downloads (user_id, order_item_id, product_id, download_token, download_count) VALUES (NULL, 8003, 30003, ?, 0)")
                 ->execute([$token]);

        // 5. Insert user into DB so they can be "logged in"
        $this->db->prepare("INSERT INTO users (id, name, email, password_hash, role, is_verified) VALUES (99, 'Guest Registered', ?, 'hash', 'customer', 1)")
                 ->execute([$guestEmail]);

        // 6. Try to download as a user who REGISTERED with the guest email
        $session = [
            'user' => ['id' => 99, 'email' => $guestEmail]
        ];
        $response = $this->get('/download/' . $token, $session);

        $this->assertInstanceOf(FileResponse::class, $response);
    }

    public function tearDown() {
        $testFiles = [
            __DIR__ . '/../../storage/downloads/test_file.txt',
            __DIR__ . '/../../storage/downloads/test_file_2.txt',
            __DIR__ . '/../../storage/downloads/test_file_3.txt'
        ];
        foreach ($testFiles as $f) {
            if (file_exists($f)) unlink($f);
        }
    }
}

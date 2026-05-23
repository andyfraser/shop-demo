<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\RequestSimulation;
use App\Core\Database;
use App\Models\Order;

class GuestOrderLookupTest extends TestCase {
    use RequestSimulation;

    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        // Clean order tables so we have a fresh test environment
        $this->db->exec("DELETE FROM order_items");
        $this->db->exec("DELETE FROM orders");
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    public function testGuestOrderDirectConfirmFailsWithoutAuthorization() {
        // 1. Create a guest order in the database
        $stmt = $this->db->prepare(
            "INSERT INTO orders (id, user_id, customer_name, customer_email, total, total_vat_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([12345, null, 'Guest Tester', 'guest@example.com', 100.0, 10.0, Order::STATUS_PENDING]);

        // 2. Attempting to directly view the order details without authentication or session permission
        $response = $this->get('/order/confirm?id=12345', [], [], ['id' => 12345]);
        
        // Should redirect to root '/'
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/', $response->getHeaders()['Location'] ?? null);
    }

    public function testOrderLookupFailsWithWrongCredentials() {
        // 1. Create a guest order in the database
        $stmt = $this->db->prepare(
            "INSERT INTO orders (id, user_id, customer_name, customer_email, total, total_vat_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([54321, null, 'Guest User', 'guest@example.com', 150.0, 15.0, Order::STATUS_CONFIRMED]);

        // 2. Submit POST /order/lookup with WRONG email
        $response = $this->post('/order/lookup', [
            'order_id' => '54321',
            'email' => 'wrong@example.com',
            'csrf_token' => 'my_token'
        ], [
            'csrf_token' => 'my_token'
        ]);

        // Should return HTML response (HTTP 200) rendering the form with error messages
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('No order found matching the provided Order ID and Email address.', $response->getContent());
    }

    public function testOrderLookupSucceedsWithCorrectCredentials() {
        // 1. Create a guest order in the database
        $stmt = $this->db->prepare(
            "INSERT INTO orders (id, user_id, customer_name, customer_email, total, total_vat_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([88888, null, 'Happy Guest', 'happy@example.com', 200.0, 20.0, Order::STATUS_PENDING]);

        // 2. Submit POST /order/lookup with correct credentials
        $response = $this->post('/order/lookup', [
            'order_id' => '88888',
            'email' => 'happy@example.com',
            'csrf_token' => 'my_token'
        ], [
            'csrf_token' => 'my_token'
        ]);

        // Should redirect (HTTP 302) to the order confirmation/details page
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/order/confirm?id=88888', $response->getHeaders()['Location'] ?? null);

        // 3. Subsequent GET /order/confirm?id=88888 should load successfully with the session flag set
        $confirmResponse = $this->get('/order/confirm?id=88888', [
            'viewed_guest_orders' => [88888 => true]
        ], [], ['id' => 88888]);

        $this->assertEquals(200, $confirmResponse->getStatusCode());
        $this->assertStringContainsString('Order Details', $confirmResponse->getContent());
        $this->assertStringContainsString('Happy Guest', $confirmResponse->getContent());
        $this->assertStringContainsString('happy@example.com', $confirmResponse->getContent());
    }

    public function testDirectEmailLinkSucceedsAndRedirects() {
        // 1. Create a guest order in the database
        $stmt = $this->db->prepare(
            "INSERT INTO orders (id, user_id, customer_name, customer_email, total, total_vat_amount, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([99999, null, 'Direct Guest', 'direct@example.com', 300.0, 30.0, Order::STATUS_PENDING]);

        // 2. Visit GET /order/lookup with direct id and email parameters
        $response = $this->simulateRequest('GET', '/order/lookup', [], [], [], ['id' => '99999', 'email' => 'direct@example.com']);

        // Should authorize and redirect directly to /order/confirm?id=99999
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/order/confirm?id=99999', $response->getHeaders()['Location'] ?? null);
    }
}

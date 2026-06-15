<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Commands\RecoverCartsCommand;
use App\Core\Database;
use App\Events\AbandonCartDetected;
use App\Core\Events\Event;
use App\Core\Events\EventDispatcherInterface;
use App\Core\Events\ListenerInterface;
use Tests\NullLogger;
use PDO;

class RecoverCartsMockEventDispatcher implements EventDispatcherInterface {
    public array $dispatched = [];

    public function dispatch(Event $event): Event {
        $this->dispatched[] = $event;
        return $event;
    }

    public function addListener(string $eventName, callable|ListenerInterface|string $listener, int $priority = 0): void {}
}

class RecoverCartsCommandTest extends TestCase {
    private PDO $db;

    public function setUp(): void {
        $this->db = Database::getConnection();
        // Clear carts first or set up a clean state for testing.
        $this->db->exec("DELETE FROM cart_items");
        $this->db->exec("DELETE FROM carts");
    }

    public function testRecoverCartsDispatchesEvent(): void {
        // Create an abandoned cart (older than 24 hours) for user ID 1 (seeded admin user)
        $pastTime = date('Y-m-d H:i:s', strtotime('-25 hours'));
        
        $stmt = $this->db->prepare("INSERT INTO carts (user_id, session_id, last_activity, recovery_email_sent_at) VALUES (?, ?, ?, NULL)");
        $stmt->execute([1, 'dummy-session', $pastTime]);
        $cartId = $this->db->lastInsertId();

        // Add an item to the cart (since command filters carts with items)
        $stmt = $this->db->prepare("INSERT INTO cart_items (cart_id, product_id, qty) VALUES (?, ?, ?)");
        $stmt->execute([$cartId, 1, 2]);

        $dispatcher = new RecoverCartsMockEventDispatcher();
        $cartRepository = new \App\Repositories\CartRepository($this->db);
        $command = new RecoverCartsCommand($cartRepository, $dispatcher, new NullLogger());

        ob_start();
        $exitCode = $command->execute();
        $output = ob_get_clean();

        $this->assertEquals(0, $exitCode);
        $this->assertCount(1, $dispatcher->dispatched);
        
        $event = $dispatcher->dispatched[0];
        $this->assertInstanceOf(AbandonCartDetected::class, $event);
        $this->assertEquals((int)$cartId, $event->cartId);
        $this->assertEquals('admin@shop.local', $event->email);

        // Verify recovery_email_sent_at has been updated in database
        $checkStmt = $this->db->prepare("SELECT recovery_email_sent_at FROM carts WHERE id = ?");
        $checkStmt->execute([$cartId]);
        $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($row['recovery_email_sent_at']);
    }
}

<?php

namespace App\Commands;

use App\Core\Events\EventDispatcherInterface;
use App\Events\AbandonCartDetected;
use Psr\Log\LoggerInterface;
use PDO;

class RecoverCartsCommand implements CommandInterface {
    public function __construct(
        private PDO $db,
        private EventDispatcherInterface $eventDispatcher,
        private ?LoggerInterface $logger = null
    ) {}

    public function getName(): string {
        return 'recover-carts';
    }

    public function getDescription(): string {
        return 'Sends recovery emails to abandoned carts.';
    }

    public function getSchedule(): ?string {
        return 'hourly';
    }

    public function execute(): int {
        $threshold = date('Y-m-d H:i:s', strtotime('-24 hours'));

        $sql = "
            SELECT c.id, u.email, u.name 
            FROM carts c
            JOIN users u ON c.user_id = u.id
            WHERE c.last_activity < ?
                AND c.recovery_email_sent_at IS NULL
                AND EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$threshold]);
        $abandonedCarts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = count($abandonedCarts);
        echo "Found {$count} abandoned carts.\n";
        if ($this->logger) {
            $this->logger->info("RecoverCartsCommand: Found {count} abandoned carts to process.", ['count' => $count]);
        }

        foreach ($abandonedCarts as $cart) {
            echo "Queueing recovery email to {$cart['email']}... ";
            
            try {
                // Update timestamp first to prevent double queueing
                $update = $this->db->prepare("UPDATE carts SET recovery_email_sent_at = ? WHERE id = ?");
                $update->execute([date('Y-m-d H:i:s'), $cart['id']]);

                // Dispatch event (which will be queued via RecoverCartListener implementing ShouldQueue)
                $event = new AbandonCartDetected($cart['id'], $cart['email'], $cart['name']);
                $this->eventDispatcher->dispatch($event);
                
                echo "Done.\n";
                if ($this->logger) {
                    $this->logger->info("RecoverCartsCommand: Queued recovery email for {email}", ['email' => $cart['email']]);
                }
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
                if ($this->logger) {
                    $this->logger->error("RecoverCartsCommand: Error queueing cart {id} for {email}: {error}", [
                        'id' => $cart['id'],
                        'email' => $cart['email'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return 0;
    }
}

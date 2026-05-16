<?php

namespace App\Commands;

use App\Services\EmailServiceInterface;
use Psr\Log\LoggerInterface;
use PDO;

class RecoverCartsCommand implements CommandInterface {
    public function __construct(
        private PDO $db,
        private EmailServiceInterface $emailService,
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
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $sql = "
                SELECT c.id, u.email, u.name 
                FROM carts c
                JOIN users u ON c.user_id = u.id
                WHERE c.last_activity < DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND c.recovery_email_sent_at IS NULL
                  AND EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id)
            ";
        } else {
            $sql = "
                SELECT c.id, u.email, u.name 
                FROM carts c
                JOIN users u ON c.user_id = u.id
                WHERE c.last_activity < datetime('now', '-24 hours')
                  AND c.recovery_email_sent_at IS NULL
                  AND EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id = c.id)
            ";
        }

        $stmt = $this->db->query($sql);
        $abandonedCarts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $count = count($abandonedCarts);
        echo "Found {$count} abandoned carts.\n";
        if ($this->logger) {
            $this->logger->info("RecoverCartsCommand: Found {count} abandoned carts to process.", ['count' => $count]);
        }

        foreach ($abandonedCarts as $cart) {
            echo "Sending recovery email to {$cart['email']}... ";
            
            try {
                $success = $this->emailService->sendAbandonedCartEmail($cart['email'], $cart['name']);
                
                if ($success) {
                    $update = $this->db->prepare("UPDATE carts SET recovery_email_sent_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $update->execute([$cart['id']]);
                    echo "Done.\n";
                    if ($this->logger) {
                        $this->logger->info("RecoverCartsCommand: Recovery email sent to {email}", ['email' => $cart['email']]);
                    }
                } else {
                    echo "Failed (mail() returned false).\n";
                    if ($this->logger) {
                        $this->logger->warning("RecoverCartsCommand: Failed to send recovery email to {email}", ['email' => $cart['email']]);
                    }
                }
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
                if ($this->logger) {
                    $this->logger->error("RecoverCartsCommand: Error processing cart {id} for {email}: {error}", [
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

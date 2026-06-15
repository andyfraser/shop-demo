<?php

namespace App\Commands;

use App\Repositories\CartRepositoryInterface;
use App\Core\Events\EventDispatcherInterface;
use App\Events\AbandonCartDetected;
use Psr\Log\LoggerInterface;

class RecoverCartsCommand implements CommandInterface {
    public function __construct(
        private CartRepositoryInterface $cartRepository,
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

        $abandonedCarts = $this->cartRepository->findAbandonedCarts($threshold);

        $count = count($abandonedCarts);
        echo "Found {$count} abandoned carts.\n";
        if ($this->logger) {
            $this->logger->info("RecoverCartsCommand: Found {count} abandoned carts to process.", ['count' => $count]);
        }

        foreach ($abandonedCarts as $cart) {
            echo "Queueing recovery email to {$cart['email']}... ";
            
            try {
                // Update timestamp first to prevent double queueing
                $this->cartRepository->updateRecoveryEmailSentAt($cart['id'], date('Y-m-d H:i:s'));

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

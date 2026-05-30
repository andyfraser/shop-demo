<?php

namespace App\Commands;

use PDO;
use Psr\Log\LoggerInterface;

class RememberTokenCleanupCommand implements CommandInterface {
    public function __construct(
        private PDO $db,
        private ?LoggerInterface $logger = null
    ) {}

    public function getName(): string {
        return 'auth:cleanup-tokens';
    }

    public function getDescription(): string {
        return 'Cleans up expired remember-me tokens from the database.';
    }

    public function getSchedule(): ?string {
        return 'daily';
    }

    public function execute(): int {
        echo "Cleaning up expired remember tokens...\n";
        
        $now = time();
        $stmt = $this->db->prepare("DELETE FROM remember_tokens WHERE expires_at < ?");
        $stmt->execute([$now]);
        
        $deleted = $stmt->rowCount();
        echo "Deleted {$deleted} expired remember tokens.\n";
        
        if ($this->logger) {
            $this->logger->info("RememberTokenCleanupCommand: Deleted {count} expired remember tokens.", [
                'count' => $deleted
            ]);
        }
        
        return 0;
    }
}

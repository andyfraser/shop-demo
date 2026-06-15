<?php

namespace App\Commands;

use App\Repositories\AuthRepositoryInterface;
use Psr\Log\LoggerInterface;

class RememberTokenCleanupCommand implements CommandInterface {
    public function __construct(
        private AuthRepositoryInterface $authRepository,
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
        $deleted = $this->authRepository->cleanupExpiredRememberTokens($now);
        
        echo "Deleted {$deleted} expired remember tokens.\n";
        
        if ($this->logger) {
            $this->logger->info("RememberTokenCleanupCommand: Deleted {count} expired remember tokens.", [
                'count' => $deleted
            ]);
        }
        
        return 0;
    }
}

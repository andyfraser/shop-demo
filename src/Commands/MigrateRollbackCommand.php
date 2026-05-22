<?php

namespace App\Commands;

use App\Services\MigrationServiceInterface;
use App\Core\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use Exception;

class MigrateRollbackCommand implements CommandInterface {
    public function __construct(
        private MigrationServiceInterface $migrationService,
        private CacheInterface $cache,
        private ?LoggerInterface $logger = null
    ) {}

    public function getName(): string {
        return 'migrate:rollback';
    }

    public function getDescription(): string {
        return 'Rolls back the last applied database migration.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        try {
            if ($this->logger) {
                $this->logger->warning("Starting database migration rollback...");
            }

            if ($this->migrationService->rollbackMigration()) {
                echo "Rollback successful.\n";
                if ($this->logger) {
                    $this->logger->info("Database migration rollback successful.");
                }

                // Clear cache after successful rollback
                $this->cache->clear();
                echo "Cache cleared.\n";
            } else {
                echo "No migrations found to rollback.\n";
                if ($this->logger) {
                    $this->logger->info("No migrations found to rollback.");
                }
            }
            return 0;
        } catch (Exception $e) {
            echo "Failed!\n";
            echo "Error: " . $e->getMessage() . "\n";
            if ($this->logger) {
                $this->logger->error("Database migration rollback failed: {error}", ['error' => $e->getMessage()]);
            }
            return 1;
        }
    }
}

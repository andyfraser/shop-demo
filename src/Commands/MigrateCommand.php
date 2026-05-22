<?php

namespace App\Commands;

use App\Services\MigrationServiceInterface;
use App\Core\Cache\CacheInterface;
use Psr\Log\LoggerInterface;
use Exception;

class MigrateCommand implements CommandInterface {
    public function __construct(
        private MigrationServiceInterface $migrationService,
        private CacheInterface $cache,
        private ?LoggerInterface $logger = null
    ) {}

    public function getName(): string {
        return 'migrate';
    }

    public function getDescription(): string {
        return 'Applies all pending database migrations.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        try {
            if ($this->logger) {
                $this->logger->info("Starting database migrations...");
            }

            $applied = $this->migrationService->applyMigrations();
            
            if (empty($applied)) {
                echo "No new migrations to apply.\n";
                if ($this->logger) {
                    $this->logger->info("No new migrations to apply.");
                }
            } else {
                foreach ($applied as $name) {
                    echo "Applied migration: {$name}\n";
                    if ($this->logger) {
                        $this->logger->info("Applied migration: {name}", ['name' => $name]);
                    }
                }
                echo "\nAll migrations applied successfully.\n";
                if ($this->logger) {
                    $this->logger->info("All migrations applied successfully. Count: {count}", ['count' => count($applied)]);
                }

                // Clear cache after successful migration
                $this->cache->clear();
                echo "Cache cleared.\n";
            }
            return 0;
        } catch (Exception $e) {
            echo "Failed!\n";
            echo "Error: " . $e->getMessage() . "\n";
            if ($this->logger) {
                $this->logger->error("Database migrations failed: {error}", ['error' => $e->getMessage()]);
            }
            return 1;
        }
    }
}

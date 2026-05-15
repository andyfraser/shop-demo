<?php

namespace App\Commands;

use App\Services\DatabaseSeedServiceInterface;
use Psr\Log\LoggerInterface;
use Exception;

class SeedCommand implements CommandInterface {
    public function __construct(
        private DatabaseSeedServiceInterface $seedService,
        private ?LoggerInterface $logger = null
    ) {}

    public function getName(): string {
        return 'db:seed';
    }

    public function getDescription(): string {
        return 'Seeds the database with initial data.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        try {
            echo "Seeding database...\n";
            if ($this->logger) {
                $this->logger->info("Starting database seeding...");
            }
            
            $this->seedService->seed();
            
            echo "Database seeded successfully!\n";
            if ($this->logger) {
                $this->logger->info("Database seeded successfully.");
            }
            return 0;
        } catch (Exception $e) {
            echo "Failed to seed database!\n";
            echo "Error: " . $e->getMessage() . "\n";
            if ($this->logger) {
                $this->logger->error("Database seeding failed: {error}", ['error' => $e->getMessage()]);
            }
            return 1;
        }
    }
}

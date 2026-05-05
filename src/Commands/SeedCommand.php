<?php

namespace App\Commands;

use App\Services\DatabaseSeedServiceInterface;
use Exception;

class SeedCommand implements CommandInterface {
    public function __construct(private DatabaseSeedServiceInterface $seedService) {}

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
            $this->seedService->seed();
            echo "Database seeded successfully!\n";
            return 0;
        } catch (Exception $e) {
            echo "Failed to seed database!\n";
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }
}

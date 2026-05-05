<?php

namespace App\Commands;

use App\Services\MigrationServiceInterface;
use Exception;

class MigrateCommand implements CommandInterface {
    public function __construct(private MigrationServiceInterface $migrationService) {}

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
            $applied = $this->migrationService->applyMigrations();
            
            if (empty($applied)) {
                echo "No new migrations to apply.\n";
            } else {
                foreach ($applied as $name) {
                    echo "Applied migration: {$name}\n";
                }
                echo "\nAll migrations applied successfully.\n";
            }
            return 0;
        } catch (Exception $e) {
            echo "Failed!\n";
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }
}

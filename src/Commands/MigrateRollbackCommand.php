<?php

namespace App\Commands;

use App\Services\MigrationServiceInterface;
use Exception;

class MigrateRollbackCommand implements CommandInterface {
    public function __construct(private MigrationServiceInterface $migrationService) {}

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
            if ($this->migrationService->rollbackMigration()) {
                echo "Rollback successful.\n";
            } else {
                echo "No migrations found to rollback.\n";
            }
            return 0;
        } catch (Exception $e) {
            echo "Failed!\n";
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }
}

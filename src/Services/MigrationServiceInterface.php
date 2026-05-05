<?php
namespace App\Services;

interface MigrationServiceInterface {
    public function applyMigrations(): array;
    public function rollbackMigration(): bool;
    public function getAppliedMigrations(): array;
    public function getAvailableMigrations(): array;
}

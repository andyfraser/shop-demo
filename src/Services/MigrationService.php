<?php
namespace App\Services;

use App\Repositories\MigrationRepositoryInterface;
use Exception;

class MigrationService implements MigrationServiceInterface {
    public function __construct(private MigrationRepositoryInterface $repository) {}

    public function applyMigrations(): array {
        $driver = DatabaseSeedService::getDriver(); // Hacky but works for now, or just get from connection
        // Actually, let's just use 'sqlite' or 'mysql' based on repository logic if possible
        // But better to just pass it in or let repo handle it.
        // For now, I'll use a local helper to get driver.
        $driver = $this->getDriver();
        $this->repository->ensureMigrationsTable($driver);

        $applied = $this->getAppliedMigrations();
        $available = $this->getAvailableMigrations();

        $results = [];
        foreach ($available as $migration) {
            if (!in_array($migration['name'], $applied)) {
                $this->apply($migration, $driver);
                $results[] = $migration['name'];
            }
        }

        return $results;
    }

    public function rollbackMigration(): bool {
        $driver = $this->getDriver();
        $this->repository->ensureMigrationsTable($driver);

        $migrationName = $this->repository->getLastMigrationName();

        if (!$migrationName) {
            return false;
        }

        $migrationsDir = __DIR__ . '/../../migrations';
        $file = $migrationsDir . '/' . $migrationName . '.php';

        if (!file_exists($file)) {
            throw new Exception("Migration file '{$file}' not found.");
        }

        $migrationInstance = require $file;
        $sql = $migrationInstance->down($driver);
        
        $this->repository->executeSql($sql, $driver);
        $this->repository->removeMigration($migrationName);

        return true;
    }

    public function getAppliedMigrations(): array {
        $driver = $this->getDriver();
        $this->repository->ensureMigrationsTable($driver);
        return $this->repository->getAppliedMigrations();
    }

    public function getAvailableMigrations(): array {
        $migrationsDir = __DIR__ . '/../../migrations';
        $migrationFiles = glob($migrationsDir . '/m*_*.php');
        sort($migrationFiles);

        $migrations = [];
        foreach ($migrationFiles as $file) {
            $migrations[] = [
                'file' => $file,
                'name' => basename($file, '.php')
            ];
        }
        return $migrations;
    }

    private function apply(array $migration, string $driver): void {
        $migrationInstance = require $migration['file'];
        $sql = $migrationInstance->up($driver);
        
        $this->repository->executeSql($sql, $driver);
        $this->repository->recordMigration($migration['name']);
    }

    private function getDriver(): string {
        return DB_CONFIG['driver'] ?? 'sqlite';
    }
}

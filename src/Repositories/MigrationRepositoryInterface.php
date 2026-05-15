<?php
namespace App\Repositories;

interface MigrationRepositoryInterface {
    public function getAppliedMigrations(): array;
    public function recordMigration(string $name): void;
    public function removeMigration(string $name): void;
    public function getLastMigrationName(): ?string;
    public function ensureMigrationsTable(string $driver): void;
    public function executeSql(string|array $sql, string $driver): void;
    public function getTables(): array;
    public function dropTable(string $tableName): void;
    public function setForeignKeyChecks(bool $enable, string $driver): void;
    public function truncateTable(string $tableName): void;
    public function insertRow(string $table, array $data): void;
    public function fetchAll(string $query, array $params = []): array;
}

<?php
namespace App\Services;

use PDO;
use PDOException;
use Exception;

class MigrationService implements MigrationServiceInterface {
    public function __construct(private PDO $db) {}

    public function applyMigrations(): array {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->ensureMigrationsTable($driver);

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
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->ensureMigrationsTable($driver);

        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 1");
        $lastMigration = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lastMigration) {
            return false;
        }

        $migrationName = $lastMigration['migration'];
        $migrationsDir = __DIR__ . '/../../migrations';
        $file = $migrationsDir . '/' . $migrationName . '.php';

        if (!file_exists($file)) {
            throw new Exception("Migration file '{$file}' not found.");
        }

        $migrationInstance = require $file;
        $sql = $migrationInstance->down($driver);
        
        $this->executeSql($sql, $driver);

        $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration = :migration");
        $stmt->execute(['migration' => $migrationName]);

        return true;
    }

    public function getAppliedMigrations(): array {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->ensureMigrationsTable($driver);
        $stmt = $this->db->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
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

    private function ensureMigrationsTable(string $driver): void {
        $sql = $driver === 'mysql' 
            ? "CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
            : "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );";

        $this->db->exec($sql);
    }

    private function apply(array $migration, string $driver): void {
        $migrationInstance = require $migration['file'];
        $sql = $migrationInstance->up($driver);
        
        $this->executeSql($sql, $driver);
        
        $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
        $stmt->execute(['migration' => $migration['name']]);
    }

    private function executeSql($sql, string $driver): void {
        if (is_string($sql)) {
            if ($driver === 'mysql') {
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $s) {
                    if (!empty($s)) {
                        try {
                            $this->db->exec($s);
                        } catch (PDOException $e) {
                            if (!$this->isIgnorableMigrationError($e, $driver)) {
                                throw $e;
                            }
                        }
                    }
                }
            } else {
                try {
                    $this->db->exec($sql);
                } catch (PDOException $e) {
                    if (!$this->isIgnorableMigrationError($e, $driver)) {
                        throw $e;
                    }
                }
            }
        } else if (is_array($sql)) {
            foreach ($sql as $s) {
                try {
                    $this->db->exec($s);
                } catch (PDOException $e) {
                    if (!$this->isIgnorableMigrationError($e, $driver)) {
                        throw $e;
                    }
                }
            }
        }
    }

    private function isIgnorableMigrationError(PDOException $e, string $driver): bool {
        if ($driver === 'mysql') {
            // 1050: Table already exists
            // 1060: Duplicate column name
            // 1061: Duplicate key name
            $mysqlErrorCode = $e->errorInfo[1] ?? 0;
            return in_array($mysqlErrorCode, [1050, 1060, 1061]);
        }

        if ($driver === 'sqlite') {
            $msg = strtolower($e->getMessage());
            return str_contains($msg, 'duplicate column name') || 
                   str_contains($msg, 'already exists');
        }

        return false;
    }
}

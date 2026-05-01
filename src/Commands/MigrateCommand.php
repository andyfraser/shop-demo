<?php

namespace App\Commands;

use PDO;
use Exception;
use PDOException;

class MigrateCommand implements CommandInterface {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

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
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Create migrations table if it doesn't exist
        $migrationsTableSql = $driver === 'mysql' 
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

        $this->db->exec($migrationsTableSql);

        // Get already applied migrations
        $stmt = $this->db->query("SELECT migration FROM migrations");
        $appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get all migration files
        $migrationsDir = __DIR__ . '/../../migrations';
        $migrationFiles = glob($migrationsDir . '/m*_*.php');
        sort($migrationFiles);

        $newMigrations = [];
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.php');
            if (!in_array($migrationName, $appliedMigrations)) {
                $newMigrations[] = ['file' => $file, 'name' => $migrationName];
            }
        }

        if (empty($newMigrations)) {
            echo "No new migrations to apply.\n";
            return 0;
        }

        foreach ($newMigrations as $migration) {
            echo "Applying migration: {$migration['name']}... ";
            
            $migrationInstance = require $migration['file'];
            
            try {
                $sql = $migrationInstance->up($driver);
                
                // Execute statements
                $this->executeSql($sql, $driver);
                
                $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
                $stmt->execute(['migration' => $migration['name']]);
                
                echo "Done.\n";
            } catch (Exception $e) {
                echo "Failed!\n";
                echo "Error: " . $e->getMessage() . "\n";
                return 1;
            }
        }

        echo "\nAll migrations applied successfully.\n";
        return 0;
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
                            if ($e->getCode() == '42000' && (str_contains($e->getMessage(), '1061') || str_contains($e->getMessage(), 'Duplicate key name'))) {
                                continue;
                            }
                            throw $e;
                        }
                    }
                }
            } else {
                try {
                    $this->db->exec($sql);
                } catch (PDOException $e) {
                    if (str_contains($e->getMessage(), 'duplicate column name')) {
                        // ignore
                    } else {
                        throw $e;
                    }
                }
            }
        } else if (is_array($sql)) {
            foreach ($sql as $s) {
                try {
                    $this->db->exec($s);
                } catch (PDOException $e) {
                    if ($driver === 'mysql' && $e->getCode() == '42000' && (str_contains($e->getMessage(), '1061') || str_contains($e->getMessage(), 'Duplicate key name'))) {
                        continue;
                    }
                    throw $e;
                }
            }
        }
    }
}

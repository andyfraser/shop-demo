<?php
namespace App\Repositories;

use PDO;
use PDOException;

class MigrationRepository implements MigrationRepositoryInterface {
    public function __construct(private PDO $db) {}

    public function getAppliedMigrations(): array {
        $stmt = $this->db->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function recordMigration(string $name): void {
        $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
        $stmt->execute(['migration' => $name]);
    }

    public function removeMigration(string $name): void {
        $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration = :migration");
        $stmt->execute(['migration' => $name]);
    }

    public function getLastMigrationName(): ?string {
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['migration'] : null;
    }

    public function ensureMigrationsTable(string $driver): void {
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

    public function executeSql(string|array $sql, string $driver): void {
        if (is_string($sql)) {
            if ($driver === 'mysql') {
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $s) {
                    if (!empty($s)) {
                        try {
                            $this->db->exec($s);
                        } catch (PDOException $e) {
                            if (!$this->isIgnorableError($e, $driver)) throw $e;
                        }
                    }
                }
            } else {
                try {
                    $this->db->exec($sql);
                } catch (PDOException $e) {
                    if (!$this->isIgnorableError($e, $driver)) throw $e;
                }
            }
        } else if (is_array($sql)) {
            foreach ($sql as $s) {
                try {
                    $this->db->exec($s);
                } catch (PDOException $e) {
                    if (!$this->isIgnorableError($e, $driver)) throw $e;
                }
            }
        }
    }

    public function getTables(): array {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        } else {
            $stmt = $this->db->query("SHOW TABLES");
        }
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function dropTable(string $tableName): void {
        $this->validateIdentifier($tableName);
        $this->db->exec("DROP TABLE IF EXISTS `{$tableName}`");
    }

    public function setForeignKeyChecks(bool $enable, string $driver): void {
        if ($driver === 'sqlite') {
            $this->db->exec('PRAGMA foreign_keys = ' . ($enable ? 'ON' : 'OFF'));
        } else {
            $this->db->exec('SET FOREIGN_KEY_CHECKS = ' . ($enable ? '1' : '0'));
        }
    }

    public function truncateTable(string $tableName): void {
        $this->validateIdentifier($tableName);
        $this->db->exec("DELETE FROM `{$tableName}`");
    }

    public function insertRow(string $table, array $data): void {
        $this->validateIdentifier($table);
        $columns = array_keys($data);
        foreach ($columns as $column) {
            $this->validateIdentifier($column);
        }

        $colList = implode('`, `', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        
        $sql = "INSERT INTO `{$table}` (`{$colList}`) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
    }

    private function validateIdentifier(string $identifier): void {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException("Invalid database identifier: {$identifier}");
        }
    }

    public function fetchAll(string $query, array $params = []): array {
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function isIgnorableError(PDOException $e, string $driver): bool {
        if ($driver === 'mysql') {
            $mysqlErrorCode = $e->errorInfo[1] ?? 0;
            return in_array($mysqlErrorCode, [1050, 1060, 1061, 1091]);
        }
        if ($driver === 'sqlite') {
            $msg = strtolower($e->getMessage());
            return str_contains($msg, 'duplicate column name') || str_contains($msg, 'already exists');
        }
        return false;
    }
}

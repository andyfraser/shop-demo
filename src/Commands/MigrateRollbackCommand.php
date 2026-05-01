<?php

namespace App\Commands;

use PDO;
use Exception;

class MigrateRollbackCommand implements CommandInterface {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

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
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Get the last applied migration
        $stmt = $this->db->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 1");
        $lastMigration = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lastMigration) {
            echo "No migrations found to rollback.\n";
            return 0;
        }

        $migrationName = $lastMigration['migration'];
        $migrationsDir = __DIR__ . '/../../migrations';
        $file = $migrationsDir . '/' . $migrationName . '.php';

        if (!file_exists($file)) {
            echo "Error: Migration file '{$file}' not found. Cannot rollback.\n";
            return 1;
        }

        echo "Rolling back migration: {$migrationName}... ";

        $migrationInstance = require $file;

        try {
            $sql = $migrationInstance->down($driver);
            
            if (is_string($sql)) {
                if ($driver === 'mysql') {
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    foreach ($statements as $s) {
                        if (!empty($s)) $this->db->exec($s);
                    }
                } else {
                    $this->db->exec($sql);
                }
            } else if (is_array($sql)) {
                foreach ($sql as $s) {
                    $this->db->exec($s);
                }
            }

            $stmt = $this->db->prepare("DELETE FROM migrations WHERE migration = :migration");
            $stmt->execute(['migration' => $migrationName]);

            echo "Done.\n";
            return 0;
        } catch (Exception $e) {
            echo "Failed!\n";
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }
}

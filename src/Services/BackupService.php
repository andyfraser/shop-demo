<?php
namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class BackupService implements BackupServiceInterface {
    public function __construct(
        private PDO $db,
        private MigrationServiceInterface $migrationService
    ) {}

    public function export(): array {
        $timestamp = date('Ymd_His');
        $tables = $this->getTables();
        $backupData = [
            'metadata' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'driver' => $this->db->getAttribute(PDO::ATTR_DRIVER_NAME),
                'version' => '1.0'
            ],
            'tables' => []
        ];

        foreach ($tables as $table) {
            $stmt = $this->db->query("SELECT * FROM `{$table}`");
            $backupData['tables'][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $json = json_encode($backupData, JSON_PRETTY_PRINT);
        $tempFile = tempnam(sys_get_temp_dir(), 'shop_bak_');
        file_put_contents($tempFile, $json);

        return [
            'path' => $tempFile,
            'mime' => 'application/json',
            'filename' => "shop_backup_{$timestamp}.json",
            'temp' => true
        ];
    }

    public function import(array $file): bool {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed with error code: " . $file['error']);
        }

        $json = file_get_contents($file['tmp_name']);
        $backupData = json_decode($json, true);

        if (!$backupData || !isset($backupData['tables'])) {
            // Check if it's an old SQL or DB file for backward compatibility?
            // User requested cross-driver, so JSON is the new standard.
            // But let's try to handle the old ones if we can, or just reject them.
            // Given the complexity of "restoring MySQL SQL to SQLite", it's better to just move forward with JSON.
            throw new Exception("Invalid backup file format. Expected portable JSON backup.");
        }

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        // 1. Reset database (drop all tables)
        $this->dropAllTables($driver);

        // 2. Re-run migrations
        $this->migrationService->applyMigrations();

        // Disable foreign key checks for import
        $this->setForeignKeyChecks(false, $driver);

        try {
            foreach ($backupData['tables'] as $table => $rows) {
                if (empty($rows)) continue;

                // 3. Truncate table (migrations might have seeded it)
                $this->db->exec("DELETE FROM `{$table}`");

                // 4. Insert data
                $columns = array_keys($rows[0]);
                $colList = implode('`, `', $columns);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                
                $sql = "INSERT INTO `{$table}` (`{$colList}`) VALUES ({$placeholders})";
                $stmt = $this->db->prepare($sql);

                foreach ($rows as $row) {
                    $stmt->execute(array_values($row));
                }
            }
        } finally {
            $this->setForeignKeyChecks(true, $driver);
        }

        return true;
    }

    private function getTables(): array {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        } else {
            $stmt = $this->db->query("SHOW TABLES");
        }
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function dropAllTables(string $driver): void {
        $tables = $this->getTables();
        $this->setForeignKeyChecks(false, $driver);
        
        foreach ($tables as $table) {
            $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
        }
        
        $this->setForeignKeyChecks(true, $driver);
    }

    private function setForeignKeyChecks(bool $enable, string $driver): void {
        if ($driver === 'sqlite') {
            $this->db->exec('PRAGMA foreign_keys = ' . ($enable ? 'ON' : 'OFF'));
        } else {
            $this->db->exec('SET FOREIGN_KEY_CHECKS = ' . ($enable ? '1' : '0'));
        }
    }
}

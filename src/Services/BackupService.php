<?php
namespace App\Services;

use App\Repositories\MigrationRepositoryInterface;
use Exception;

class BackupService implements BackupServiceInterface {
    public function __construct(
        private MigrationRepositoryInterface $repository,
        private MigrationServiceInterface $migrationService
    ) {}

    public function export(?callable $onProgress = null): array {
        $lockFile = sys_get_temp_dir() . '/demoshop_backup_restore.lock';
        file_put_contents($lockFile, 'backup');

        try {
            $timestamp = date('Ymd_His');
            $tables = $this->repository->getTables();
            $driver = $this->getDriver();

            $backupData = [
                'metadata' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'driver' => $driver,
                    'version' => '1.0'
                ],
                'tables' => []
            ];

            $totalTables = count($tables);
            $currentIndex = 0;

            foreach ($tables as $table) {
                $backupData['tables'][$table] = $this->repository->fetchAll("SELECT * FROM `{$table}`");
                $currentIndex++;
                if ($onProgress) {
                    $progress = (int)(($currentIndex / $totalTables) * 100);
                    $onProgress($progress, "Backed up table: {$table}");
                }
                // Tiny sleep to make the progress bar visible and smooth
                usleep(20000); // 20ms
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
        } finally {
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }
        }
    }

    public function import(array $file, ?callable $onProgress = null): bool {
        $lockFile = sys_get_temp_dir() . '/demoshop_backup_restore.lock';
        file_put_contents($lockFile, 'restore');
        $driver = $this->getDriver();

        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Upload failed with error code: " . $file['error']);
            }

            $json = file_get_contents($file['tmp_name']);
            $backupData = json_decode($json, true);

            // Structural pre-validation: check format before modifying anything
            if (!$backupData || !isset($backupData['tables']) || !is_array($backupData['tables'])) {
                throw new Exception("Invalid backup file format. Expected portable JSON backup.");
            }

            foreach ($backupData['tables'] as $tableName => $rows) {
                if (!is_string($tableName)) {
                    throw new Exception("Invalid backup file format. Table name must be a string.");
                }
                if (!is_array($rows)) {
                    throw new Exception("Invalid backup file format. Rows for table '{$tableName}' must be an array.");
                }
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        throw new Exception("Invalid backup file format. Row data in table '{$tableName}' must be an array.");
                    }
                }
            }

            // Disable foreign key checks before everything - this is session-scoped in MySQL/MariaDB.
            // For MySQL/MariaDB we also use resetDatabase() (DROP + CREATE DATABASE) rather than
            // dropping tables individually, to fully purge stale InnoDB FK metadata which would
            // otherwise cause error 1823 when migrations recreate the same FK constraint names.
            $this->repository->setForeignKeyChecks(false, $driver);

            $this->repository->beginTransaction();

            // 1. Reset database (drop all tables, or drop+recreate database for MySQL/MariaDB)
            if ($onProgress) {
                $onProgress(10, "Resetting database...");
            }
            $this->repository->resetDatabase($driver);
            usleep(50000);

            // 2. Re-run migrations
            if ($onProgress) {
                $onProgress(25, "Running database migrations...");
            }
            $this->migrationService->applyMigrations();
            usleep(50000);

            // DDL causes implicit commits in MySQL/MariaDB; re-assert FK checks off and
            // start a fresh transaction for the data restoration phase.
            $this->repository->setForeignKeyChecks(false, $driver);
            if (!$this->repository->inTransaction()) {
                $this->repository->beginTransaction();
            }

            $tables = $backupData['tables'];
            $totalTables = count($tables);
            $currentIndex = 0;

            foreach ($tables as $table => $rows) {
                $currentIndex++;
                
                if ($onProgress) {
                    // Scale progress from 30% to 100%
                    $progress = 30 + (int)(($currentIndex / $totalTables) * 70);
                    $onProgress($progress, "Restoring table data: {$table}");
                }
                
                // Add a tiny sleep to make it smooth and visible
                usleep(30000); // 30ms

                if (empty($rows)) {
                    $this->repository->truncateTable($table);
                    continue;
                }

                // 3. Truncate table (migrations might have seeded it)
                $this->repository->truncateTable($table);

                // 4. Insert data
                foreach ($rows as $row) {
                    $this->repository->insertRow($table, $row);
                }
            }

            if ($this->repository->inTransaction()) {
                $this->repository->commit();
            }
        } catch (\Throwable $e) {
            if ($this->repository->inTransaction()) {
                try {
                    $this->repository->rollBack();
                } catch (\Throwable $rollbackEx) {
                    // Suppress nested rollback exception to prioritize the main failure
                }
            }
            throw $e;
        } finally {
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }
            try {
                $this->repository->setForeignKeyChecks(true, $driver);
            } catch (\Throwable $fkEx) {
                // Suppress finalizer exception
            }
        }

        return true;
    }

    private function getDriver(): string {
        return DB_CONFIG['driver'] ?? 'sqlite';
    }
}

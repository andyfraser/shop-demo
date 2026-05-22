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
    }

    public function import(array $file, ?callable $onProgress = null): bool {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed with error code: " . $file['error']);
        }

        $json = file_get_contents($file['tmp_name']);
        $backupData = json_decode($json, true);

        if (!$backupData || !isset($backupData['tables'])) {
            throw new Exception("Invalid backup file format. Expected portable JSON backup.");
        }

        $driver = $this->getDriver();

        // 1. Reset database (drop all tables)
        if ($onProgress) {
            $onProgress(10, "Resetting database...");
        }
        $this->dropAllTables($driver);
        usleep(50000);

        // 2. Re-run migrations
        if ($onProgress) {
            $onProgress(25, "Running database migrations...");
        }
        $this->migrationService->applyMigrations();
        usleep(50000);

        // Disable foreign key checks for import
        $this->repository->setForeignKeyChecks(false, $driver);

        try {
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
        } finally {
            $this->repository->setForeignKeyChecks(true, $driver);
        }

        return true;
    }

    private function dropAllTables(string $driver): void {
        $tables = $this->repository->getTables();
        $this->repository->setForeignKeyChecks(false, $driver);
        
        foreach ($tables as $table) {
            $this->repository->dropTable($table);
        }
        
        $this->repository->setForeignKeyChecks(true, $driver);
    }

    private function getDriver(): string {
        return DB_CONFIG['driver'] ?? 'sqlite';
    }
}

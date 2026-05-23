<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BackupService;
use App\Services\MigrationServiceInterface;
use App\Core\Database;

class MockMigrationService implements MigrationServiceInterface {
    public function __construct(private $db) {}
    public function applyMigrations(): array {
        $this->db->exec("CREATE TABLE IF NOT EXISTS test_table (id INTEGER PRIMARY KEY, val TEXT)");
        return ['mock_migration'];
    }
    public function rollbackMigration(): bool { return true; }
    public function getAppliedMigrations(): array { return []; }
    public function getAvailableMigrations(): array { return []; }
}

class BackupServiceTest extends TestCase {
    private $db;
    private $service;
    private $migrationService;

    private $dbFile;

    public function setUp(): void {
        $this->dbFile = tempnam(sys_get_temp_dir(), 'test_db_');
        $this->db = new \PDO('sqlite:' . $this->dbFile);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        $repository = new \App\Repositories\MigrationRepository($this->db);
        $this->migrationService = new MockMigrationService($this->db);
        $this->service = new BackupService($repository, $this->migrationService);

        $this->db->exec("CREATE TABLE test_table (id INTEGER PRIMARY KEY, val TEXT)");
        $this->db->exec("INSERT INTO test_table (val) VALUES ('hello')");
    }

    public function tearDown(): void {
        $this->db = null;
        if ($this->dbFile && file_exists($this->dbFile)) {
            @unlink($this->dbFile);
        }
    }

    public function testExportReturnsValidData() {
        $result = $this->service->export();
        
        $this->assertIsArray($result);
        $this->assertTrue(isset($result['path']));
        $this->assertTrue(isset($result['filename']));
        $this->assertStringContainsString('shop_backup_', $result['filename']);
        $this->assertEquals('application/json', $result['mime']);

        $content = json_decode(file_get_contents($result['path']), true);
        $this->assertIsArray($content);
        $this->assertTrue(isset($content['metadata']));
        $this->assertTrue(isset($content['tables']['test_table']));
        $this->assertEquals('hello', $content['tables']['test_table'][0]['val']);

        @unlink($result['path']);
    }

    public function testImportRestoresData() {
        // Create a backup file manually
        $backupFile = tempnam(sys_get_temp_dir(), 'test_bak_');
        $data = [
            'metadata' => ['version' => '1.0'],
            'tables' => [
                'test_table' => [
                    ['id' => 1, 'val' => 'imported']
                ]
            ]
        ];
        file_put_contents($backupFile, json_encode($data));

        $file = [
            'tmp_name' => $backupFile,
            'name' => 'backup.json',
            'error' => UPLOAD_ERR_OK
        ];

        // We need to mock migrationService for import because it calls applyMigrations which might fail if no files
        // But in our case we can use a dummy table to represent "applied" state if needed.
        // Actually MigrationService will just see no files in migrations dir.
        
        $this->service->import($file);

        $stmt = $this->db->query("SELECT val FROM test_table WHERE id = 1");
        $row = $stmt->fetch();
        $this->assertEquals('imported', $row['val']);

        @unlink($backupFile);
    }

    public function testImportThrowsOnStructuralFailureWithoutModifyingDatabase() {
        // Create an invalid backup file (tables is a string instead of array)
        $backupFile = tempnam(sys_get_temp_dir(), 'test_bak_');
        $data = [
            'metadata' => ['version' => '1.0'],
            'tables' => 'corrupted_string_here'
        ];
        file_put_contents($backupFile, json_encode($data));

        $file = [
            'tmp_name' => $backupFile,
            'name' => 'backup.json',
            'error' => UPLOAD_ERR_OK
        ];

        $exceptionThrown = false;
        try {
            $this->service->import($file);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('Invalid backup file format', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown);

        // Verify the original database is completely untouched (the hello record remains)
        $stmt = $this->db->query("SELECT val FROM test_table WHERE id = 1");
        $row = $stmt->fetch();
        $this->assertEquals('hello', $row['val']);

        @unlink($backupFile);
    }

    public function testImportRollsBackOnInsertionFailure() {
        // Create a backup with a row that violates constraints (e.g. invalid columns/types)
        $backupFile = tempnam(sys_get_temp_dir(), 'test_bak_');
        
        // This row will fail to insert because we'll cause it to fail, e.g. column doesn't exist
        $data = [
            'metadata' => ['version' => '1.0'],
            'tables' => [
                'test_table' => [
                    ['id' => 1, 'non_existent_column' => 'will_fail']
                ]
            ]
        ];
        file_put_contents($backupFile, json_encode($data));

        $file = [
            'tmp_name' => $backupFile,
            'name' => 'backup.json',
            'error' => UPLOAD_ERR_OK
        ];

        $exceptionThrown = false;
        try {
            $this->service->import($file);
        } catch (\Exception $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);

        // Verify the original database is rolled back and preserved
        $stmt = $this->db->query("SELECT val FROM test_table");
        $rows = $stmt->fetchAll();
        $this->assertCount(1, $rows);
        $this->assertEquals('hello', $rows[0]['val']);

        @unlink($backupFile);
    }

    public function testExportCallsOnProgress() {
        $progressCalls = [];
        $result = $this->service->export(function($percent, $msg) use (&$progressCalls) {
            $progressCalls[] = [$percent, $msg];
        });

        $this->assertNotEmpty($progressCalls);
        $this->assertEquals(100, end($progressCalls)[0]);
        $this->assertStringContainsString('test_table', end($progressCalls)[1]);

        @unlink($result['path']);
    }

    public function testImportCallsOnProgress() {
        $backupFile = tempnam(sys_get_temp_dir(), 'test_bak_');
        $data = [
            'metadata' => ['version' => '1.0'],
            'tables' => [
                'test_table' => [
                    ['id' => 1, 'val' => 'imported']
                ]
            ]
        ];
        file_put_contents($backupFile, json_encode($data));

        $file = [
            'tmp_name' => $backupFile,
            'name' => 'backup.json',
            'error' => UPLOAD_ERR_OK
        ];

        $progressCalls = [];
        $this->service->import($file, function($percent, $msg) use (&$progressCalls) {
            $progressCalls[] = [$percent, $msg];
        });

        $this->assertNotEmpty($progressCalls);
        $this->assertEquals(100, end($progressCalls)[0]);
        $this->assertStringContainsString('test_table', end($progressCalls)[1]);

        @unlink($backupFile);
    }

    public function testImportDoesNotReenableForeignKeysPrematurely() {
        $log = [];
        
        $repositorySpy = new class($this->db, $log) extends \App\Repositories\MigrationRepository {
            private array $spyLog;
            public function __construct(\PDO $db, array &$log) {
                parent::__construct($db);
                $this->spyLog =& $log;
            }
            
            public function getTables(): array {
                $this->spyLog[] = 'getTables';
                return ['test_table'];
            }
            public function dropTable(string $tableName): void {
                $this->spyLog[] = "dropTable:{$tableName}";
            }
            public function setForeignKeyChecks(bool $enable, string $driver): void {
                $this->spyLog[] = "setForeignKeyChecks:" . ($enable ? 'true' : 'false');
                parent::setForeignKeyChecks($enable, $driver);
            }
            public function truncateTable(string $tableName): void {
                $this->spyLog[] = "truncateTable:{$tableName}";
            }
            public function insertRow(string $table, array $data): void {
                $this->spyLog[] = "insertRow:{$table}";
            }
            public function beginTransaction(): void {
                $this->spyLog[] = 'beginTransaction';
                parent::beginTransaction();
            }
            public function commit(): void {
                $this->spyLog[] = 'commit';
                parent::commit();
            }
            public function rollBack(): void {
                $this->spyLog[] = 'rollBack';
                parent::rollBack();
            }
        };

        $migrationServiceSpy = new class($log) implements MigrationServiceInterface {
            public function __construct(private array &$log) {}
            public function applyMigrations(): array {
                $this->log[] = 'applyMigrations';
                return [];
            }
            public function rollbackMigration(): bool { return true; }
            public function getAppliedMigrations(): array { return []; }
            public function getAvailableMigrations(): array { return []; }
        };

        $service = new BackupService($repositorySpy, $migrationServiceSpy);

        $backupFile = tempnam(sys_get_temp_dir(), 'test_bak_');
        $data = [
            'metadata' => ['version' => '1.0'],
            'tables' => [
                'test_table' => [
                    ['id' => 1, 'val' => 'imported']
                ]
            ]
        ];
        file_put_contents($backupFile, json_encode($data));

        $file = [
            'tmp_name' => $backupFile,
            'name' => 'backup.json',
            'error' => UPLOAD_ERR_OK
        ];

        $service->import($file);
        @unlink($backupFile);

        // Verify the expected call sequence
        $expected = [
            'beginTransaction',
            'setForeignKeyChecks:false',
            'getTables',
            'dropTable:test_table',
            'applyMigrations',
            'truncateTable:test_table',
            'insertRow:test_table',
            'commit',
            'setForeignKeyChecks:true'
        ];

        $this->assertEquals($expected, $log);
    }

    public function testImportHandlesImplicitCommitOnDDL() {
        $log = [];
        
        $repositorySpy = new class($this->db, $log) extends \App\Repositories\MigrationRepository {
            private array $spyLog;
            private bool $inTx = false;
            
            public function __construct(\PDO $db, array &$log) {
                parent::__construct($db);
                $this->spyLog =& $log;
            }
            
            public function getTables(): array {
                $this->spyLog[] = 'getTables';
                return ['test_table'];
            }
            
            public function dropTable(string $tableName): void {
                $this->spyLog[] = "dropTable:{$tableName}";
                // Simulate implicit commit by terminating the active transaction
                $this->inTx = false;
            }
            
            public function setForeignKeyChecks(bool $enable, string $driver): void {
                $this->spyLog[] = "setForeignKeyChecks:" . ($enable ? 'true' : 'false');
            }
            
            public function truncateTable(string $tableName): void {
                $this->spyLog[] = "truncateTable:{$tableName}";
            }
            
            public function insertRow(string $table, array $data): void {
                $this->spyLog[] = "insertRow:{$table}";
            }
            
            public function beginTransaction(): void {
                $this->spyLog[] = 'beginTransaction';
                $this->inTx = true;
            }
            
            public function commit(): void {
                $this->spyLog[] = 'commit';
                $this->inTx = false;
            }
            
            public function rollBack(): void {
                $this->spyLog[] = 'rollBack';
                $this->inTx = false;
            }
            
            public function inTransaction(): bool {
                return $this->inTx;
            }
        };

        $migrationServiceSpy = new class($log) implements MigrationServiceInterface {
            public function __construct(private array &$log) {}
            public function applyMigrations(): array {
                $this->log[] = 'applyMigrations';
                return [];
            }
            public function rollbackMigration(): bool { return true; }
            public function getAppliedMigrations(): array { return []; }
            public function getAvailableMigrations(): array { return []; }
        };

        $service = new BackupService($repositorySpy, $migrationServiceSpy);

        $backupFile = tempnam(sys_get_temp_dir(), 'test_bak_');
        $data = [
            'metadata' => ['version' => '1.0'],
            'tables' => [
                'test_table' => [
                    ['id' => 1, 'val' => 'imported']
                ]
            ]
        ];
        file_put_contents($backupFile, json_encode($data));

        $file = [
            'tmp_name' => $backupFile,
            'name' => 'backup.json',
            'error' => UPLOAD_ERR_OK
        ];

        $service->import($file);
        @unlink($backupFile);

        // Verify the expected call sequence handles the implicit commit gracefully by starting a second transaction!
        $expected = [
            'beginTransaction',
            'setForeignKeyChecks:false',
            'getTables',
            'dropTable:test_table',
            'applyMigrations',
            'beginTransaction', // Started a second transaction!
            'truncateTable:test_table',
            'insertRow:test_table',
            'commit',
            'setForeignKeyChecks:true'
        ];

        $this->assertEquals($expected, $log);
    }

    public function testLockFileIsCreatedAndDeletedDuringExport() {
        $lockFile = sys_get_temp_dir() . '/demoshop_backup_restore.lock';
        $this->assertFalse(file_exists($lockFile));

        $lockChecked = false;
        $result = $this->service->export(function($percent, $msg) use ($lockFile, &$lockChecked) {
            $this->assertTrue(file_exists($lockFile));
            $this->assertEquals('backup', file_get_contents($lockFile));
            $lockChecked = true;
        });

        $this->assertTrue($lockChecked);
        $this->assertFalse(file_exists($lockFile));

        @unlink($result['path']);
    }

    public function testLockFileIsCreatedAndDeletedDuringImport() {
        $lockFile = sys_get_temp_dir() . '/demoshop_backup_restore.lock';
        $this->assertFalse(file_exists($lockFile));

        $backupFile = tempnam(sys_get_temp_dir(), 'test_bak_');
        $data = [
            'metadata' => ['version' => '1.0'],
            'tables' => [
                'test_table' => [
                    ['id' => 1, 'val' => 'imported']
                ]
            ]
        ];
        file_put_contents($backupFile, json_encode($data));

        $file = [
            'tmp_name' => $backupFile,
            'name' => 'backup.json',
            'error' => UPLOAD_ERR_OK
        ];

        $lockChecked = false;
        $this->service->import($file, function($percent, $msg) use ($lockFile, &$lockChecked) {
            $this->assertTrue(file_exists($lockFile));
            $this->assertEquals('restore', file_get_contents($lockFile));
            $lockChecked = true;
        });

        $this->assertTrue($lockChecked);
        $this->assertFalse(file_exists($lockFile));

        @unlink($backupFile);
    }
}


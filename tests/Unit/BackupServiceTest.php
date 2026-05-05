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
        
        $this->migrationService = new MockMigrationService($this->db);
        $this->service = new BackupService($this->db, $this->migrationService);

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
}

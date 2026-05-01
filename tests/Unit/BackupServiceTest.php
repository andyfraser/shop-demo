<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BackupService;
use App\Core\Database;

class BackupServiceTest extends TestCase {
    private $db;
    private $service;

    private $dbFile;

    public function setUp(): void {
        $this->dbFile = tempnam(sys_get_temp_dir(), 'test_db_');
        $this->db = new \PDO('sqlite:' . $this->dbFile);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        if (defined('DB_CONFIG')) {
            // Cannot redefine constant, but Database::getSqlitePath uses it.
            // If it's already defined, we might have issues if it doesn't match.
            // But usually in tests it's not defined yet.
        } else {
            define('DB_CONFIG', ['driver' => 'sqlite', 'path' => $this->dbFile]);
        }

        $this->db->exec("CREATE TABLE test_table (id INTEGER PRIMARY KEY, val TEXT)");
        $this->db->exec("INSERT INTO test_table (val) VALUES ('hello')");

        $this->service = new BackupService($this->db);
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
    }
}

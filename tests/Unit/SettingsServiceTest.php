<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SettingsService;
use App\Core\Database;

class SettingsServiceTest extends TestCase {
    private SettingsService $settings;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        // Clear settings table for isolation
        $this->db->exec("DELETE FROM settings");
        $this->settings = new SettingsService($this->db);
    }

    public function testGetDefaultValue() {
        $this->assertEquals('Demo|shop', $this->settings->get('site_name'));
        $this->assertEquals('£', $this->settings->get('currency_symbol'));
    }

    public function testSetAndGet() {
        $this->settings->set('test_key', 'test_value');
        $this->assertEquals('test_value', $this->settings->get('test_key'));
    }

    public function testPersistence() {
        $this->settings->set('persistent_key', 'persistent_value');
        
        // Create a new instance to verify it loads from DB
        $newSettings = new SettingsService($this->db);
        $this->assertEquals('persistent_value', $newSettings->get('persistent_key'));
    }

    public function testAll() {
        $this->settings->set('custom_key', 'custom_value');
        $all = $this->settings->all();
        
        $this->assertEquals('custom_value', $all['custom_key']);
        $this->assertEquals('Demo|shop', $all['site_name']); // Still has defaults
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SettingsService;
use App\Models\Settings;
use App\Core\Database;

class SettingsServiceTest extends TestCase {
    private SettingsService $service;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        // Clear settings table for isolation
        $this->db->exec("DELETE FROM settings");
        $this->service = new SettingsService($this->db);
    }

    public function testGetSettingsModel() {
        $settings = $this->service->getSettings();
        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertEquals('Demo|shop', $settings->site_name);
        $this->assertEquals(6, $settings->password_min_length); // Integer check
    }

    public function testSetAndGetTyped() {
        $this->service->set('password_min_length', '12');
        $settings = $this->service->getSettings();
        
        $this->assertEquals(12, $settings->password_min_length);
        $this->assertSame(12, $settings->password_min_length); // Strict type check
    }

    public function testPersistence() {
        $this->service->set('site_name', 'My Store');
        
        // New service instance should load from DB
        $newService = new SettingsService($this->db);
        $this->assertEquals('My Store', $newService->getSettings()->site_name);
    }

    public function testLegacySupport() {
        $this->service->set('site_name', 'Legacy Test');
        $this->assertEquals('Legacy Test', $this->service->get('site_name'));
        
        $all = $this->service->all();
        $this->assertEquals('Legacy Test', $all['site_name']);
        $this->assertEquals('£', $all['currency_symbol']);
    }
}

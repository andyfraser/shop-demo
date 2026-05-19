<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SettingsServiceInterface;
use App\Services\SettingsService;
use App\Models\Settings;
use App\Core\Database;

class SettingsServiceTest extends TestCase {
    private SettingsServiceInterface $service;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        // Clear settings table for isolation
        $this->db->exec("DELETE FROM settings");
        $logger = new \Tests\NullLogger();
        $cache = new \Tests\NullCache();
        $repository = new \App\Repositories\SettingsRepository($this->db, $logger);
        $this->service = new SettingsService($repository, $logger, $cache);
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
        $logger = new \Tests\NullLogger();
        $cache = new \Tests\NullCache();
        $repository = new \App\Repositories\SettingsRepository($this->db, $logger);
        $newService = new SettingsService($repository, $logger, $cache);
        $this->assertEquals('My Store', $newService->getSettings()->site_name);
    }

    public function testLegacySupport() {
        $this->service->set('site_name', 'Legacy Test');
        $this->assertEquals('Legacy Test', $this->service->get('site_name'));
        
        $all = $this->service->all();
        $this->assertEquals('Legacy Test', $all['site_name']);
        $this->assertEquals('£', $all['currency_symbol']);
    }

    public function testHandlesMissingTableGracefully() {
        $emptyDb = new \PDO('sqlite::memory:');
        $emptyDb->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $logger = new \Tests\NullLogger();
        $cache = new \Tests\NullCache();
        $repository = new \App\Repositories\SettingsRepository($emptyDb, $logger);
        $service = new SettingsService($repository, $logger, $cache);
        
        $settings = $service->getSettings();
        $this->assertInstanceOf(Settings::class, $settings);
        $this->assertEquals('Demo|shop', $settings->site_name); // Should use defaults
    }
}

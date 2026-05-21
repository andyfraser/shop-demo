<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DeliveryService;
use App\Models\DeliveryOption;
use Tests\NullLogger;

class DeliveryServiceTest extends TestCase {
    private $db;
    private $service;

    public function setUp(): void {
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->exec("CREATE TABLE delivery_options (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            price REAL NOT NULL,
            active INTEGER DEFAULT 1,
            min_order_total REAL DEFAULT 0,
            target_role TEXT DEFAULT NULL
        )");
        
        $this->db->exec("INSERT INTO delivery_options (name, price, active, min_order_total, target_role) VALUES ('Standard', 5.0, 1, 0, NULL)");
        $this->db->exec("INSERT INTO delivery_options (name, price, active, min_order_total, target_role) VALUES ('Express', 10.0, 1, 0, NULL)");
        $this->db->exec("INSERT INTO delivery_options (name, price, active, min_order_total, target_role) VALUES ('Free', 0.0, 1, 100, NULL)");
        $this->db->exec("INSERT INTO delivery_options (name, price, active, min_order_total, target_role) VALUES ('Disabled', 20.0, 0, 0, NULL)");
        $this->db->exec("INSERT INTO delivery_options (name, price, active, min_order_total, target_role) VALUES ('VIP Free Shipping', 0.0, 1, 0, 'vip')");

        $logger = new NullLogger();
        $repository = new \App\Repositories\DeliveryRepository($this->db, $logger);
        $this->service = new DeliveryService($repository, $logger);
    }

    public function testAllReturnsAllOptions() {
        $options = $this->service->all();
        $this->assertEquals(5, count($options));
        $this->assertInstanceOf(DeliveryOption::class, $options[0]);
    }

    public function testActiveFiltersCorrectly() {
        $options = $this->service->active(50);
        $this->assertEquals(2, count($options), "Should return Standard and Express");
        
        $options = $this->service->active(150);
        $this->assertEquals(3, count($options), "Should return Standard, Express and Free");
    }

    public function testActiveFiltersByRole() {
        // Without role, should only get standard/express (no VIP)
        $options = $this->service->active(50, null);
        $this->assertEquals(2, count($options));
        
        // With 'vip' role, should also get VIP Free Shipping
        $options = $this->service->active(50, 'vip');
        $this->assertEquals(3, count($options));
        $this->assertEquals('VIP Free Shipping', $options[0]->name); // Price is 0.0, so sorted first
    }

    public function testGetById() {
        $option = $this->service->get(1);
        $this->assertNotNull($option);
        $this->assertEquals('Standard', $option->name);
        
        $this->assertNull($this->service->get(999));
    }

    public function testDelete() {
        $this->assertTrue($this->service->delete(1));
        $this->assertEquals(4, count($this->service->all()));
    }
}

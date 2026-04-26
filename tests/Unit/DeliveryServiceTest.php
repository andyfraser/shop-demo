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
            min_order_total REAL DEFAULT 0
        )");
        
        $this->db->exec("INSERT INTO delivery_options (name, price, active, min_order_total) VALUES ('Standard', 5.0, 1, 0)");
        $this->db->exec("INSERT INTO delivery_options (name, price, active, min_order_total) VALUES ('Express', 10.0, 1, 0)");
        $this->db->exec("INSERT INTO delivery_options (name, price, active, min_order_total) VALUES ('Free', 0.0, 1, 100)");
        $this->db->exec("INSERT INTO delivery_options (name, price, active, min_order_total) VALUES ('Disabled', 20.0, 0, 0)");

        $this->service = new DeliveryService($this->db, new NullLogger());
    }

    public function testAllReturnsAllOptions() {
        $options = $this->service->all();
        $this->assertEquals(4, count($options));
        $this->assertInstanceOf(DeliveryOption::class, $options[0]);
    }

    public function testActiveFiltersCorrectly() {
        $options = $this->service->active(50);
        $this->assertEquals(2, count($options), "Should return Standard and Express");
        
        $options = $this->service->active(150);
        $this->assertEquals(3, count($options), "Should return Standard, Express and Free");
    }

    public function testGetById() {
        $option = $this->service->get(1);
        $this->assertNotNull($option);
        $this->assertEquals('Standard', $option->name);
        
        $this->assertNull($this->service->get(999));
    }

    public function testDelete() {
        $this->assertTrue($this->service->delete(1));
        $this->assertEquals(3, count($this->service->all()));
    }
}

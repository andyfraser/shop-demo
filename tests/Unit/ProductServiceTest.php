<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProductServiceInterface;
use App\Services\ProductService;
use App\Models\Product;
use App\Core\Database;

class ProductServiceTest extends TestCase {
    private ProductServiceInterface $service;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $this->service = new ProductService($this->db);
    }

    public function testFindById() {
        // Product 1 is 'ProBook Laptop 15"' in seed data
        $product = $this->service->findById(1);
        
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals(1, $product->id);
        $this->assertEquals('ProBook Laptop 15"', $product->name);
    }

    public function testFindBySlug() {
        $product = $this->service->findBySlug('probook-laptop-15');
        
        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('probook-laptop-15', $product->slug);
    }

    public function testSaveAndDeactivate() {
        $name = 'Test Product ' . time();
        $data = [
            'name'        => $name,
            'description' => 'Test Desc',
            'price'       => 99.99,
            'vat_rate'    => 20.0,
            'stock'       => 10,
            'category_id' => 1,
            'image'       => null,
            'active'      => 1,
            'featured'    => 0,
        ];

        $id = $this->service->save($data);
        $this->assertGreaterThan(0, $id);

        $product = $this->service->findById($id);
        $this->assertEquals($name, $product->name);
        $this->assertStringContainsString('test-product', $product->slug);

        $this->service->deactivate($id);
        $product = $this->service->findById($id);
        $this->assertEquals(0, (int)$product->active);
    }

    public function testSearch() {
        // 'ProBook' should be in seed data
        $results = $this->service->search('ProBook', 10, 1, 'name');
        $this->assertNotEmpty($results);
        $this->assertInstanceOf(Product::class, $results[0]);
        $this->assertStringContainsString('ProBook', $results[0]->name);
    }
}

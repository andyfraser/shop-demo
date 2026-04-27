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
        $this->service = new ProductService($this->db, new \Tests\NullLogger());
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

    public function testVariantSorting() {
        // Create a product
        $productId = $this->service->save([
            'name' => 'Sort Test Product',
            'price' => 10,
            'vat_rate' => 20,
            'stock' => 10,
            'active' => 1,
            'featured' => 0,
            'image' => null,
            'category_id' => null
        ]);

        // Create variants out of order
        $this->service->saveVariant([
            'product_id' => $productId,
            'name' => 'Variant B',
            'sort_order' => 2,
            'stock' => 5
        ]);
        $this->service->saveVariant([
            'product_id' => $productId,
            'name' => 'Variant A',
            'sort_order' => 1,
            'stock' => 5
        ]);
        $this->service->saveVariant([
            'product_id' => $productId,
            'name' => 'Variant C',
            'sort_order' => 0,
            'stock' => 5
        ]);

        $variants = $this->service->getVariants($productId);
        
        $this->assertCount(3, $variants);
        $this->assertEquals('Variant C', $variants[0]->name);
        $this->assertEquals('Variant A', $variants[1]->name);
        $this->assertEquals('Variant B', $variants[2]->name);
    }
}

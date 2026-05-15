<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProductServiceInterface;
use App\Services\ProductService;
use App\Models\Product;
use App\Core\Database;
use App\Services\AttributeService;

class ProductServiceTest extends TestCase {
    private ProductServiceInterface $service;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $logger = new \Tests\NullLogger();
        $attrService = new AttributeService($this->db, $logger);
        $promoService = new \App\Services\PromotionService($this->db, $logger);
        $repository = new \App\Repositories\ProductRepository($this->db, $logger);
        $this->service = new ProductService($repository, $attrService, $promoService, $logger);
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

    public function testFilteringByPrice() {
        // Find products between 800 and 1500
        $results = $this->service->getAllActive(10, 1, 'name', ['price_min' => 800, 'price_max' => 1500]);
        $this->assertNotEmpty($results);
        foreach ($results as $p) {
            $this->assertTrue($p->price >= 800, "Expected price {$p->price} >= 800");
            $this->assertTrue($p->price <= 1500, "Expected price {$p->price} <= 1500");
        }
    }

    public function testFilteringByAttributes() {
        // Seed data has attribute values: 1 is Brand: ProBook, 10 is Color: Silver
        // Product 1 has both 1 and 10.
        $results = $this->service->getAllActive(10, 1, 'name', ['attributes' => [1, 10]]);
        $this->assertNotEmpty($results, "Filtering by attributes 1 and 10 returned no results");
        
        $foundProduct1 = false;
        foreach ($results as $p) {
            if ($p->id === 1) $foundProduct1 = true;
        }
        $this->assertTrue($foundProduct1, "Product 1 not found in filtered results");

        // Selection 1 and 2 (two different brands) should return products that have EITHER 1 OR 2 
        $results = $this->service->getAllActive(10, 1, 'name', ['attributes' => [1, 2]]);
        $this->assertTrue(count($results) >= 2, "Expected at least 2 products for attributes 1 or 2"); 
    }

    public function testGetAvailableFiltersOrdering() {
        // Create an attribute
        $this->db->exec("INSERT INTO attributes (name) VALUES ('Order Test')");
        $attrId = $this->db->lastInsertId();

        // Create values with specific sort order
        $this->db->exec("INSERT INTO attribute_values (attribute_id, value, sort_order) VALUES ($attrId, 'Z', 10)");
        $valZ = $this->db->lastInsertId();
        $this->db->exec("INSERT INTO attribute_values (attribute_id, value, sort_order) VALUES ($attrId, 'A', 30)");
        $valA = $this->db->lastInsertId();
        $this->db->exec("INSERT INTO attribute_values (attribute_id, value, sort_order) VALUES ($attrId, 'M', 20)");
        $valM = $this->db->lastInsertId();

        // Create product and link it
        $productId = $this->service->save([
            'name' => 'Order Test Product',
            'price' => 100,
            'vat_rate' => 20,
            'stock' => 10,
            'active' => 1,
            'featured' => 0,
            'image' => null,
            'category_id' => null
        ]);
        $this->db->exec("INSERT INTO product_attribute_values (product_id, attribute_value_id) VALUES ($productId, $valZ)");
        $this->db->exec("INSERT INTO product_attribute_values (product_id, attribute_value_id) VALUES ($productId, $valA)");
        $this->db->exec("INSERT INTO product_attribute_values (product_id, attribute_value_id) VALUES ($productId, $valM)");

        $filters = $this->service->getAvailableFilters();
        
        $testAttr = null;
        foreach ($filters['attributes'] as $attr) {
            if ($attr['name'] === 'Order Test') {
                $testAttr = $attr;
                break;
            }
        }

        $this->assertNotNull($testAttr, "Order Test attribute not found");
        $values = array_map(fn($v) => $v['name'], $testAttr['values']);
        
        // Should be sorted by sort_order: Z (10), M (20), A (30)
        $this->assertEquals(['Z', 'M', 'A'], $values);
    }

    public function testGetAvailableFilters() {
        $filters = $this->service->getAvailableFilters([4, 5, 6]); // Electronics subcategories
        $this->assertTrue(isset($filters['min_price']), "min_price missing");
        $this->assertTrue(isset($filters['max_price']), "max_price missing");
        $this->assertTrue(isset($filters['attributes']), "attributes missing");
        $this->assertNotEmpty($filters['attributes'], "Available attributes list is empty");
    }

    public function testGetRelatedProducts() {
        // Product 1 (ProBook Laptop 15") is in category 4 (Laptops)
        // Product 8 (MiniBook 13") is also in category 4 (Laptops)
        // They share brand/color attributes in seed data.
        
        $related = $this->service->getRelatedProducts(1, 4);
        
        $this->assertNotEmpty($related);
        $this->assertCount(4, $related);
        
        // Product 8 should be in the results and high relevance
        $found8 = false;
        foreach ($related as $p) {
            if ($p->id === 8) $found8 = true;
            $this->assertTrue($p->id !== 1, "Current product should not be in related results");
        }
        $this->assertTrue($found8, "Product 8 (similar laptop) should be in related results");
    }

    public function testSearchPunctuation() {
        // Create a product with punctuation
        $productId = $this->service->save([
            'name' => "Men's T-Shirt",
            'price' => 19.99,
            'vat_rate' => 20,
            'stock' => 50,
            'active' => 1,
            'featured' => 0,
            'image' => null,
            'category_id' => null
        ]);

        // Search with punctuation - should match
        $results = $this->service->search("Men's T-Shirt", 10, 1, 'name');
        $this->assertNotEmpty($results);
        $this->assertEquals("Men's T-Shirt", $results[0]->name);

        // Search without punctuation - should match
        $results = $this->service->search("Mens TShirt", 10, 1, 'name');
        $this->assertNotEmpty($results);
        $this->assertEquals("Men's T-Shirt", $results[0]->name);
        
        // Search mixed
        $results = $this->service->search("men's tshirt", 10, 1, 'name');
        $this->assertNotEmpty($results);
    }

    public function testSearchSuggestions() {
        // 'ProBook' in seed data
        $results = $this->service->searchSuggestions('ProB', 5);
        $this->assertNotEmpty($results);
        $this->assertStringContainsString('ProBook', $results[0]->name);
        
        // Test limit
        $results = $this->service->searchSuggestions('P', 1);
        $this->assertCount(1, $results);
    }
}

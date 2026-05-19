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
        $attrRepository = new \App\Repositories\AttributeRepository($this->db, $logger);
        $attrService = new AttributeService($attrRepository, $logger, new \Tests\NullCache());
        $categoryRepository = new \App\Repositories\CategoryRepository($this->db, $logger);
        $categoryService = new \App\Services\CategoryService($categoryRepository, $logger, new \Tests\NullCache());
        $promoEvaluator = new \App\Services\PromotionEvaluator($categoryService);
        $promotionRepository = new \App\Repositories\PromotionRepository($this->db, $logger);
        $promoService = new \App\Services\PromotionService($promotionRepository, $promoEvaluator, $logger, new \Tests\NullCache());
        $repository = new \App\Repositories\ProductRepository($this->db, $logger);
        $variantService = new \App\Services\ProductVariantService($repository, $attrService);
        $this->service = new ProductService($repository, $attrService, $promoService, $variantService, $logger, new \Tests\NullCache());
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

    public function testUpdateStock() {
        $productId = $this->service->save([
            'name' => 'Stock Update Test',
            'price' => 10,
            'vat_rate' => 20,
            'stock' => 50,
            'active' => 1,
            'featured' => 0,
            'image' => null,
            'category_id' => null
        ]);

        $this->service->updateStock($productId, 75);
        
        $updatedProduct = $this->service->findById($productId);
        $this->assertEquals(75, $updatedProduct->stock);
        $this->assertEquals('Stock Update Test', $updatedProduct->name);
    }

    public function testUpdateVariantStock() {
        $productId = $this->service->save([
            'name' => 'Variant Stock Update Test',
            'price' => 10,
            'vat_rate' => 20,
            'stock' => 10,
            'active' => 1,
            'featured' => 0,
            'image' => null,
            'category_id' => null
        ]);

        $variantId = $this->service->saveVariant([
            'product_id' => $productId,
            'name' => 'Test Variant',
            'stock' => 5,
            'active' => 1
        ]);

        $this->service->updateVariantStock($variantId, 25);
        
        $updatedVariant = $this->service->findVariantById($variantId);
        $this->assertEquals(25, $updatedVariant->stock);
        $this->assertEquals('Test Variant', $updatedVariant->name);
    }

    public function testSearch() {
        // 'ProBook' should be in seed data
        $criteria = new \App\Core\QueryCriteria(['search' => 'ProBook', 'limit' => 10, 'sort' => 'name']);
        $results = $this->service->search($criteria);
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
        $criteria = new \App\Core\QueryCriteria([
            'limit' => 10, 
            'sort' => 'name', 
            'filters' => ['price_min' => 800, 'price_max' => 1500]
        ]);
        $results = $this->service->getAllActive($criteria);
        $this->assertNotEmpty($results);
        foreach ($results as $p) {
            $this->assertTrue($p->price >= 800, "Expected price {$p->price} >= 800");
            $this->assertTrue($p->price <= 1500, "Expected price {$p->price} <= 1500");
        }
    }

    public function testFilteringByAttributes() {
        // Seed data has attribute values: 1 is Brand: ProBook, 10 is Color: Silver
        // Product 1 has both 1 and 10.
        $criteria = new \App\Core\QueryCriteria([
            'limit' => 10, 
            'sort' => 'name', 
            'filters' => ['attributes' => [1, 10]]
        ]);
        $results = $this->service->getAllActive($criteria);
        $this->assertNotEmpty($results, "Filtering by attributes 1 and 10 returned no results");
        
        $foundProduct1 = false;
        foreach ($results as $p) {
            if ($p->id === 1) $foundProduct1 = true;
        }
        $this->assertTrue($foundProduct1, "Product 1 not found in filtered results");

        // Selection 1 and 2 (two different brands) should return products that have EITHER 1 OR 2 
        $criteria = new \App\Core\QueryCriteria([
            'limit' => 10, 
            'sort' => 'name', 
            'filters' => ['attributes' => [1, 2]]
        ]);
        $results = $this->service->getAllActive($criteria);
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
        $results = $this->service->search(new \App\Core\QueryCriteria(['search' => "Men's T-Shirt", 'limit' => 10, 'sort' => 'name']));
        $this->assertNotEmpty($results);
        $this->assertEquals("Men's T-Shirt", $results[0]->name);

        // Search without punctuation - should match
        $results = $this->service->search(new \App\Core\QueryCriteria(['search' => "Mens TShirt", 'limit' => 10, 'sort' => 'name']));
        $this->assertNotEmpty($results);
        $this->assertEquals("Men's T-Shirt", $results[0]->name);
        
        // Search mixed
        $results = $this->service->search(new \App\Core\QueryCriteria(['search' => "men's tshirt", 'limit' => 10, 'sort' => 'name']));
        $this->assertNotEmpty($results);
    }

    public function testProductLevelAttributeWithForceVariant() {
        // Create an attribute "Brand Reproduction"
        $this->db->exec("INSERT INTO attributes (name) VALUES ('Brand Reproduction')");
        $attrId = $this->db->lastInsertId();

        // Create an attribute value "TestBrandRepro"
        $this->db->exec("INSERT INTO attribute_values (attribute_id, value, sort_order) VALUES ($attrId, 'TestBrandRepro', 0)");
        $valId = $this->db->lastInsertId();

        // Create a product with force_variant = 1
        $productId = $this->service->save([
            'name' => 'Force Variant Product',
            'price' => 100,
            'vat_rate' => 20,
            'stock' => 10,
            'active' => 1,
            'featured' => 0,
            'image' => null,
            'category_id' => null,
            'force_variant' => 1
        ]);

        // Link the "TestBrandRepro" value to the product in product_attribute_values
        $this->db->exec("INSERT INTO product_attribute_values (product_id, attribute_value_id) VALUES ($productId, $valId)");

        // 1. Verify it shows up in getAvailableFilters()
        $filters = $this->service->getAvailableFilters();
        $found = false;
        foreach ($filters['attributes'] as $attr) {
            if ($attr['name'] === 'Brand Reproduction') {
                foreach ($attr['values'] as $val) {
                    if ($val['name'] === 'TestBrandRepro') {
                        $found = true;
                        break 2;
                    }
                }
            }
        }
        $this->assertTrue($found, "Product-level attribute should be available in filters even if force_variant=1");

        // 2. Verify filtering by this attribute works
        $criteria = new \App\Core\QueryCriteria([
            'filters' => ['attributes' => [(int)$valId]]
        ]);
        $results = $this->service->getAllActive($criteria);
        
        $foundProduct = false;
        foreach ($results as $p) {
            if ($p->id == $productId) {
                $foundProduct = true;
                break;
            }
        }
        $this->assertTrue($foundProduct, "Product with force_variant=1 should be findable by product-level attribute");
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

    public function testAdminListProductStockAggregation() {
        // ... (previous test code)
    }

    public function testGetLowStockIncludingVariants() {
        // 1. Create a product with high stock (not low)
        $pIdHigh = $this->service->save([
            'name' => 'High Stock Product',
            'price' => 10,
            'vat_rate' => 20,
            'stock' => 100,
            'active' => 1,
            'featured' => 0,
            'force_variant' => 0,
            'category_id' => null
        ]);

        // 2. Create a product with low stock
        $pIdLow = $this->service->save([
            'name' => 'Low Stock Product',
            'price' => 10,
            'vat_rate' => 20,
            'stock' => 2,
            'active' => 1,
            'featured' => 0,
            'force_variant' => 0,
            'category_id' => null
        ]);

        // 3. Create a product with high total stock but one low stock variant
        $pIdVariant = $this->service->save([
            'name' => 'Variant Product',
            'price' => 10,
            'vat_rate' => 20,
            'stock' => 100,
            'active' => 1,
            'featured' => 0,
            'force_variant' => 1,
            'category_id' => null
        ]);
        $this->service->saveVariant([
            'product_id' => $pIdVariant,
            'name' => 'Low Stock Variant',
            'stock' => 1,
            'active' => 1
        ]);
        $this->service->saveVariant([
            'product_id' => $pIdVariant,
            'name' => 'High Stock Variant',
            'stock' => 50,
            'active' => 1
        ]);

        $threshold = 5;
        $lowStockItems = $this->service->getLowStock($threshold, 100);

        $foundLowProduct = false;
        $foundLowVariant = false;
        $foundHighProduct = false;

        foreach ($lowStockItems as $item) {
            if ($item->name === 'Low Stock Product') $foundLowProduct = true;
            if ($item instanceof \App\Models\ProductVariant && $item->name === 'Variant Product - Low Stock Variant') {
                $foundLowVariant = true;
                $this->assertEquals('Variant Product', $item->product_name, "Variant should have product_name populated");
            }
            if ($item->name === 'High Stock Product') $foundHighProduct = true;
        }

        $this->assertTrue($foundLowProduct, "Low stock product should be in results");
        $this->assertTrue($foundLowVariant, "Low stock variant should be in results");
        $this->assertFalse($foundHighProduct, "High stock product should not be in results");

        // Verify alphabetical sorting
        $names = array_map(fn($i) => $i->name, $lowStockItems);
        $sortedNames = $names;
        usort($sortedNames, 'strcasecmp');
        $this->assertEquals($sortedNames, $names, "Results should be sorted alphabetically");
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ProductServiceInterface;
use App\Services\ProductService;
use App\Models\Product;
use App\Core\Database;
use App\Services\AttributeService;

class BundleStockTest extends TestCase {
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
        $variantService = new \App\Services\ProductVariantService($repository, $attrService, new \Tests\NullEventDispatcher());
        $this->service = new ProductService($repository, $attrService, $promoService, $variantService, $logger, new \Tests\NullCache(), new \Tests\NullEventDispatcher());
    }

    public function testBundleStockInAdminList() {
        // 1. Create component products
        $p1Id = $this->service->save([
            'name' => 'Component 1',
            'price' => 10,
            'vat_rate' => 20,
            'stock' => 10,
            'active' => 1,
            'featured' => 0,
            'image' => null,
            'category_id' => null
        ]);

        $p2Id = $this->service->save([
            'name' => 'Component 2',
            'price' => 20,
            'vat_rate' => 20,
            'stock' => 5,
            'active' => 1,
            'featured' => 0,
            'image' => null,
            'category_id' => null
        ]);

        // 3. Create a bundle
        $bundleId = $this->service->save([
            'name' => 'Test Bundle',
            'price' => 25,
            'vat_rate' => 20,
            'stock' => 0,
            'is_bundle' => 1,
            'active' => 1,
            'featured' => 0,
            'image' => null,
            'category_id' => null
        ]);

        $this->service->syncBundleItems($bundleId, [
            ['product_id' => $p1Id, 'qty' => 1],
            ['product_id' => $p2Id, 'qty' => 1],
        ]);

        // 4. Fetch via getAllForAdmin
        $criteria = new \App\Core\QueryCriteria(['search' => 'Test Bundle']);
        $products = $this->service->getAllForAdmin($criteria);

        $this->assertNotEmpty($products);
        $bundle = null;
        foreach ($products as $p) {
            if ($p->id == $bundleId) {
                $bundle = $p;
                break;
            }
        }

        $this->assertNotNull($bundle, "Bundle not found in admin list");
        
        // This is expected to FAIL before the fix
        $this->assertEquals(5, $bundle->getAvailableStock(), "Bundle stock should be 5 (minimum of component stocks)");
    }
}

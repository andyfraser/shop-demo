<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Promotion;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\PromotionService;
use Tests\NullLogger;

class PromotionEnhancementTest extends TestCase {
    private $db;
    private $promotionService;
    private $logger;

    public function setUp(): void {
        $this->db = \App\Core\Database::getConnection();
        $this->logger = new NullLogger();
        $promotionRepository = new \App\Repositories\PromotionRepository($this->db, $this->logger);
        $this->promotionService = new PromotionService($promotionRepository, $this->logger);

        // Clear tables
        $this->db->exec("DELETE FROM promotion_tiers");
        $this->db->exec("DELETE FROM promotion_codes");
        $this->db->exec("DELETE FROM promotion_targets");
        $this->db->exec("DELETE FROM order_promotions");
        $this->db->exec("DELETE FROM orders");
        $this->db->exec("DELETE FROM promotions");
    }

    public function testUsageLimitPerUser() {
        $promoId = $this->promotionService->save([
            'name' => 'Once Only',
            'type' => Promotion::TYPE_FIXED_AMOUNT,
            'value' => 10.0,
            'target_type' => Promotion::TARGET_ORDER,
            'code' => 'ONCE',
            'usage_limit_per_user' => 1,
            'active' => 1
        ]);

        $user = new User($this->logger);
        $user->id = 1;
        $user->role = 'customer';

        $items = [new CartItem($this->logger)];
        $subtotal = 100.0;

        // Valid first time
        $promo = $this->promotionService->validateCode('ONCE', $items, $subtotal, $user);
        $this->assertNotNull($promo);

        // Simulate order creation
        $this->db->prepare("INSERT INTO orders (user_id, total, status) VALUES (1, 90.0, 'pending')")->execute();
        $orderId = $this->db->lastInsertId();
        $this->db->prepare("INSERT INTO order_promotions (order_id, promotion_id, promotion_name, discount_amount, promo_code) VALUES (?, ?, 'Once Only', 10.0, 'ONCE')")
            ->execute([$orderId, $promoId]);

        // Invalid second time
        $promo = $this->promotionService->validateCode('ONCE', $items, $subtotal, $user);
        $this->assertNull($promo);
    }

    public function testAutomaticPromotionUserLimit() {
        $promoId = $this->promotionService->save([
            'name' => 'Automatic Once Only',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10.0,
            'target_type' => Promotion::TARGET_ORDER,
            'usage_limit_per_user' => 1,
            'active' => 1
        ]);

        $user = new User($this->logger);
        $user->id = 1;
        $user->role = 'customer';

        // Initially active
        $promos = $this->promotionService->getActivePromotions(true, $user);
        $this->assertNotEmpty($promos);
        $this->assertTrue($promos[0]->isActive($user));

        // Place an order with this promotion
        $this->db->prepare("INSERT INTO orders (user_id, total, status) VALUES (1, 90.0, 'pending')")->execute();
        $orderId = $this->db->lastInsertId();
        $this->db->prepare("INSERT INTO order_promotions (order_id, promotion_id, promotion_name, discount_amount) VALUES (?, ?, 'Automatic Once Only', 10.0)")
            ->execute([$orderId, $promoId]);

        // Now it should be filtered out for this user
        $promos = $this->promotionService->getActivePromotions(true, $user);
        $this->assertCount(0, $promos, "Promotion should be filtered out for user who already used it");
    }

    public function testPriority() {
        $this->promotionService->save([
            'name' => 'Low Priority High Value',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 50.0,
            'target_type' => Promotion::TARGET_ORDER,
            'priority' => 0,
            'active' => 1
        ]);

        $this->promotionService->save([
            'name' => 'High Priority Low Value',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10.0,
            'target_type' => Promotion::TARGET_ORDER,
            'priority' => 10,
            'active' => 1
        ]);

        $promos = $this->promotionService->getActivePromotions(true);
        $this->assertEquals('High Priority Low Value', $promos[0]->name);
    }

    public function testTieredDiscounts() {
        $promoId = $this->promotionService->save([
            'name' => 'Tiered',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 5.0, // Base 5%
            'target_type' => Promotion::TARGET_ORDER,
            'active' => 1,
            'tiers' => [
                ['min_amount' => 100.0, 'value' => 10.0],
                ['min_amount' => 200.0, 'value' => 20.0]
            ]
        ]);

        $promo = $this->promotionService->findById($promoId);
        $items = [];

        // Under 100 -> 5%
        $discount = $this->promotionService->calculateDiscount($promo, $items, 50.0);
        $this->assertEquals(2.5, $discount);

        // 100-199 -> 10%
        $discount = $this->promotionService->calculateDiscount($promo, $items, 150.0);
        $this->assertEquals(15.0, $discount);

        // 200+ -> 20%
        $discount = $this->promotionService->calculateDiscount($promo, $items, 250.0);
        $this->assertEquals(50.0, $discount);
    }

    public function testExclusionRules() {
        $productId = 1;
        $excludedProductId = 2;

        $promoId = $this->promotionService->save([
            'name' => 'Exclusion Test',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10.0,
            'target_type' => Promotion::TARGET_PRODUCT,
            'active' => 1,
            'target_ids' => [$productId, $excludedProductId],
            'excluded_ids' => [$excludedProductId]
        ]);

        $promo = $this->promotionService->findById($promoId);
        
        $p1 = new Product($this->logger);
        $p1->id = $productId;
        $p1->category_id = 1;

        $p2 = new Product($this->logger);
        $p2->id = $excludedProductId;
        $p2->category_id = 1;

        $item1 = new CartItem($this->logger);
        $item1->product = $p1;
        $item1->product_id = $productId;
        $item1->unit_price = 100.0;
        $item1->qty = 1;

        $item2 = new CartItem($this->logger);
        $item2->product = $p2;
        $item2->product_id = $excludedProductId;
        $item2->unit_price = 100.0;
        $item2->qty = 1;

        $discount = $this->promotionService->calculateDiscount($promo, [$item1, $item2], 200.0);
        // Only item1 should be discounted
        $this->assertEquals(10.0, $discount);
    }

    public function testAdditionalCodes() {
        $promoId = $this->promotionService->save([
            'name' => 'Multi Code',
            'type' => Promotion::TYPE_FIXED_AMOUNT,
            'value' => 10.0,
            'target_type' => Promotion::TARGET_ORDER,
            'code' => 'MAIN',
            'additional_codes' => ['EXTRA1', 'EXTRA2'],
            'active' => 1
        ]);

        $items = [];
        $subtotal = 100.0;

        $this->assertNotNull($this->promotionService->validateCode('MAIN', $items, $subtotal));
        $this->assertNotNull($this->promotionService->validateCode('EXTRA1', $items, $subtotal));
        $this->assertNotNull($this->promotionService->validateCode('EXTRA2', $items, $subtotal));
        $this->assertNull($this->promotionService->validateCode('WRONG', $items, $subtotal));
    }

    public function testTargetRole() {
        $promoId = $this->promotionService->save([
            'name' => 'VIP Only',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 20.0,
            'target_type' => Promotion::TARGET_ORDER,
            'target_role' => 'admin',
            'code' => 'VIP',
            'active' => 1
        ]);

        $items = [];
        $subtotal = 100.0;

        $customer = new User($this->logger);
        $customer->role = 'customer';

        $admin = new User($this->logger);
        $admin->role = 'admin';

        $this->assertNull($this->promotionService->validateCode('VIP', $items, $subtotal, $customer));
        $this->assertNotNull($this->promotionService->validateCode('VIP', $items, $subtotal, $admin));
    }

    public function testEverythingExceptCategories() {
        $excludedCategoryId = 99;
        $normalCategoryId = 1;

        $promoId = $this->promotionService->save([
            'name' => 'Everything Except Category 99',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10.0,
            'target_type' => Promotion::TARGET_CATEGORY,
            'active' => 1,
            'target_ids' => [], // Empty means all
            'excluded_ids' => [$excludedCategoryId]
        ]);

        $promo = $this->promotionService->findById($promoId);
        
        $p1 = new Product($this->logger);
        $p1->id = 1;
        $p1->category_id = $normalCategoryId;

        $p2 = new Product($this->logger);
        $p2->id = 2;
        $p2->category_id = $excludedCategoryId;

        // Verify matches
        $this->assertTrue($this->promotionService->isProductQualifying($p1, $promo));
        $this->assertFalse($this->promotionService->isProductQualifying($p2, $promo));
    }
}

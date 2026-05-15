<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Promotion;
use App\Models\User;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\PromotionService;
use Tests\NullLogger;

class CustomRolePromotionTest extends TestCase {
    private $db;
    private $promoService;
    private $logger;

    public function setUp(): void {
        $this->db = \App\Core\Database::getConnection();
        $this->logger = new NullLogger();
        $categoryRepo = new \App\Repositories\CategoryRepository($this->db, $this->logger);
        $categoryService = new \App\Services\CategoryService($categoryRepo, $this->logger);
        $promoEvaluator = new \App\Services\PromotionEvaluator($categoryService);
        $promotionRepository = new \App\Repositories\PromotionRepository($this->db, $this->logger);
        $this->promoService = new PromotionService($promotionRepository, $promoEvaluator, $this->logger);
    }

    public function testPromotionAppliesToCustomRole() {
        // 1. Create a custom role promotion
        $promoId = $this->promoService->save([
            'name' => 'VIP Discount',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 20,
            'target_type' => Promotion::TARGET_ORDER,
            'target_role' => 'vip',
            'active' => 1,
            'min_order_amount' => 0
        ]);

        $promo = $this->promoService->findById($promoId);

        // 2. Create users
        $vipUser = new User($this->logger);
        $vipUser->id = 999;
        $vipUser->role = 'vip';

        $regularUser = new User($this->logger);
        $regularUser->id = 1000;
        $regularUser->role = 'customer';

        // 3. Test activity
        $this->assertTrue($promo->isLive(), "Promotion should be live (generally active)");
        $this->assertTrue($promo->isActive($vipUser), "Promotion should be active for VIP user");
        $this->assertFalse($promo->isActive($regularUser), "Promotion should NOT be active for regular user");
        $this->assertFalse($promo->isActive(null), "Promotion should NOT be active for guest");
    }

    public function testCalculationWithCustomRole() {
        $promo = new Promotion($this->logger);
        $promo->type = Promotion::TYPE_PERCENTAGE;
        $promo->value = 10;
        $promo->target_type = Promotion::TARGET_ORDER;
        $promo->target_role = 'wholesale';
        $promo->active = 1;
        $promo->min_order_amount = 0;

        $user = new User($this->logger);
        $user->role = 'wholesale';

        $item = new CartItem($this->logger);
        $item->unit_price = 100;
        $item->qty = 1;
        
        // Use validateCode to check if it qualifies
        // Since we are not using a real code, we'll manually check isActive
        $this->assertTrue($promo->isActive($user));
        
        $discount = $this->promoService->calculateDiscount($promo, [$item], 100);
        $this->assertEquals(10.0, $discount);
    }
}

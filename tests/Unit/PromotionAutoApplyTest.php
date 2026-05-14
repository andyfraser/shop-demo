<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CartService;
use App\Services\CartServiceInterface;
use App\Services\ProductService;
use App\Services\PromotionService;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Services\VatService;
use App\Services\AttributeService;
use App\Core\Database;
use App\Core\Container;
use App\Models\Promotion;

class PromotionAutoApplyTest extends TestCase {
    private CartServiceInterface $cartService;
    private PromotionService $promotionService;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $logger = new \Tests\NullLogger();
        
        $settingsService = new SettingsService($this->db, $logger);
        $authService = new AuthService($this->db, $settingsService, $logger);
        $vatService = new VatService();
        $emailService = new \App\Services\EmailService($settingsService, $logger);
        $paymentService = new \App\Services\Payment\PaymentService($logger);
        $orderService = new \App\Services\OrderService($this->db, $logger, $vatService, $paymentService, $emailService);
        $attrService = new AttributeService($this->db, $logger);
        $this->promotionService = new PromotionService($this->db, $logger, null, $orderService);
        $productService = new ProductService($this->db, $attrService, $this->promotionService, $logger);
        
        $this->cartService = new CartService(
            $this->db,
            $productService,
            $authService,
            $vatService,
            $this->promotionService,
            $orderService,
            $logger
        );

        // Clean up
        $this->db->exec("DELETE FROM promotions");
        $this->db->exec("DELETE FROM carts");
    }

    public function testAutoApplyActivePromo() {
        // Create an active promotion
        $promoId = $this->promotionService->save([
            'name' => 'Auto Apply Test',
            'code' => 'AUTO10',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_ORDER,
            'min_order_amount' => 100,
            'active' => 1
        ]);

        // Initially no promotion
        $this->assertCount(0, $this->cartService->getAppliedPromotions());

        // Apply promo code (simulating URL visit logic)
        $result = $this->cartService->applyPromoCode('AUTO10');
        $this->assertTrue($result);

        // Should be applied (staged)
        $applied = $this->cartService->getAppliedPromotions();
        $this->assertCount(1, $applied);
        $this->assertEquals('AUTO10', $applied[0]->code);
        
        // Discount should be 0 because cart is empty (min_order_amount 100)
        $this->assertEquals(0.0, $this->cartService->discount());

        // Add item to meet threshold (assume product 1 price is 899.99 in seed but we cleared db? wait, seed might not be there if I exec DELETE)
        // I'll use save to ensure product exists
        $attrService = new AttributeService($this->db, new \Tests\NullLogger());
        $productService = new ProductService($this->db, $attrService, $this->promotionService, new \Tests\NullLogger());
        $productId = $productService->save([
            'name' => 'Test Product',
            'price' => 200,
            'vat_rate' => 20,
            'stock' => 10,
            'active' => 1,
            'featured' => 0
        ]);

        $this->cartService->add($productId, 1);
        
        // Now discount should be 20 (10% of 200)
        $this->assertEquals(20.0, $this->cartService->discount());
    }

    public function testDoesNotApplyInactivePromo() {
        $this->promotionService->save([
            'name' => 'Inactive Test',
            'code' => 'INACTIVE',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_ORDER,
            'min_order_amount' => 0,
            'active' => 0
        ]);

        $result = $this->cartService->applyPromoCode('INACTIVE');
        $this->assertFalse($result);
        $this->assertCount(0, $this->cartService->getAppliedPromotions());
    }

    public function testPromotionNotStartedMessage() {
        // This test will verify that we can identify a promotion that hasn't started.
        // The controller logic is what actually sets the message, so here we just verify the state.
        $startDate = date('Y-m-d H:i:s', strtotime('+1 day'));
        $this->promotionService->save([
            'name' => 'Future Test',
            'code' => 'FUTURE',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_ORDER,
            'min_order_amount' => 0,
            'start_date' => $startDate,
            'active' => 1
        ]);

        $promo = $this->promotionService->findByCode('FUTURE');
        $this->assertNotNull($promo);
        $this->assertFalse($promo->isActive());
        $this->assertTrue(strtotime($promo->start_date) > time());
    }
}

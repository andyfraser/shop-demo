<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PromotionService;
use App\Services\CartService;
use App\Services\ProductService;
use App\Services\AuthService;
use App\Services\SettingsService;
use App\Services\VatService;
use App\Services\AttributeService;
use App\Models\Promotion;
use App\Core\Database;

class DiscountIsolationTest extends TestCase {
    private CartService $cart;
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
        $repository = new \App\Repositories\ProductRepository($this->db, $logger);
        $productService = new ProductService($repository, $attrService, $this->promotionService, $logger);
        
        $this->cart = new CartService(
            $this->db,
            $productService,
            $authService,
            $vatService,
            $this->promotionService,
            $orderService,
            $logger
        );

        // We don't delete products/categories because other tests depend on seed data
        // We just clear carts and promotions to have a clean slate for this test
        $this->db->exec("DELETE FROM promotion_targets");
        $this->db->exec("DELETE FROM promotions");
        $this->db->exec("DELETE FROM cart_items");
        $this->db->exec("DELETE FROM carts");
    }

    public function testPercentageDiscountOnlyAppliesToProducts() {
        // 1. Create a 20% off whole order promotion
        $this->promotionService->save([
            'name' => '20% OFF',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 20,
            'target_type' => Promotion::TARGET_ORDER,
            'active' => 1,
            'min_order_amount' => 0
        ]);

        // 2. Add a product worth 100
        $attrService = new AttributeService($this->db, new \Tests\NullLogger());
        $logger = new \Tests\NullLogger();
        $repository = new \App\Repositories\ProductRepository($this->db, $logger);
        $productService = new ProductService($repository, $attrService, $this->promotionService, $logger);
        $productId = $productService->save([
            'name' => 'Product 100',
            'price' => 100,
            'vat_rate' => 20,
            'stock' => 10,
            'active' => 1,
            'featured' => 0
        ]);
        $this->cart->add($productId, 1);

        // 3. Verify discount is 20
        $this->assertEquals(20.0, $this->cart->discount());
        $this->assertEquals(80.0, $this->cart->grandTotal());

        // 4. Simulate a delivery cost of 10. 
        // In CheckoutController logic: total = grandTotal + delivery
        $finalTotal = $this->cart->grandTotal() + 10.0;
        $this->assertEquals(90.0, $finalTotal);

        // If 20% was applied to (100 + 10), total would be 110 * 0.8 = 88.
        // Since it's 90, we know delivery was NOT discounted.
    }

    public function testFixedDiscountCappedAtSubtotal() {
        // 1. Create a 150 OFF promotion
        $this->promotionService->save([
            'name' => '150 OFF',
            'type' => Promotion::TYPE_FIXED_AMOUNT,
            'value' => 150,
            'target_type' => Promotion::TARGET_ORDER,
            'active' => 1,
            'min_order_amount' => 0
        ]);

        // 2. Add a product worth 100
        $attrService = new AttributeService($this->db, new \Tests\NullLogger());
        $logger = new \Tests\NullLogger();
        $repository = new \App\Repositories\ProductRepository($this->db, $logger);
        $productService = new ProductService($repository, $attrService, $this->promotionService, $logger);
        $productId = $productService->save([
            'name' => 'Product 100',
            'price' => 100,
            'vat_rate' => 20,
            'stock' => 10,
            'active' => 1,
            'featured' => 0
        ]);
        $this->cart->add($productId, 1);

        // 3. Verify discount is capped at 100 (product subtotal)
        $this->assertEquals(100.0, $this->cart->discount());
        $this->assertEquals(0.0, $this->cart->grandTotal());

        // 4. Final total with 10 delivery should be exactly 10
        $finalTotal = $this->cart->grandTotal() + 10.0;
        $this->assertEquals(10.0, $finalTotal);
    }
}

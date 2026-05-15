<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PromotionService;
use App\Models\Promotion;
use App\Models\CartItem;
use App\Models\Product;
use App\Core\Database;

class PromotionServiceTest extends TestCase {
    private PromotionService $service;
    private \App\Services\ProductService $productService;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $this->db->exec("DELETE FROM promotion_targets");
        $this->db->exec("DELETE FROM promotions");
        
        $logger = new \Tests\NullLogger();
        $vatService = new \App\Services\VatService();
        $settings = new \App\Services\SettingsService($this->db, $logger);
        $emailService = new \App\Services\EmailService($settings, $logger);
        $paymentService = new \App\Services\Payment\PaymentService($logger);
        $orderService = new \App\Services\OrderService($this->db, $logger, $vatService, $paymentService, $emailService);
        
        $categoryService = new \App\Services\CategoryService($this->db, $logger);
        $this->service = new PromotionService($this->db, $logger, $categoryService, $orderService);
        
        $attrService = new \App\Services\AttributeService($this->db, $logger);
        $repository = new \App\Repositories\ProductRepository($this->db, $logger);
        $this->productService = new \App\Services\ProductService($repository, $attrService, $this->service, $logger);
    }

    public function testSubcategoryProductQualifiesForParentCategoryPromotion() {
        // Seed data: Electronics (1) -> Laptops (4). Product 1 is in 4.
        $product = $this->productService->findById(1);
        $product->category_id = 4;

        $promoData = [
            'name' => 'Electronics Sale',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_CATEGORY,
            'active' => 1,
            'target_ids' => [1] // Targeted at parent
        ];
        $promoId = $this->service->save($promoData);
        $promotion = $this->service->findById($promoId);

        $this->assertTrue($this->service->isProductQualifying($product, $promotion));
    }

    public function testSubcategoryProductIsExcludedViaParentCategory() {
        $product = $this->productService->findById(1);
        $product->category_id = 4;

        $promoData = [
            'name' => 'Non-Electronics Sale',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 20,
            'target_type' => Promotion::TARGET_CATEGORY,
            'active' => 1,
            'target_ids' => [],
            'excluded_ids' => [1] // Excluded parent
        ];
        $promoId = $this->service->save($promoData);
        $promotion = $this->service->findById($promoId);

        $this->assertFalse($this->service->isProductQualifying($product, $promotion));
    }

    public function testPromotionProductVisibilityFiltering() {
        // Parent Category 1, Subcategory 4
        // Product 1 is in category 4
        $product1 = $this->productService->findById(1);
        $product1->category_id = 4;

        $promoData = [
            'name' => 'Parent Included Sub Excluded',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_CATEGORY,
            'active' => 1,
            'target_ids' => [1],
            'excluded_ids' => [4]
        ];
        $promoId = $this->service->save($promoData);
        $promotion = $this->service->findById($promoId);

        $this->assertFalse($this->service->isProductQualifying($product1, $promotion));
        
        // Simulate controller filtering
        $products = [$product1];
        $filtered = array_filter($products, fn($p) => $this->service->isProductQualifying($p, $promotion));
        
        $this->assertCount(0, $filtered, "Product in excluded subcategory should be filtered out of promotion list");
    }

    public function testCategoryPromotionWithEmptyTargetsMeansAllExceptExcluded() {
        // Product 1 is in Category 4 (Laptops)
        $product1 = $this->productService->findById(1);
        $product1->category_id = 4;

        // Product 2 is in Category 5 (Phones)
        $product2 = $this->productService->findById(2);
        $product2->category_id = 5;

        $promoData = [
            'name' => 'Everything Except Laptops',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_CATEGORY,
            'active' => 1,
            'target_ids' => [], // All categories
            'excluded_ids' => [4] // Except Laptops
        ];
        $promoId = $this->service->save($promoData);
        $promotion = $this->service->findById($promoId);

        // Product 1 (Laptop) should NOT qualify
        $this->assertFalse($this->service->isProductQualifying($product1, $promotion));
        
        // Product 2 (Phone) SHOULD qualify
        $this->assertTrue($this->service->isProductQualifying($product2, $promotion));
    }

    public function testSaveAndFind() {
        $data = [
            'name' => 'Test Promo',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_PRODUCT,
            'code' => 'SAVE10',
            'active' => 1,
            'min_order_amount' => 50,
            'target_ids' => [1, 2]
        ];

        $id = $this->service->save($data);
        $this->assertGreaterThan(0, $id);

        $promo = $this->service->findById($id);
        $this->assertEquals('Test Promo', $promo->name);
        $this->assertEquals('SAVE10', $promo->code);
        $this->assertEquals([1, 2], $promo->target_ids);
    }

    public function testSaveWithDuplicateTargets() {
        $data = [
            'name' => 'Duplicate Test',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 5,
            'target_type' => Promotion::TARGET_PRODUCT,
            'active' => 1,
            'target_ids' => [10, 10, 11, 10] // Duplicates
        ];

        $id = $this->service->save($data);
        $promo = $this->service->findById($id);
        
        $this->assertEquals(2, count($promo->target_ids));
        $this->assertTrue(in_array(10, $promo->target_ids));
        $this->assertTrue(in_array(11, $promo->target_ids));
    }

    public function testCalculateDiscountPercentage() {
        $promo = new Promotion(new \Tests\NullLogger());
        $promo->type = Promotion::TYPE_PERCENTAGE;
        $promo->value = 10;
        $promo->target_type = Promotion::TARGET_ORDER;
        $promo->active = 1;
        $promo->min_order_amount = 0;

        $discount = $this->service->calculateDiscount($promo, [], 100.0);
        $this->assertEquals(10.0, $discount);
    }

    public function testCalculateDiscountFixed() {
        $promo = new Promotion(new \Tests\NullLogger());
        $promo->type = Promotion::TYPE_FIXED_AMOUNT;
        $promo->value = 15;
        $promo->target_type = Promotion::TARGET_ORDER;
        $promo->active = 1;
        $promo->min_order_amount = 0;

        $discount = $this->service->calculateDiscount($promo, [], 100.0);
        $this->assertEquals(15.0, $discount);
    }

    public function testMinOrderAmount() {
        $promo = new Promotion(new \Tests\NullLogger());
        $promo->type = Promotion::TYPE_FIXED_AMOUNT;
        $promo->value = 10;
        $promo->target_type = Promotion::TARGET_ORDER;
        $promo->active = 1;
        $promo->min_order_amount = 50;

        $this->assertEquals(0.0, $this->service->calculateDiscount($promo, [], 40.0));
        $this->assertEquals(10.0, $this->service->calculateDiscount($promo, [], 60.0));
    }

    public function testProductTargets() {
        $promo = new Promotion(new \Tests\NullLogger());
        $promo->type = Promotion::TYPE_PERCENTAGE;
        $promo->value = 50;
        $promo->target_type = Promotion::TARGET_PRODUCT;
        $promo->active = 1;
        $promo->min_order_amount = 0;
        $promo->target_ids = [1]; // Only product 1

        $product1 = new Product(new \Tests\NullLogger());
        $product1->id = 1;
        $product1->price = 100.0;
        $product1->vat_rate = 20;

        $product2 = new Product(new \Tests\NullLogger());
        $product2->id = 2;
        $product2->price = 100.0;
        $product2->vat_rate = 20;

        $item1 = new CartItem(new \Tests\NullLogger());
        $item1->product_id = 1;
        $item1->qty = 1;
        $item1->unit_price = 100.0;
        $item1->product = $product1;

        $item2 = new CartItem(new \Tests\NullLogger());
        $item2->product_id = 2;
        $item2->qty = 1;
        $item2->unit_price = 100.0;
        $item2->product = $product2;

        $cartItems = [$item1, $item2];
        $subtotal = 200.0;

        // 50% of product 1 ($100) = $50. Product 2 ($100) is ignored.
        $this->assertEquals(50.0, $this->service->calculateDiscount($promo, $cartItems, $subtotal));
    }

    public function testDateValidity() {
        $promo = new Promotion(new \Tests\NullLogger());
        $promo->active = 1;
        $promo->start_date = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $this->assertFalse($promo->isActive());

        $promo->start_date = date('Y-m-d H:i:s', strtotime('-1 day'));
        $promo->end_date = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $this->assertFalse($promo->isActive());

        $promo->end_date = date('Y-m-d H:i:s', strtotime('+1 day'));
        $this->assertTrue($promo->isActive());
    }

    public function testDateValidityWithT() {
        $promo = new Promotion(new \Tests\NullLogger());
        $promo->active = 1;
        
        $today = date('Y-m-d');
        
        // Testing isActive() with a future date containing T
        $promo->start_date = date('Y-m-d', strtotime('+1 day')) . 'T10:00';
        $this->assertFalse($promo->isActive(), "Future promotion with T should not be active");

        // Testing isActive() with a past end_date containing T
        $promo->start_date = date('Y-m-d', strtotime('-2 days')) . 'T10:00';
        $promo->end_date = date('Y-m-d', strtotime('-1 day')) . 'T10:00';
        $this->assertFalse($promo->isActive(), "Expired promotion with T should not be active");

        // Testing isActive() with currently valid range containing T
        $promo->start_date = date('Y-m-d', strtotime('-1 day')) . 'T10:00';
        $promo->end_date = date('Y-m-d', strtotime('+1 day')) . 'T10:00';
        $this->assertTrue($promo->isActive(), "Currently valid promotion with T should be active");
    }

    public function testDateNormalizationOnSave() {
        $data = [
            'name' => 'Normalization Test',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_ORDER,
            'start_date' => '2026-05-05T10:00',
            'end_date' => '2026-05-06T10:00',
            'active' => 1
        ];

        $id = $this->service->save($data);
        
        $stmt = $this->db->prepare("SELECT start_date, end_date FROM promotions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $this->assertEquals('2026-05-05 10:00', $row['start_date']);
        $this->assertEquals('2026-05-06 10:00', $row['end_date']);
    }

    public function testClearsTargetsWhenSwitchingToOrderType() {
        $data = [
            'name' => 'Product Promo',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_PRODUCT,
            'active' => 1,
            'target_ids' => [1, 2]
        ];

        $id = $this->service->save($data);
        $promo = $this->service->findById($id);
        $this->assertEquals([1, 2], $promo->target_ids);

        $updateData = [
            'name' => 'Order Promo',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_ORDER,
            'active' => 1,
            'target_ids' => [1, 2]
        ];

        $this->service->save($updateData, $id);
        $updatedPromo = $this->service->findById($id);
        $this->assertEquals([], $updatedPromo->target_ids);
    }

    public function testCalculateBogoDiscount() {
        $promo = new Promotion(new \Tests\NullLogger());
        $promo->type = Promotion::TYPE_BUY_X_GET_Y;
        $promo->value = 100; // 100% off (Free)
        $promo->buy_qty = 2;
        $promo->get_qty = 1;
        $promo->target_type = Promotion::TARGET_ORDER;
        $promo->active = 1;
        $promo->min_order_amount = 0;

        // 3 items of $100 each. Bundle 2+1=3. 1 item should be free.
        $item1 = $this->createCartItem(1, 3, 100.0);
        $discount = $this->service->calculateDiscount($promo, [$item1], 300.0);
        $this->assertEquals(100.0, $discount);

        // 5 items of $100 each. Still 1 bundle (3 items). 1 item free.
        $item2 = $this->createCartItem(1, 5, 100.0);
        $discount = $this->service->calculateDiscount($promo, [$item2], 500.0);
        $this->assertEquals(100.0, $discount);

        // 6 items of $100 each. 2 bundles. 2 items free.
        $item3 = $this->createCartItem(1, 6, 100.0);
        $discount = $this->service->calculateDiscount($promo, [$item3], 600.0);
        $this->assertEquals(200.0, $discount);
    }

    public function testCalculateBogoWithDifferentPrices() {
        $promo = new Promotion(new \Tests\NullLogger());
        $promo->type = Promotion::TYPE_BUY_X_GET_Y;
        $promo->value = 100;
        $promo->buy_qty = 1;
        $promo->get_qty = 1; // Buy 1 Get 1 Free
        $promo->target_type = Promotion::TARGET_ORDER;
        $promo->active = 1;
        $promo->min_order_amount = 0;

        // Items: $10, $20. One bundle. Cheapest ($10) should be free.
        $item1 = $this->createCartItem(1, 1, 10.0);
        $item2 = $this->createCartItem(2, 1, 20.0);
        $discount = $this->service->calculateDiscount($promo, [$item1, $item2], 300.0);
        $this->assertEquals(10.0, $discount);
    }

    private function createCartItem(int $productId, int $qty, float $price): CartItem {
        $product = new Product(new \Tests\NullLogger());
        $product->id = $productId;
        $product->price = $price;

        $item = new CartItem(new \Tests\NullLogger());
        $item->product_id = $productId;
        $item->qty = $qty;
        $item->unit_price = $price;
        $item->product = $product;
        return $item;
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CartServiceInterface;
use App\Services\CartService;

use App\Core\Database;
use App\Services\AuthService;
use App\Services\ProductService;
use App\Services\SettingsService;
use App\Services\AttributeService;

class CartServiceTest extends TestCase {
    private CartServiceInterface $cart;

    public function setUp() {
        $db = Database::getConnection();
        $db->exec("DELETE FROM cart_items");
        $db->exec("DELETE FROM carts");
        $_SESSION = [];
        $logger = new \Tests\NullLogger();
        $settingsRepository = new \App\Repositories\SettingsRepository($db);
        $settings = new SettingsService($settingsRepository, $logger);
        $authRepository = new \App\Repositories\AuthRepository($db, $logger);
        $auth = new AuthService($authRepository, $settings, $logger);
        $vatService = new \App\Services\VatService();
        $emailService = new \App\Services\EmailService($settings, $logger);
        $paymentService = new \App\Services\Payment\PaymentService($logger);
        $orderRepository = new \App\Repositories\OrderRepository($db, $logger);
        $orderService = new \App\Services\OrderService($orderRepository, $logger, $vatService, $paymentService, $emailService);
        $attrRepository = new \App\Repositories\AttributeRepository($db, $logger);
        $attrService = new AttributeService($attrRepository, $logger);
        
        $categoryRepo = new \App\Repositories\CategoryRepository($db, $logger);
        $categoryService = new \App\Services\CategoryService($categoryRepo, $logger);
        $promoEvaluator = new \App\Services\PromotionEvaluator($categoryService);
        $pricingService = new \App\Services\PricingService($vatService, $promoEvaluator, $settings);

        $promotionRepository = new \App\Repositories\PromotionRepository($db, $logger);
        $promoService = new \App\Services\PromotionService($promotionRepository, $promoEvaluator, $logger, null, $orderService);
        $repository = new \App\Repositories\ProductRepository($db, $logger);
        $variantService = new \App\Services\ProductVariantService($repository, $attrService);
        $productService = new ProductService($repository, $attrService, $promoService, $variantService, $logger);
        $cartRepository = new \App\Repositories\CartRepository($db);
        $this->cart = new CartService($cartRepository, $productService, $auth, $pricingService, $promoService, $orderService, $logger);
    }

    public function testAdd() {
        $this->cart->add(1, 2);
        $this->assertEquals([1 => 2], $this->cart->get());

        $this->cart->add(1, 1);
        $this->assertEquals([1 => 3], $this->cart->get());

        $this->cart->add(2, 5);
        $this->assertEquals([1 => 3, 2 => 5], $this->cart->get());
    }

    public function testRemove() {
        $this->cart->add(1, 3);
        $this->cart->add(2, 5);
        
        $this->cart->remove(1);
        $this->assertEquals([2 => 5], $this->cart->get());

        $this->cart->remove(2);
        $this->assertEquals([], $this->cart->get());
    }

    public function testUpdate() {
        $this->cart->add(1, 1);
        $this->cart->update(1, 10);
        $this->assertEquals([1 => 10], $this->cart->get());

        $this->cart->update(1, 0);
        $this->assertEquals([], $this->cart->get());
    }

    public function testCount() {
        $this->cart->add(1, 2);
        $this->cart->add(2, 3);
        $this->assertEquals(5, $this->cart->count());

        $this->cart->clear();
        $this->assertEquals(0, $this->cart->count());
    }

    public function testItems() {
        // Product IDs 1 and 2 are usually seeded in this project's test db
        $this->cart->add(1, 2);
        $items = $this->cart->items();
        
        $this->assertCount(1, $items);
        $this->assertInstanceOf(\App\Models\Product::class, $items[0]['product']);
        $this->assertEquals(2, $items[0]['qty']);
        $this->assertEquals(1, $items[0]['product']->id);
    }

    public function testTotal() {
        $this->cart->add(1, 1);
        $items = $this->cart->items();
        $expected = $items[0]['product']->price;
        $this->assertEquals($expected, $this->cart->total());
    }

    public function testRecoveryEmailFlagReset() {
        $db = Database::getConnection();
        
        // 1. Add item and manually set the recovery_email_sent_at flag
        $this->cart->add(1, 1);
        $stmt = $db->query("SELECT id FROM carts LIMIT 1");
        $cartId = $stmt->fetchColumn();
        
        $db->prepare("UPDATE carts SET recovery_email_sent_at = CURRENT_TIMESTAMP WHERE id = ?")
           ->execute([$cartId]);
        
        $sentAt = $db->query("SELECT recovery_email_sent_at FROM carts WHERE id = $cartId")->fetchColumn();
        $this->assertNotNull($sentAt);

        // 2. Modify cart and check if flag is reset
        $this->cart->add(1, 1); // increment qty
        $sentAt = $db->query("SELECT recovery_email_sent_at FROM carts WHERE id = $cartId")->fetchColumn();
        $this->assertNull($sentAt, "Flag should be reset after adding an item");

        // 3. Set flag again and test removal
        $db->prepare("UPDATE carts SET recovery_email_sent_at = CURRENT_TIMESTAMP WHERE id = ?")
           ->execute([$cartId]);
        $this->cart->remove('1');
        $sentAt = $db->query("SELECT recovery_email_sent_at FROM carts WHERE id = $cartId")->fetchColumn();
        $this->assertNull($sentAt, "Flag should be reset after removing an item");

        // 4. Set flag again and test update
        $this->cart->add(1, 1);
        $db->prepare("UPDATE carts SET recovery_email_sent_at = CURRENT_TIMESTAMP WHERE id = ?")
           ->execute([$cartId]);
        $this->cart->update('1', 5);
        $sentAt = $db->query("SELECT recovery_email_sent_at FROM carts WHERE id = $cartId")->fetchColumn();
        $this->assertNull($sentAt, "Flag should be reset after updating quantity");

        // 5. Set flag again and test clear (simulates post-order behavior)
        $db->prepare("UPDATE carts SET recovery_email_sent_at = CURRENT_TIMESTAMP WHERE id = ?")
           ->execute([$cartId]);
        $this->cart->clear();
        $sentAt = $db->query("SELECT recovery_email_sent_at FROM carts WHERE id = $cartId")->fetchColumn();
        $this->assertNull($sentAt, "Flag should be reset after clearing the cart");
    }
}

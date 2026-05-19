<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PromotionService;
use App\Services\OrderService;
use App\Models\Promotion;
use App\Models\User;
use App\Core\Database;
use Tests\NullLogger;

class FirstTimePromotionTest extends TestCase {
    private PromotionService $promotionService;
    private OrderService $orderService;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $this->db->exec("DELETE FROM promotions");
        $this->db->exec("DELETE FROM orders");
        
        $logger = new NullLogger();
        $cache = new \Tests\NullCache();
        $eventDispatcher = new \Tests\NullEventDispatcher();
        
        $vatService = new \App\Services\VatService();
        $settingsRepo = new \App\Repositories\SettingsRepository($this->db, $logger);
        $settings = new \App\Services\SettingsService($settingsRepo, $logger, $cache);
        $emailService = new \App\Services\EmailService($settings, $logger);
        $paymentService = new \App\Services\Payment\PaymentService($logger);
        $orderRepository = new \App\Repositories\OrderRepository($this->db, $logger);
        $this->orderService = new OrderService($orderRepository, $logger, $vatService, $paymentService, $emailService, $eventDispatcher);
        
        $categoryRepo = new \App\Repositories\CategoryRepository($this->db, $logger);
        $categoryService = new \App\Services\CategoryService($categoryRepo, $logger, $cache);
        $promoEvaluator = new \App\Services\PromotionEvaluator($categoryService);
        
        $promotionRepository = new \App\Repositories\PromotionRepository($this->db, $logger);
        $this->promotionService = new PromotionService($promotionRepository, $promoEvaluator, $logger, null, $this->orderService);
    }

    public function testFirstTimePromotionActiveForNewUser() {
        $user = new User(new NullLogger());
        $user->id = 1;
        $user->role = 'customer';

        $promo = new Promotion(new NullLogger());
        $promo->active = 1;
        $promo->target_role = Promotion::ROLE_FIRST_TIME;

        // User has no orders yet
        $this->assertTrue($promo->isActive($user, true), "Promo should be active for user with no orders");
    }

    public function testFirstTimePromotionInactiveForUserWithOrders() {
        $user = new User(new NullLogger());
        $user->id = 1;
        $user->role = 'customer';

        $promo = new Promotion(new NullLogger());
        $promo->active = 1;
        $promo->target_role = Promotion::ROLE_FIRST_TIME;

        // Simulate user has orders
        $this->assertFalse($promo->isActive($user, false), "Promo should be inactive for user with existing orders");
    }

    public function testFirstTimePromotionInService() {
        // Create a user in DB
        $this->db->prepare("INSERT INTO users (id, name, email, password_hash, role, is_verified) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([999, 'Test User', 'test@example.com', 'hash', 'customer', 1]);

        // Create a first-time promo
        $promoId = $this->promotionService->save([
            'name' => 'First Order 10% Off',
            'code' => 'FIRST10',
            'type' => Promotion::TYPE_PERCENTAGE,
            'value' => 10,
            'target_type' => Promotion::TARGET_ORDER,
            'target_role' => Promotion::ROLE_FIRST_TIME,
            'active' => 1
        ]);

        $user = new User(new NullLogger());
        $user->id = 999;
        $user->role = 'customer';

        // 1. New user (no orders in DB)
        $promo = $this->promotionService->validateCode('FIRST10', [], 100.0, $user);
        $this->assertNotNull($promo, "Service should validate FIRST10 for a new user");

        // 2. Create an order for this user
        $this->db->prepare("INSERT INTO orders (user_id, customer_name, customer_email, total, status) VALUES (?, ?, ?, ?, ?)")
            ->execute([$user->id, 'Test User', 'test@example.com', 100.0, 'pending']);

        // 3. Now it should be invalid
        $promo = $this->promotionService->validateCode('FIRST10', [], 100.0, $user);
        $this->assertNull($promo, "Service should NOT validate FIRST10 for a user who already has an order");
    }

    public function testFirstTimePromotionForGuest() {
        // Guests are considered first-time customers
        $promo = new Promotion(new NullLogger());
        $promo->active = 1;
        $promo->target_role = Promotion::ROLE_FIRST_TIME;

        $this->assertTrue($promo->isActive(null, true), "Promo should be active for guests (they are first-timers)");
    }
}

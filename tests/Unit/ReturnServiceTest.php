<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ReturnService;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\AttributeService;
use App\Services\VatService;
use App\Services\Payment\PaymentService;
use App\Services\Payment\ManualGateway;
use App\Services\EmailService;
use App\Services\SettingsService;
use App\Core\Database;
use App\Models\Order;
use App\Models\ReturnOrder;
use Tests\NullLogger;

class ReturnServiceTest extends TestCase {
    private ReturnService $returnService;
    private OrderService $orderService;
    private ProductService $productService;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $logger = new NullLogger();
        
        $container = \App\Core\Container::getInstance();
        $container->set(\PDO::class, fn() => $this->db);
        $container->set(\Psr\Log\LoggerInterface::class, fn() => $logger);
        
        $settingsRepository = new \App\Repositories\SettingsRepository($this->db);
        $settingsService = new SettingsService($settingsRepository, $logger, new \Tests\NullCache(), new \Tests\NullEventDispatcher());
        $container->set(\App\Services\SettingsService::class, fn() => $settingsService);
        $container->set(\App\Services\SettingsServiceInterface::class, fn() => $settingsService);

        $container->set(\App\Services\CurrencyServiceInterface::class, function() {
            return new \Tests\NullCurrencyService();
        });

        $container->set(\App\Services\PricingServiceInterface::class, function($c) {
            return new \App\Services\PricingService(
                new \App\Services\VatService(),
                new \App\Services\PromotionEvaluator(new \App\Services\CategoryService(
                    new \App\Repositories\CategoryRepository(\App\Core\Database::getConnection(), new \Tests\NullLogger()),
                    new \Tests\NullLogger(),
                    new \Tests\NullCache()
                )),
                $c->get(\App\Services\SettingsServiceInterface::class),
                $c->get(\App\Services\CurrencyServiceInterface::class)
            );
        });
        
        $emailService = new EmailService($settingsService, $logger);
        $paymentService = new PaymentService($logger);
        $paymentService->registerGateway(new ManualGateway());
        
        $vatService = new VatService();
        $orderRepository = new \App\Repositories\OrderRepository($this->db, $logger);
        $this->orderService = new OrderService($orderRepository, $logger, $vatService, $paymentService, $emailService, new \Tests\NullEventDispatcher());
        $attrRepository = new \App\Repositories\AttributeRepository($this->db, $logger);
        $attrService = new AttributeService($attrRepository, $logger, new \Tests\NullCache());
        $categoryRepo = new \App\Repositories\CategoryRepository($this->db, $logger);
        $categoryService = new \App\Services\CategoryService($categoryRepo, $logger, new \Tests\NullCache());
        $evaluator = new \App\Services\PromotionEvaluator($categoryService);
        $promotionRepository = new \App\Repositories\PromotionRepository($this->db, $logger);
        $promoService = new \App\Services\PromotionService($promotionRepository, $evaluator, $logger, new \Tests\NullCache());
        $repository = new \App\Repositories\ProductRepository($this->db, $logger);
        $variantService = new \App\Services\ProductVariantService($repository, $attrService, new \Tests\NullEventDispatcher());
        $this->productService = new ProductService($repository, $attrService, $promoService, $variantService, $logger, new \Tests\NullCache(), new \Tests\NullEventDispatcher());
        
        $returnRepository = new \App\Repositories\ReturnRepository($this->db, $logger);
        $this->returnService = new ReturnService(
            $returnRepository,
            $logger,
            $this->orderService,
            $paymentService,
            $emailService
        );
        \App\Core\Container::getInstance()->set(ProductServiceInterface::class, fn() => $this->productService);
    }

    public function testPartialRefundStatus() {
        // 1. Create an order with 2 items
        $product = $this->productService->findById(1);
        $orderData = [
            'user_id'          => 1,
            'customer_name'    => 'Test User',
            'customer_email'   => 'test@example.com',
            'total'            => 100.00,
            'total_vat_amount' => 16.67,
            'shipping_address' => '123 Test St',
            'notes'            => '',
            'delivery_method'  => 'Standard',
            'delivery_cost'    => 0.00
        ];
        $items = [
            ['product' => $product, 'qty' => 1, 'unit_price' => 50.00, 'vat_amount' => 8.33],
            ['product' => $product, 'qty' => 1, 'unit_price' => 50.00, 'vat_amount' => 8.34]
        ];
        $orderId = $this->orderService->create($orderData, $items);
        $order = $this->orderService->findById($orderId);
        
        // Move to delivered so it can be returned
        $this->orderService->updateStatus($orderId, Order::STATUS_DELIVERED);

        // 2. Create Return Request 1 (Item 1)
        $return1Id = $this->returnService->createReturnRequest($orderId, $order->user_id, [$order->items[0]->id => 1], 'Defective');
        
        // 3. Create Return Request 2 (Item 2)
        $return2Id = $this->returnService->createReturnRequest($orderId, $order->user_id, [$order->items[1]->id => 1], 'Changed mind');

        // 4. Approve Return 1
        $this->returnService->approveReturn($return1Id);
        $order = $this->orderService->findById($orderId);
        $this->assertEquals(Order::STATUS_PARTIAL_REFUND, $order->status);

        // 5. Reject Return 2
        $this->returnService->rejectReturn($return2Id, 'Too late');
        $order = $this->orderService->findById($orderId);
        
        // 6. Verify status is 'partial refund'
        $this->assertEquals(Order::STATUS_PARTIAL_REFUND, $order->status);
    }

    public function testPartialRefundStatusReverseOrder() {
        // 1. Create an order
        $product = $this->productService->findById(1);
        $orderData = [
            'user_id'          => 1,
            'customer_name'    => 'Test User',
            'customer_email'   => 'test@example.com',
            'total'            => 100.00,
            'total_vat_amount' => 16.67,
            'shipping_address' => '123 Test St',
            'notes'            => '',
            'delivery_method'  => 'Standard',
            'delivery_cost'    => 0.00
        ];
        $items = [
            ['product' => $product, 'qty' => 1, 'unit_price' => 50.00, 'vat_amount' => 8.33],
            ['product' => $product, 'qty' => 1, 'unit_price' => 50.00, 'vat_amount' => 8.34]
        ];
        $orderId = $this->orderService->create($orderData, $items);
        $order = $this->orderService->findById($orderId);
        $this->orderService->updateStatus($orderId, Order::STATUS_DELIVERED);

        // 2. Create Return Requests
        $return1Id = $this->returnService->createReturnRequest($orderId, $order->user_id, [$order->items[0]->id => 1], 'Defective');
        $return2Id = $this->returnService->createReturnRequest($orderId, $order->user_id, [$order->items[1]->id => 1], 'Changed mind');

        // 3. Reject Return 1 first
        $this->returnService->rejectReturn($return1Id, 'Not defective');
        $order = $this->orderService->findById($orderId);
        // It should be 'returning' because return2 is still pending, or 'not refunded' if we follow current logic
        // Current logic says if there are pending ones, stay 'returning'
        $this->assertEquals(Order::STATUS_RETURNING, $order->status);

        // 4. Approve Return 2
        $this->returnService->approveReturn($return2Id);
        $order = $this->orderService->findById($orderId);
        
        // 5. Verify status is 'partial refund'
        $this->assertEquals(Order::STATUS_PARTIAL_REFUND, $order->status);
    }

    public function testHistoryLogging() {
        // Create a user first
        $this->db->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)")
            ->execute(['Admin', 'admin@example.com', 'password', 'admin']);
        $adminId = (int)$this->db->lastInsertId();

        $product = $this->productService->findById(1);
        $orderData = [
            'user_id'          => $adminId,
            'customer_name'    => 'History User',
            'customer_email'   => 'history@example.com',
            'total'            => 50.00,
            'total_vat_amount' => 8.33,
            'shipping_address' => '123 History Lane',
            'notes'            => '',
            'delivery_method'  => 'Standard',
            'delivery_cost'    => 0.00
        ];
        $items = [['product' => $product, 'qty' => 1, 'unit_price' => 50.00, 'vat_amount' => 8.33]];
        $orderId = $this->orderService->create($orderData, $items);
        $order = $this->orderService->findById($orderId);
        $this->orderService->updateStatus($orderId, Order::STATUS_DELIVERED);

        $history = $this->orderService->getStatusHistory($orderId);
        $this->assertCount(2, $history);

        // Request return
        $returnId = $this->returnService->createReturnRequest($orderId, $order->user_id, [$order->items[0]->id => 1], 'Testing history');
        $history = $this->orderService->getStatusHistory($orderId);
        $this->assertCount(3, $history);
        $this->assertEquals(Order::STATUS_RETURNING, $history[0]['status']);
        $this->assertStringContainsString('Testing history', $history[0]['notes']);

        // Approve return
        $result = $this->returnService->approveReturn($returnId, false, $adminId); 
        $this->assertTrue($result, 'approveReturn should succeed');
        
        $history = $this->orderService->getStatusHistory($orderId);
        $this->assertCount(4, $history);
        $this->assertEquals($adminId, $history[0]['created_by_user_id']);
        $this->assertStringContainsString('approved', $history[0]['notes']);
    }
}

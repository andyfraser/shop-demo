<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OrderServiceInterface;
use App\Services\OrderService;
use App\Services\ProductServiceInterface;
use App\Services\ProductService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Core\Database;
use App\Services\VatService;
use App\Services\AttributeService;

use App\Services\Payment\PaymentService;
use App\Services\Payment\ManualGateway;
use App\Services\EmailService;
use App\Services\SettingsService;

class OrderServiceTest extends TestCase {
    private OrderServiceInterface $orderService;
    private ProductServiceInterface $productService;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $logger = new \Tests\NullLogger();
        
        $settingsService = new SettingsService($this->db, $logger);
        $emailService = new EmailService($settingsService, $logger);
        $paymentService = new PaymentService($logger);
        $paymentService->registerGateway(new ManualGateway());

        $this->orderService = new OrderService($this->db, $logger, new VatService(), $paymentService, $emailService);
        $attrService = new AttributeService($this->db, $logger);
        $promoService = new \App\Services\PromotionService($this->db, $logger);
        $repository = new \App\Repositories\ProductRepository($this->db, $logger);
        $this->productService = new ProductService($repository, $attrService, $promoService, $logger);
    }

    public function testCreateOrder() {
        $product = $this->productService->findById(1);
        $initialStock = $product->stock;

        $orderData = [
            'user_id'          => 1,
            'customer_name'    => 'Test User',
            'customer_email'   => 'test@example.com',
            'total'            => 120.00,
            'total_vat_amount' => 20.00,
            'shipping_address' => '123 Test St',
            'notes'            => 'Test notes',
            'delivery_method'  => 'Standard',
            'delivery_cost'    => 5.00
        ];

        $items = [
            [
                'product' => $product,
                'qty'     => 2,
                'unit_price' => $product->price,
                'vat_amount' => 20.00
            ]
        ];

        $orderId = $this->orderService->create($orderData, $items);
        $this->assertGreaterThan(0, $orderId);

        $order = $this->orderService->findById($orderId);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('pending', $order->status);
        $this->assertCount(1, $order->items);
        $this->assertInstanceOf(OrderItem::class, $order->items[0]);

        // Check stock was reduced
        $productAfter = $this->productService->findById(1);
        $this->assertEquals($initialStock - 2, $productAfter->stock);

        // Check history
        $history = $this->orderService->getStatusHistory($orderId);
        $this->assertCount(1, $history);
        $this->assertEquals('pending', $history[0]['status']);
        $this->assertEquals(1, $history[0]['created_by_user_id']);
    }

    public function testCreateGuestOrder() {
        $product = $this->productService->findById(1);
        $orderData = [
            'user_id'          => null,
            'customer_name'    => 'Guest User',
            'customer_email'   => 'guest@example.com',
            'total'            => 50.00,
            'total_vat_amount' => 8.33,
            'shipping_address' => '456 Guest Ln',
            'notes'            => '',
            'delivery_method'  => 'Standard',
            'delivery_cost'    => 0.00
        ];
        $items = [['product' => $product, 'qty' => 1, 'unit_price' => 50.00, 'vat_amount' => 8.33]];
        
        $orderId = $this->orderService->create($orderData, $items);
        $this->assertGreaterThan(0, $orderId);

        $history = $this->orderService->getStatusHistory($orderId);
        $this->assertCount(1, $history);
        $this->assertNull($history[0]['created_by_user_id']);
    }

    public function testUpdateStatus() {
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
        $items = [['product' => $product, 'qty' => 1, 'unit_price' => $product->price, 'vat_amount' => 16.67]];
        $orderId = $this->orderService->create($orderData, $items);

        $this->orderService->updateStatus($orderId, Order::STATUS_SHIPPED);
        $order = $this->orderService->findById($orderId);
        $this->assertEquals(Order::STATUS_SHIPPED, $order->status);
    }

    public function testCancelOrder() {
        $product = $this->productService->findById(1);
        $initialStock = $product->stock;

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
        $items = [['product' => $product, 'qty' => 1, 'unit_price' => $product->price, 'vat_amount' => 16.67]];
        $orderId = $this->orderService->create($orderData, $items);

        // Confirm and set payment method
        $this->orderService->updateStatus($orderId, Order::STATUS_CONFIRMED);
        $this->orderService->updatePaymentInfo($orderId, 'manual', 'paid', 'TEST-123');

        $productAfterCreate = $this->productService->findById(1);
        $this->assertEquals($initialStock - 1, $productAfterCreate->stock);

        // Cancel the order
        $success = $this->orderService->cancelOrder($orderId);
        $this->assertTrue($success);

        $order = $this->orderService->findById($orderId);
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);
        $this->assertEquals('refunded', $order->refund_status);
        $this->assertEquals(100.00, $order->refunded_amount);

        // Check stock was replenished
        $productAfterCancel = $this->productService->findById(1);
        $this->assertEquals($initialStock, $productAfterCancel->stock);
    }

    public function testStatusHistory() {
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
        $items = [['product' => $product, 'qty' => 1, 'unit_price' => $product->price, 'vat_amount' => 16.67]];
        $orderId = $this->orderService->create($orderData, $items);

        $this->orderService->updateStatus($orderId, Order::STATUS_CONFIRMED, 1, 'Confirmed by admin');
        $this->orderService->updateStatus($orderId, Order::STATUS_SHIPPED, null, 'Package sent');

        $history = $this->orderService->getStatusHistory($orderId);
        
        $this->assertCount(3, $history);
        $this->assertEquals(Order::STATUS_SHIPPED, $history[0]['status']);
        $this->assertEquals('Package sent', $history[0]['notes']);
        $this->assertEquals(Order::STATUS_CONFIRMED, $history[1]['status']);
        $this->assertEquals('Confirmed by admin', $history[1]['notes']);
        $this->assertEquals(1, $history[1]['created_by_user_id']);
        $this->assertEquals(Order::STATUS_PENDING, $history[2]['status']);
    }
}

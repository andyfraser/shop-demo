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

class OrderServiceTest extends TestCase {
    private OrderServiceInterface $orderService;
    private ProductServiceInterface $productService;
    private \PDO $db;

    public function setUp() {
        $this->db = Database::getConnection();
        $this->orderService = new OrderService($this->db, new \Tests\NullLogger());
        $this->productService = new ProductService($this->db, new \Tests\NullLogger());
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
                'qty'     => 2
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
    }

    public function testUpdateStatus() {
        $this->orderService->updateStatus(1, Order::STATUS_SHIPPED);
        $order = $this->orderService->findById(1);
        $this->assertEquals(Order::STATUS_SHIPPED, $order->status);
    }
}

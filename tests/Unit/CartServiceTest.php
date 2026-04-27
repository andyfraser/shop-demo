<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CartServiceInterface;
use App\Services\CartService;

use App\Core\Database;
use App\Services\AuthService;
use App\Services\ProductService;
use App\Services\SettingsService;

class CartServiceTest extends TestCase {
    private CartServiceInterface $cart;

    public function setUp() {
        $_SESSION['cart'] = [];
        $db = Database::getConnection();
        $logger = new \Tests\NullLogger();
        $settings = new SettingsService($db, $logger);
        $auth = new AuthService($db, $settings, $logger);
        $productService = new ProductService($db, $logger);
        $vatService = new \App\Services\VatService();
        $this->cart = new CartService($productService, $auth, $vatService);
    }

    public function testAdd() {
        $this->cart->add(1, 2);
        $this->assertEquals([1 => 2], $_SESSION['cart']);

        $this->cart->add(1, 1);
        $this->assertEquals([1 => 3], $_SESSION['cart']);

        $this->cart->add(2, 5);
        $this->assertEquals([1 => 3, 2 => 5], $_SESSION['cart']);
    }

    public function testRemove() {
        $_SESSION['cart'] = [1 => 3, 2 => 5];
        
        $this->cart->remove(1);
        $this->assertEquals([2 => 5], $_SESSION['cart']);

        $this->cart->remove(2);
        $this->assertEquals([], $_SESSION['cart']);
    }

    public function testUpdate() {
        $this->cart->update(1, 10);
        $this->assertEquals([1 => 10], $_SESSION['cart']);

        $this->cart->update(1, 0);
        $this->assertEquals([], $_SESSION['cart']);

        $this->cart->update(2, -5);
        $this->assertEquals([], $_SESSION['cart']);
    }

    public function testCount() {
        $_SESSION['cart'] = [1 => 2, 2 => 3];
        $this->assertEquals(5, $this->cart->count());

        $this->cart->clear();
        $this->assertEquals(0, $this->cart->count());
    }

    public function testItems() {
        // Product IDs 1 and 2 are usually seeded in this project's test db
        $_SESSION['cart'] = [1 => 2];
        $items = $this->cart->items();
        
        $this->assertCount(1, $items);
        $this->assertInstanceOf(\App\Models\Product::class, $items[0]['product']);
        $this->assertEquals(2, $items[0]['qty']);
        $this->assertEquals(1, $items[0]['product']->id);
    }

    public function testTotal() {
        $_SESSION['cart'] = [1 => 1];
        $items = $this->cart->items();
        $expected = $items[0]['product']->price;
        $this->assertEquals($expected, $this->cart->total());
    }
}

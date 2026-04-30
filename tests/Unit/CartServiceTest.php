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
        $settings = new SettingsService($db, $logger);
        $auth = new AuthService($db, $settings, $logger);
        $attrService = new AttributeService($db, $logger);
        $productService = new ProductService($db, $attrService, $logger);
        $vatService = new \App\Services\VatService();
        $this->cart = new CartService($db, $productService, $auth, $vatService, $logger);
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
}

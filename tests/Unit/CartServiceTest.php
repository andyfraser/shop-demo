<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CartService;

use App\Core\Database;
use App\Services\AuthService;

class CartServiceTest extends TestCase {
    private CartService $cart;

    public function setUp() {
        $_SESSION['cart'] = [];
        $db = Database::getConnection();
        $settings = new \App\Services\SettingsService($db);
        $auth = new AuthService($db, $settings);
        $this->cart = new CartService($db, $auth);
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
}

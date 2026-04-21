<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CartService;

class CartServiceTest extends TestCase {
    public function setUp() {
        $_SESSION['cart'] = [];
    }

    public function testAdd() {
        CartService::add(1, 2);
        $this->assertEquals([1 => 2], $_SESSION['cart']);

        CartService::add(1, 1);
        $this->assertEquals([1 => 3], $_SESSION['cart']);

        CartService::add(2, 5);
        $this->assertEquals([1 => 3, 2 => 5], $_SESSION['cart']);
    }

    public function testRemove() {
        $_SESSION['cart'] = [1 => 3, 2 => 5];
        
        CartService::remove(1);
        $this->assertEquals([2 => 5], $_SESSION['cart']);

        CartService::remove(2);
        $this->assertEquals([], $_SESSION['cart']);
    }

    public function testUpdate() {
        CartService::update(1, 10);
        $this->assertEquals([1 => 10], $_SESSION['cart']);

        CartService::update(1, 0);
        $this->assertEquals([], $_SESSION['cart']);

        CartService::update(2, -5);
        $this->assertEquals([], $_SESSION['cart']);
    }

    public function testCount() {
        $_SESSION['cart'] = [1 => 2, 2 => 3];
        $this->assertEquals(5, CartService::count());

        CartService::clear();
        $this->assertEquals(0, CartService::count());
    }
}

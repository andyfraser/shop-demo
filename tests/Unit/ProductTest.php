<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;

class ProductTest extends TestCase {
    public function testIsNew() {
        $product = new Product(new \Tests\NullLogger());
        
        // 7 days = 7 * 24 * 60 * 60 = 604800 seconds
        
        $product->created_at = date('Y-m-d H:i:s');
        $this->assertTrue($product->isNew());

        $product->created_at = date('Y-m-d H:i:s', time() - (6 * 24 * 60 * 60));
        $this->assertTrue($product->isNew());

        $product->created_at = date('Y-m-d H:i:s', time() - (8 * 24 * 60 * 60));
        $this->assertFalse($product->isNew());
    }

    public function testGetSubtotal() {
        $product = new Product(new \Tests\NullLogger());
        $product->price = 100.0;
        
        $this->assertEquals(200.0, $product->getSubtotal(2));
    }

    public function testGetVatAmount() {
        $product = new Product(new \Tests\NullLogger());
        $product->price = 120.0; // 100 + 20 VAT
        $product->vat_rate = 20.0;
        
        // VAT = 120 * (20 / 120) = 20
        $this->assertEquals(20.0, $product->getVatAmount(1));
        $this->assertEquals(40.0, $product->getVatAmount(2));
    }
}

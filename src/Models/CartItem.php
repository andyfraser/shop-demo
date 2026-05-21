<?php

namespace App\Models;

class CartItem extends Model {
    public int $id;
    public int $cart_id;
    public int $product_id;
    public ?int $variant_id = null;
    public int $qty;
    public ?string $metadata = null;
    public string $created_at;

    // Computed / Joined properties
    public string $key;
    public float $unit_price;

    // Related objects
    public ?Product $product = null;
    public ?ProductVariant $variant = null;

    public function getSubtotal(): float {
        if (!$this->product) return 0.0;
        $unitPrice = isset($this->unit_price) ? $this->unit_price : ($this->variant ? $this->variant->getEffectivePrice($this->product->price) : $this->product->price);
        return $unitPrice * $this->qty;
    }

    public function getVatAmount(): float {
        if (!$this->product) return 0.0;
        $subtotal = $this->getSubtotal();
        return $subtotal * ($this->product->vat_rate / (100 + $this->product->vat_rate));
    }
}

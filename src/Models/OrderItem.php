<?php

namespace App\Models;

class OrderItem extends Model {
    public int $id;
    public int $order_id;
    public int $product_id;
    public int $quantity;
    public float $unit_price;
    public float $vat_rate;
    public float $vat_amount;

    // Join fields
    public ?string $name = null; // product name
    public ?string $product_name = null; // some queries use product_name
    public ?string $slug = null; // product slug

    public function getSubtotal(): float {
        return $this->unit_price * $this->quantity;
    }
}

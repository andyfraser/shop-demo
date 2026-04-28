<?php

namespace App\Models;

class ReturnItem extends Model {
    public int $id;
    public int $return_id;
    public int $order_item_id;
    public int $quantity;

    // Join fields
    public ?string $product_name = null;
    public ?string $variant_name = null;
    public float $unit_price = 0.0;
}

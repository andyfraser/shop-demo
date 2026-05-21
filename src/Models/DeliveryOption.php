<?php

namespace App\Models;

class DeliveryOption extends Model {
    public int $id;
    public string $name;
    public float $price;
    public int|bool $active;
    public float $min_order_total;
    public ?string $target_role = null;

    public function isFree(): bool {
        return $this->price <= 0;
    }
}

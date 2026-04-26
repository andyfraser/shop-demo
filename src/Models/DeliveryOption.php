<?php

namespace App\Models;

class DeliveryOption {
    public int $id;
    public string $name;
    public float $price;
    public int|bool $active;
    public float $min_order_total;

    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    public function isFree(): bool {
        return $this->price <= 0;
    }
}

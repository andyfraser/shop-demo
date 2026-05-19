<?php

namespace App\Models;

class Currency extends Model {
    public int $id;
    public string $code;
    public string $name;
    public string $symbol;
    public float $exchange_rate;
    public bool $is_base;
    public bool $active;
    public string $created_at;
    public string $updated_at;

    public function convertFromBase(float $amount): float {
        return $amount * $this->exchange_rate;
    }

    public function convertToBase(float $amount): float {
        return $this->exchange_rate > 0 ? $amount / $this->exchange_rate : 0.0;
    }
}

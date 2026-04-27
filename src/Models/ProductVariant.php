<?php

namespace App\Models;

class ProductVariant extends Model {
    public int $id;
    public int $product_id;
    public string $name;
    public ?string $sku = null;
    public ?float $price = null;
    public int $stock;
    public int|bool $active;
    public string $created_at;

    /**
     * Get effective price (falling back to product price if null).
     */
    public function getEffectivePrice(float $productPrice): float {
        return $this->price ?? $productPrice;
    }

    /**
     * Format price for display.
     */
    public function formattedPrice(float $productPrice): string {
        return '£' . number_format($this->getEffectivePrice($productPrice), 2);
    }
}

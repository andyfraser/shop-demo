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
    public int $sort_order = 0;
    public string $created_at;
    public ?string $product_name = null;

    /**
     * @var int[] IDs of attribute values assigned to this variant.
     */
    public array $attribute_value_ids = [];

    /**
     * Get effective price (falling back to product price if null).
     */
    public function getEffectivePrice(float $productPrice): float {
        return $this->price ?? $productPrice;
    }

    /**
     * Get available stock (same as stock for variants).
     */
    public function getAvailableStock(): int {
        return $this->stock;
    }

    /**
     * Check if variant has low stock.
     */
    public function isLowStock(int $threshold): bool {
        return $this->stock <= $threshold;
    }

    /**
     * Format price for display.
     */
    public function formattedPrice(float $productPrice): string {
        return '£' . number_format($this->getEffectivePrice($productPrice), 2);
    }
}

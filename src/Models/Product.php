<?php

namespace App\Models;

class Product {
    public int $id;
    public string $name;
    public string $slug;
    public ?string $description = null;
    public float $price;
    public float $vat_rate;
    public int $stock;
    public ?int $category_id = null;
    public ?string $image = null;
    public int|bool $active;
    public int|bool $featured;
    public string $created_at;

    // Join fields (explicitly defined)
    public ?string $cat_name = null;
    public ?string $cat_slug = null;

    public function fill(array $data): self {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    /**
     * Calculate subtotal for a given quantity.
     */
    public function getSubtotal(int $qty): float {
        return $this->price * $qty;
    }

    /**
     * Calculate VAT amount for a given quantity.
     */
    public function getVatAmount(int $qty): float {
        return $this->getSubtotal($qty) * ($this->vat_rate / (100 + $this->vat_rate));
    }

    /**
     * Check if product has low stock.
     */
    public function isLowStock(int $threshold): bool {
        return $this->stock <= $threshold;
    }

    /**
     * Check if product is considered "new".
     */
    public function isNew(): bool {
        $ts = strtotime($this->created_at);
        return (time() - $ts) < (7 * 24 * 60 * 60);
    }

    /**
     * Format price for display.
     */
    public function formattedPrice(): string {
        return '£' . number_format($this->price, 2);
    }
}

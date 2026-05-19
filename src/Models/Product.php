<?php

namespace App\Models;

class Product extends Model {
    public int $id;
    public string $name;
    public string $slug;
    public ?string $sku = null;
    public ?string $description = null;
    public float $price;
    public float $vat_rate;
    public int $stock;
    public ?int $category_id = null;
    public ?string $image = null;
    public int|bool $active;
    public int|bool $featured;
    public int|bool $force_variant;
    public int|bool $is_bundle;
    public string $created_at;

    /**
     * @var \App\Models\ProductVariant[]
     */
    public array $variants = [];

    /**
     * @var array List of bundle components [{product, qty}]
     */
    public array $bundle_items = [];

    /**
     * @var int[] IDs of attributes that define variants for this product.
     */
    public array $variant_attribute_ids = [];

    // Join fields (explicitly defined)
    public ?string $cat_name = null;
    public ?string $cat_slug = null;
    public ?int $relevance_score = null;
    public ?int $variant_stock = null;

    /** @var \App\Models\Promotion[] */
    public array $active_promotions = [];

    /** @var array List of quantity tiers [{min_qty, discount}] */
    public array $tiers = [];

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
     * Get available stock, aggregating from variants.
     */
    public function getAvailableStock(): int {
        $vStock = 0;
        if (!empty($this->variants)) {
            $vStock = array_reduce($this->variants, fn($sum, $v) => $sum + $v->stock, 0);
        } elseif ($this->variant_stock !== null) {
            $vStock = $this->variant_stock;
        }

        if ($this->force_variant) {
            return $vStock;
        }
        
        return $this->stock + $vStock;
    }

    /**
     * Check if product has low stock.
     */
    public function isLowStock(int $threshold): bool {
        return $this->getAvailableStock() <= $threshold;
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
        return money($this->price);
    }
}

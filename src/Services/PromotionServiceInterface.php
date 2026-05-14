<?php

namespace App\Services;

use App\Models\Promotion;

interface PromotionServiceInterface {
    public function getAllForAdmin(): array;
    public function findById(int $id): ?Promotion;
    public function findByCode(string $code): ?Promotion;
    public function save(array|Promotion $data, int $id = 0): int;
    public function delete(int $id): void;
    
    /**
     * Get all automatic promotions that are currently active.
     * @return Promotion[]
     */
    public function getActiveAutomaticPromotions(): array;

    /**
     * Get all promotions that are currently active.
     * @param bool $onlyAutomatic
     * @return Promotion[]
     */
    public function getActivePromotions(bool $onlyAutomatic = false): array;

    /**
     * Validate a promo code against a cart and return the Promotion if valid.
     */
    public function validateCode(string $code, array $cartItems, float $subtotal, ?\App\Models\User $user = null): ?Promotion;

    /**
     * Calculate the discount amount for a promotion given cart items and current subtotal.
     */
    public function calculateDiscount(Promotion $promotion, array $cartItems, float $subtotal): float;

    /**
     * Check if a specific product qualifies for a promotion.
     */
    public function isProductQualifying(\App\Models\Product $product, Promotion $promotion): bool;
}

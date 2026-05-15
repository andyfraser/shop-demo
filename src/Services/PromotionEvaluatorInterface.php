<?php
namespace App\Services;

use App\Models\Promotion;
use App\Models\Product;

interface PromotionEvaluatorInterface {
    public function isProductQualifying(Product $product, Promotion $promotion): bool;
    
    /**
     * @param \App\Models\CartItem[] $cartItems
     */
    public function calculateDiscount(Promotion $promotion, array $cartItems, float $subtotal): float;
}

<?php

namespace App\Services;

use App\Models\Promotion;
use App\Models\Product;

class PromotionEvaluator implements PromotionEvaluatorInterface {
    public function __construct(
        private CategoryServiceInterface $categoryService
    ) {}

    public function isProductQualifying(Product $product, Promotion $promotion): bool {
        if ($promotion->target_type === Promotion::TARGET_ORDER) {
            return true;
        }

        // Exclusions take precedence
        if ($promotion->target_type === Promotion::TARGET_PRODUCT) {
            if (in_array($product->id, $promotion->excluded_ids)) return false;
            return empty($promotion->target_ids) || in_array($product->id, $promotion->target_ids);
        }
        
        if ($promotion->target_type === Promotion::TARGET_CATEGORY) {
            $categoryPath = [$product->category_id];
            if ($product->category_id) {
                $breadcrumb = $this->categoryService->getBreadcrumb($product->category_id);
                if (!empty($breadcrumb)) {
                    $categoryPath = array_map(fn($c) => (int)$c->id, $breadcrumb);
                }
            }

            // Exclusions: if any category in the path is excluded, the product is excluded
            foreach ($categoryPath as $catId) {
                if (in_array($catId, $promotion->excluded_ids)) return false;
            }

            // Targets: if any category in the path is targeted, the product qualifies
            if (empty($promotion->target_ids)) return true;
            foreach ($categoryPath as $catId) {
                if (in_array($catId, $promotion->target_ids)) return true;
            }
            return false;
        }

        return true;
    }

    public function calculateDiscount(Promotion $promotion, array $cartItems, float $subtotal): float {
        // Tiers can override the base value
        $value = $promotion->value;
        if (!empty($promotion->tiers)) {
            $sortedTiers = $promotion->tiers;
            usort($sortedTiers, fn($a, $b) => $b['min_amount'] <=> $a['min_amount']);
            foreach ($sortedTiers as $tier) {
                if ($subtotal >= $tier['min_amount']) {
                    $value = $tier['value'];
                    break;
                }
            }
        }

        if ($subtotal < $promotion->min_order_amount) {
            return 0.0;
        }

        $discount = 0.0;

        if ($promotion->target_type === Promotion::TARGET_ORDER) {
            if ($promotion->type === Promotion::TYPE_PERCENTAGE) {
                $discount = $subtotal * ($value / 100);
            } elseif ($promotion->type === Promotion::TYPE_FIXED_AMOUNT) {
                $discount = $value;
            } elseif ($promotion->type === Promotion::TYPE_BUY_X_GET_Y) {
                $qualifyingPrices = [];
                foreach ($cartItems as $item) {
                    for ($i = 0; $i < $item->qty; $i++) {
                        $qualifyingPrices[] = $item->unit_price;
                    }
                }
                $discount = $this->calculateBogoDiscount($promotion, $qualifyingPrices, $value);
            } elseif ($promotion->type === Promotion::TYPE_FREE_SHIPPING) {
                return 0.0;
            }
        } else {
            // Product or Category specific
            $qualifyingPrices = [];
            foreach ($cartItems as $item) {
                if ($this->isProductQualifying($item->product, $promotion)) {
                    if ($promotion->type === Promotion::TYPE_PERCENTAGE) {
                        $discount += ($item->unit_price * $item->qty) * ($value / 100);
                    } elseif ($promotion->type === Promotion::TYPE_FIXED_AMOUNT) {
                        $discount += $value * $item->qty;
                    } elseif ($promotion->type === Promotion::TYPE_BUY_X_GET_Y) {
                        for ($i = 0; $i < $item->qty; $i++) {
                            $qualifyingPrices[] = $item->unit_price;
                        }
                    }
                }
            }

            if ($promotion->type === Promotion::TYPE_BUY_X_GET_Y) {
                $discount = $this->calculateBogoDiscount($promotion, $qualifyingPrices, $value);
            }
        }

        return min($discount, $subtotal);
    }

    private function calculateBogoDiscount(Promotion $promotion, array $prices, ?float $valueOverride = null): float {
        if (empty($prices)) {
            return 0.0;
        }

        $value = $valueOverride ?? $promotion->value;
        sort($prices); // Cheapest first
        $totalUnits = count($prices);
        $bundleSize = $promotion->buy_qty + $promotion->get_qty;
        
        if ($bundleSize <= 0) {
            return 0.0;
        }

        $discountUnits = floor($totalUnits / $bundleSize) * $promotion->get_qty;
        $discount = 0.0;

        for ($i = 0; $i < $discountUnits; $i++) {
            $discount += $prices[$i] * ($value / 100);
        }

        return $discount;
    }
}

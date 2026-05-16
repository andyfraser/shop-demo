<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Promotion;

class PricingService implements PricingServiceInterface {
    public function __construct(
        private VatServiceInterface $vatService,
        private PromotionEvaluatorInterface $promotionEvaluator,
        private SettingsServiceInterface $settings
    ) {}

    public function calculateItemUnitPrice(CartItem $item): float {
        if (!$item->product) return 0.0;
        
        $baseUnitPrice = $item->variant ? $item->variant->getEffectivePrice($item->product->price) : $item->product->price;
        $unitPrice = $baseUnitPrice;

        // Apply quantity tiers (fixed discount from base/variant price)
        if (!empty($item->product->tiers)) {
            $applicableDiscount = 0.0;
            foreach ($item->product->tiers as $tier) {
                if ($item->qty >= $tier['min_qty']) {
                    $applicableDiscount = max($applicableDiscount, (float)$tier['discount']);
                }
            }
            $unitPrice = max(0, $baseUnitPrice - $applicableDiscount);
        }

        return $this->round($unitPrice);
    }

    public function calculateItemSubtotal(CartItem $item): float {
        $unitPrice = $this->calculateItemUnitPrice($item);
        return $this->round($unitPrice * $item->qty);
    }

    public function calculateItemVat(CartItem $item): float {
        if (!$item->product) return 0.0;
        $subtotal = $this->calculateItemSubtotal($item);
        return $this->round($this->vatService->calculateVatFromGross($subtotal, $item->product->vat_rate));
    }

    public function calculateTotalSubtotal(array $items): float {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $this->calculateItemSubtotal($item);
        }
        return $this->round($total);
    }

    public function calculateTotalVat(array $items): float {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $this->calculateItemVat($item);
        }
        return $this->round($total);
    }

    public function calculateDiscount(Promotion $promo, array $items, float $subtotal): float {
        return $this->round($this->promotionEvaluator->calculateDiscount($promo, $items, $subtotal));
    }

    public function calculateOrderTotals(float $subtotal, float $totalVat, float $discount, float $deliveryPrice, float $defaultVatRate): array {
        $grandTotal = max(0, $subtotal - $discount) + $deliveryPrice;
        
        $deliveryVat = $this->round($this->vatService->calculateVatFromGross($deliveryPrice, $defaultVatRate));
        
        // Proportional reduction of VAT based on discount
        $vatReductionFactor = $subtotal > 0 ? (1 - ($discount / $subtotal)) : 1;
        $finalVat = $this->round(($totalVat * $vatReductionFactor) + $deliveryVat);

        return [
            'grand_total' => $this->round($grandTotal),
            'total_vat'   => $finalVat
        ];
    }

    public function format(float $amount): string {
        $currency = $this->settings->get('currency_symbol', '$');
        return $currency . number_format($amount, 2);
    }

    private function round(float $amount): float {
        return round($amount, 2);
    }
}

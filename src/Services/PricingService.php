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

    public function calculateItemSubtotal(CartItem $item): float {
        if (!$item->product) return 0.0;
        $unitPrice = $item->variant ? $item->variant->getEffectivePrice($item->product->price) : $item->product->price;
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

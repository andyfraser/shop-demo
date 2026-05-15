<?php
namespace App\Services;

use App\Models\CartItem;
use App\Models\Promotion;

interface PricingServiceInterface {
    public function calculateItemSubtotal(CartItem $item): float;
    public function calculateItemVat(CartItem $item): float;
    public function calculateTotalSubtotal(array $items): float;
    public function calculateTotalVat(array $items): float;
    public function calculateDiscount(Promotion $promo, array $items, float $subtotal): float;
    public function calculateOrderTotals(float $subtotal, float $totalVat, float $discount, float $deliveryPrice, float $defaultVatRate): array;
    public function format(float $amount): string;
}

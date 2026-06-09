<?php
namespace App\View\Components;

use App\Core\BaseComponent;
use App\Services\CartServiceInterface;

class OrderSummary extends BaseComponent {
    public function __construct(
        private CartServiceInterface $cart,
        private bool $isCheckout = false,
        private array $items = [],
        private float $giftCardDiscount = 0.0,
        private float $deliveryCost = 0.0
    ) {
        if (empty($this->items)) {
            $this->items = $this->cart->items();
        }
    }

    protected function getTemplate(): string {
        return 'order_summary';
    }

    protected function getContext(): array {
        $total = $this->cart->total();
        $discount = $this->cart->discount();
        $grandTotal = $this->cart->grandTotal();
        $appliedPromotions = $this->cart->getAppliedPromotions();

        if ($this->isCheckout) {
            $totalItemVat = $this->cart->totalVat();
            $vatAmount = $totalItemVat * ($total > 0 ? (1 - ($discount / $total)) : 1);
        } else {
            $vatAmount = $this->cart->totalVat();
        }

        return [
            'cart' => $this->cart,
            'isCheckout' => $this->isCheckout,
            'items' => $this->items,
            'total' => $total,
            'discount' => $discount,
            'grandTotal' => $grandTotal,
            'applied_promotions' => $appliedPromotions,
            'giftCardDiscount' => $this->giftCardDiscount,
            'deliveryCost' => $this->deliveryCost,
            'vatAmount' => $vatAmount,
        ];
    }
}

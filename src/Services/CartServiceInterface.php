<?php
namespace App\Services;

interface CartServiceInterface {
    public function get(): array;
    public function add(int $productId, int $qty = 1, ?int $variantId = null, ?array $metadata = null): void;
    public function isVirtualOnly(): bool;
    public function remove(string $key): void;
    public function update(string $key, int $qty): void;
    public function clear(): void;
    public function count(): int;
    public function items(): array;
    public function total(): float;
    public function totalVat(): float;
    public function applyPromoCode(string $code): bool;
    public function removePromoCode(?string $code = null): void;
    public function getAppliedPromotions(): array;
    public function getPromotionDiscount(\App\Models\Promotion $promo): float;
    public function discount(): float;
    public function grandTotal(): float;
    public function syncOnLogin(int $userId): void;
}

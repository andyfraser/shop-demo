<?php
namespace App\Services;

interface CartServiceInterface {
    public function get(): array;
    public function add(int $productId, int $qty = 1, ?int $variantId = null): void;
    public function remove(string $key): void;
    public function update(string $key, int $qty): void;
    public function clear(): void;
    public function count(): int;
    public function items(): array;
    public function total(): float;
    public function totalVat(): float;
    public function applyPromoCode(string $code): bool;
    public function removePromoCode(): void;
    public function getAppliedPromotion(): ?\App\Models\Promotion;
    public function discount(): float;
    public function grandTotal(): float;
    public function syncOnLogin(int $userId): void;
}

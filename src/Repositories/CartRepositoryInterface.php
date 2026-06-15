<?php
namespace App\Repositories;

interface CartRepositoryInterface {
    public function getItems(int $cartId): array;
    public function addItem(int $cartId, int $productId, int $qty, ?int $variantId = null, ?string $metadata = null): void;
    public function removeItem(int $cartId, int $productId, ?int $variantId = null, ?string $metadata = null): void;
    public function updateItemQty(int $cartId, int $productId, int $qty, ?int $variantId = null, ?string $metadata = null): void;
    public function clearItems(int $cartId): void;
    public function applyPromoCode(int $cartId, string $code): bool;
    public function removePromoCode(int $cartId, ?string $code = null): void;
    public function getPromoCodes(int $cartId): array;
    public function findCartByUserId(int $userId): ?int;
    public function findCartBySessionId(string $sessionId): ?int;
    public function createCart(?int $userId, string $sessionId): int;
    public function updateLastActivity(int $cartId): void;
    public function attachCartToUser(int $cartId, int $userId): void;
    public function deleteCart(int $cartId): void;
    public function findAbandonedCarts(string $threshold): array;
    public function updateRecoveryEmailSentAt(int $cartId, string $sentAt): void;
}

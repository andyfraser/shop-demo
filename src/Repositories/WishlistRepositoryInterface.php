<?php
namespace App\Repositories;

interface WishlistRepositoryInterface {
    public function getUserProductIds(int $userId): array;
    public function add(int $userId, int $productId): bool;
    public function remove(int $userId, int $productId): bool;
    public function exists(int $userId, int $productId): bool;
    public function getSettings(int $userId): ?array;
    public function updateSettings(int $userId, bool $isPublic, string $shareHash): bool;
    public function getUserIdByShareHash(string $shareHash): ?int;
}

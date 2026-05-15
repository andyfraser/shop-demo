<?php
namespace App\Repositories;

interface WishlistRepositoryInterface {
    public function getUserProductIds(int $userId): array;
    public function add(int $userId, int $productId): bool;
    public function remove(int $userId, int $productId): bool;
    public function exists(int $userId, int $productId): bool;
}

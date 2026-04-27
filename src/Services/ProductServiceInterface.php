<?php
namespace App\Services;

use App\Models\Product;

interface ProductServiceInterface {
    public function getAllForAdmin(string $search = ''): array;
    public function findById(int $id): ?Product;
    public function findBySlug(string $slug): ?Product;
    public function save(array|Product $data, int $id = 0): int;
    public function deactivate(int $id): void;
    public function findByIds(array $ids): array;
    public function search(string $query, ?int $perPage, int $currentPage, string $sort): array;
    public function countSearch(string $query): int;
    public function getByCategory(array $categoryIds, ?int $perPage, int $currentPage, string $sort): array;
    public function countByCategory(array $categoryIds): int;
    public function getAllActive(?int $perPage, int $currentPage, string $sort): array;
    public function countAllActive(): int;
    public function getLowStock(int $threshold, int $limit = 10): array;
    public function getFeatured(int $limit = 8): array;
    public function getVariants(int $productId): array;
    public function findVariantById(int $variantId): ?\App\Models\ProductVariant;
    public function findVariantsByIds(array $ids): array;
    public function saveVariant(array $data, int $id = 0): int;
    public function deleteVariant(int $id): void;
}

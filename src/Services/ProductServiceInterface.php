<?php
namespace App\Services;

use App\Models\Product;

interface ProductServiceInterface {
    public function getAllForAdmin(\App\Core\QueryCriteria $criteria): array;
    public function findById(int $id): ?Product;
    public function findBySlug(string $slug): ?Product;
    public function save(array|Product $data, int $id = 0): int;
    public function updateStock(int $id, int $newStock): void;
    public function deactivate(int $id): void;
    public function findByIds(array $ids): array;
    public function search(\App\Core\QueryCriteria $criteria): array;
    public function countSearch(\App\Core\QueryCriteria $criteria): int;
    public function getByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): array;
    public function countByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): int;
    public function getAllActive(\App\Core\QueryCriteria $criteria): array;
    public function countAllActive(\App\Core\QueryCriteria $criteria): int;
    public function getAvailableFilters(array $categoryIds = [], string $query = ''): array;
    public function getLowStock(int $threshold, int $limit = 1000, string $sort = 'name'): array;
    public function countLowStock(int $threshold): int;
    public function getFeatured(int $limit = 8): array;
    public function getVariants(int $productId): array;
    public function findVariantById(int $variantId): ?\App\Models\ProductVariant;
    public function findVariantsByIds(array $ids): array;
    public function saveVariant(array $data, int $id = 0): int;
    public function updateVariantStock(int $id, int $newStock): void;
    public function deleteVariant(int $id): void;
    public function getRelatedProducts(int $productId, int $limit = 4): array;
    public function attachActivePromotions(array $products, ?\App\Models\User $user = null): void;
    public function searchSuggestions(string $query, int $limit = 5): array;
    public function syncTiers(int $productId, array $tiers): void;
    public function syncBundleItems(int $bundleId, array $items): void;
}

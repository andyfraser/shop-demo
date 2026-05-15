<?php
namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductVariant;

interface ProductRepositoryInterface {
    public function getAllForAdmin(\App\Core\QueryCriteria $criteria): array;
    public function findById(int $id): ?Product;
    public function findBySlug(string $slug): ?Product;
    public function save(array|Product $data, int $id = 0): int;
    public function deactivate(int $id): void;
    public function findByIds(array $ids): array;
    public function search(\App\Core\QueryCriteria $criteria): array;
    public function countSearch(\App\Core\QueryCriteria $criteria): int;
    public function getByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): array;
    public function countByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): int;
    public function getAllActive(\App\Core\QueryCriteria $criteria): array;
    public function countAllActive(\App\Core\QueryCriteria $criteria): int;
    public function getAvailableFilters(array $categoryIds = [], string $query = ''): array;
    public function getLowStock(int $threshold, int $limit = 10): array;
    public function getFeatured(int $limit = 8): array;
    public function getVariants(int $productId): array;
    public function findVariantById(int $variantId): ?ProductVariant;
    public function findVariantsByIds(array $ids): array;
    public function saveVariant(array $data, int $id = 0): int;
    public function deleteVariant(int $id): void;
    public function getRelatedProducts(int $productId, int $limit = 4): array;
    public function searchSuggestions(string $query, int $limit = 5): array;
}

<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class ProductService implements ProductServiceInterface {
    public function __construct(
        private ProductRepositoryInterface $repository,
        private AttributeServiceInterface $attributeService,
        private PromotionServiceInterface $promotionService,
        private LoggerInterface $logger
    ) {}

    public function attachActivePromotions(array $products, ?\App\Models\User $user = null): void {
        $activePromos = $this->promotionService->getActivePromotions(false, $user);
        if (empty($activePromos)) return;

        foreach ($products as $product) {
            $product->active_promotions = [];
            foreach ($activePromos as $promo) {
                if ($this->promotionService->isProductQualifying($product, $promo)) {
                    $product->active_promotions[] = $promo;
                }
            }
        }
    }

    public function getAllForAdmin(string $search = ''): array {
        return $this->repository->getAllForAdmin($search);
    }

    public function findById(int $id): ?Product {
        $product = $this->repository->findById($id);
        if ($product) {
            $product->variants = $this->getVariants($id);
            $product->variant_attribute_ids = $this->attributeService->getVariantAttributes($id);
        }
        return $product;
    }

    public function findBySlug(string $slug): ?Product {
        $product = $this->repository->findBySlug($slug);
        if ($product) {
            $product->variants = $this->getVariants($product->id);
            $product->variant_attribute_ids = $this->attributeService->getVariantAttributes($product->id);
        }
        return $product;
    }

    public function save(array|Product $data, int $id = 0): int {
        return $this->repository->save($data, $id);
    }

    public function deactivate(int $id): void {
        $this->repository->deactivate($id);
    }

    public function findByIds(array $ids): array {
        return $this->repository->findByIds($ids);
    }

    public function search(string $query, ?int $perPage, int $currentPage, string $sort, array $filters = []): array {
        return $this->repository->search($query, $perPage, $currentPage, $sort, $filters);
    }

    public function countSearch(string $query, array $filters = []): int {
        return $this->repository->countSearch($query, $filters);
    }

    public function getByCategory(array $categoryIds, ?int $perPage, int $currentPage, string $sort, array $filters = []): array {
        return $this->repository->getByCategory($categoryIds, $perPage, $currentPage, $sort, $filters);
    }

    public function countByCategory(array $categoryIds, array $filters = []): int {
        return $this->repository->countByCategory($categoryIds, $filters);
    }

    public function getAllActive(?int $perPage, int $currentPage, string $sort, array $filters = []): array {
        return $this->repository->getAllActive($perPage, $currentPage, $sort, $filters);
    }

    public function countAllActive(array $filters = []): int {
        return $this->repository->countAllActive($filters);
    }

    public function getAvailableFilters(array $categoryIds = [], string $query = ''): array {
        return $this->repository->getAvailableFilters($categoryIds, $query);
    }

    public function getLowStock(int $threshold, int $limit = 10): array {
        return $this->repository->getLowStock($threshold, $limit);
    }

    public function getFeatured(int $limit = 8): array {
        return $this->repository->getFeatured($limit);
    }

    public function getVariants(int $productId): array {
        $variants = $this->repository->getVariants($productId);
        foreach ($variants as $variant) {
            $variant->attribute_value_ids = $this->attributeService->getVariantAttributeValues($variant->id);
        }
        return $variants;
    }

    public function findVariantById(int $variantId): ?ProductVariant {
        return $this->repository->findVariantById($variantId);
    }

    public function findVariantsByIds(array $ids): array {
        return $this->repository->findVariantsByIds($ids);
    }

    public function saveVariant(array $data, int $id = 0): int {
        return $this->repository->saveVariant($data, $id);
    }

    public function deleteVariant(int $id): void {
        $this->repository->deleteVariant($id);
    }

    public function getRelatedProducts(int $productId, int $limit = 4): array {
        return $this->repository->getRelatedProducts($productId, $limit);
    }

    public function searchSuggestions(string $query, int $limit = 5): array {
        return $this->repository->searchSuggestions($query, $limit);
    }
}

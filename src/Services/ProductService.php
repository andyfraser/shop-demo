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
        private ProductVariantServiceInterface $variantService,
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

    public function getAllForAdmin(\App\Core\QueryCriteria $criteria): array {
        return $this->repository->getAllForAdmin($criteria);
    }

    public function findById(int $id): ?Product {
        $product = $this->repository->findById($id);
        if ($product) {
            $product->variants = $this->variantService->getVariants($id);
            $product->variant_attribute_ids = $this->attributeService->getVariantAttributes($id);
        }
        return $product;
    }

    public function findBySlug(string $slug): ?Product {
        $product = $this->repository->findBySlug($slug);
        if ($product) {
            $product->variants = $this->variantService->getVariants($product->id);
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

    public function search(\App\Core\QueryCriteria $criteria): array {
        return $this->repository->search($criteria);
    }

    public function countSearch(\App\Core\QueryCriteria $criteria): int {
        return $this->repository->countSearch($criteria);
    }

    public function getByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): array {
        return $this->repository->getByCategory($categoryIds, $criteria);
    }

    public function countByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): int {
        return $this->repository->countByCategory($categoryIds, $criteria);
    }

    public function getAllActive(\App\Core\QueryCriteria $criteria): array {
        return $this->repository->getAllActive($criteria);
    }

    public function countAllActive(\App\Core\QueryCriteria $criteria): int {
        return $this->repository->countAllActive($criteria);
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
        return $this->variantService->getVariants($productId);
    }

    public function findVariantById(int $variantId): ?ProductVariant {
        return $this->variantService->findById($variantId);
    }

    public function findVariantsByIds(array $ids): array {
        return $this->variantService->findByIds($ids);
    }

    public function saveVariant(array $data, int $id = 0): int {
        return $this->variantService->save($data, $id);
    }

    public function deleteVariant(int $id): void {
        $this->variantService->delete($id);
    }

    public function getRelatedProducts(int $productId, int $limit = 4): array {
        return $this->repository->getRelatedProducts($productId, $limit);
    }

    public function searchSuggestions(string $query, int $limit = 5): array {
        return $this->repository->searchSuggestions($query, $limit);
    }
}

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
            $this->hydrateProduct($product);
        }
        return $product;
    }

    public function findBySlug(string $slug): ?Product {
        $product = $this->repository->findBySlug($slug);
        if ($product) {
            $this->hydrateProduct($product);
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
        $products = $this->repository->search($criteria);
        foreach ($products as $product) {
            $this->hydrateProduct($product);
        }
        return $products;
    }

    public function countSearch(\App\Core\QueryCriteria $criteria): int {
        return $this->repository->countSearch($criteria);
    }

    public function getByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): array {
        $products = $this->repository->getByCategory($categoryIds, $criteria);
        foreach ($products as $product) {
            $this->hydrateProduct($product);
        }
        return $products;
    }

    public function countByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): int {
        return $this->repository->countByCategory($categoryIds, $criteria);
    }

    public function getAllActive(\App\Core\QueryCriteria $criteria): array {
        $products = $this->repository->getAllActive($criteria);
        foreach ($products as $product) {
            $this->hydrateProduct($product);
        }
        return $products;
    }

    public function countAllActive(\App\Core\QueryCriteria $criteria): int {
        return $this->repository->countAllActive($criteria);
    }

    public function getAvailableFilters(array $categoryIds = [], string $query = ''): array {
        return $this->repository->getAvailableFilters($categoryIds, $query);
    }

    public function getLowStock(int $threshold, int $limit = 10, string $sort = 'name'): array {
        $items = $this->repository->getLowStock($threshold, $limit, $sort);
        foreach ($items as $item) {
            if ($item instanceof Product) {
                $this->hydrateProduct($item);
            }
        }
        return $items;
    }

    public function countLowStock(int $threshold): int {
        return $this->repository->countLowStock($threshold);
    }

    public function getFeatured(int $limit = 8): array {
        $products = $this->repository->getFeatured($limit);
        foreach ($products as $product) {
            $this->hydrateProduct($product);
        }
        return $products;
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
        $products = $this->repository->getRelatedProducts($productId, $limit);
        foreach ($products as $product) {
            $this->hydrateProduct($product);
        }
        return $products;
    }

    public function searchSuggestions(string $query, int $limit = 5): array {
        return $this->repository->searchSuggestions($query, $limit);
    }

    private function hydrateProduct(Product $product): void {
        $product->variants = $this->variantService->getVariants($product->id);
        $product->variant_attribute_ids = $this->attributeService->getVariantAttributes($product->id);
    }
}

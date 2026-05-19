<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\ProductRepositoryInterface;
use App\Core\Events\EventDispatcherInterface;
use App\Events\StockUpdated;
use Psr\Log\LoggerInterface;

class ProductService implements ProductServiceInterface {
    public function __construct(
        private ProductRepositoryInterface $repository,
        private AttributeServiceInterface $attributeService,
        private PromotionServiceInterface $promotionService,
        private ProductVariantServiceInterface $variantService,
        private LoggerInterface $logger,
        private \App\Core\Cache\CacheInterface $cache,
        private EventDispatcherInterface $eventDispatcher
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
        $cacheKey = "product_hydrated_$id";
        $product = $this->cache->get($cacheKey);
        
        if ($product === null) {
            $product = $this->repository->findById($id);
            if ($product) {
                $this->hydrateProduct($product);
                $this->cache->set($cacheKey, $product, 3600);
            }
        }
        return $product;
    }

    public function findBySlug(string $slug): ?Product {
        $product = $this->repository->findBySlug($slug);
        if ($product) {
            // Slugs are dynamic but we can still use ID-based hydration cache if we find the product first
            $cacheKey = "product_hydrated_{$product->id}";
            $cachedProduct = $this->cache->get($cacheKey);
            if ($cachedProduct) return $cachedProduct;

            $this->hydrateProduct($product);
            $this->cache->set($cacheKey, $product, 3600);
        }
        return $product;
    }

    public function save(array|Product $data, int $id = 0): int {
        $resultId = $this->repository->save($data, $id);
        $this->clearCache($resultId ?: $id);
        $this->cache->delete('featured_products');
        return $resultId;
    }

    public function updateStock(int $id, int $newStock): void {
        $product = $this->findById($id);
        $oldStock = $product ? $product->stock : 0;

        $this->repository->updateStock($id, $newStock);
        $this->clearCache($id);

        $this->eventDispatcher->dispatch(new StockUpdated($id, $oldStock, $newStock, false));
    }

    public function deactivate(int $id): void {
        $this->repository->deactivate($id);
        $this->clearCache($id);
        $this->cache->delete('featured_products');
    }

    public function findByIds(array $ids): array {
        $products = [];
        foreach ($ids as $id) {
            $p = $this->findById($id);
            if ($p) $products[] = $p;
        }
        return $products;
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
        $cacheKey = 'available_filters_' . md5(json_encode($categoryIds) . $query);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;

        $filters = $this->repository->getAvailableFilters($categoryIds, $query);
        $this->cache->set($cacheKey, $filters, 3600);
        return $filters;
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
        $cacheKey = "featured_products_$limit";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;

        $products = $this->repository->getFeatured($limit);
        foreach ($products as $product) {
            $this->hydrateProduct($product);
        }
        $this->cache->set($cacheKey, $products, 3600);
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
        $variantId = $this->variantService->save($data, $id);
        $v = $this->findVariantById($variantId ?: $id);
        if ($v) $this->clearCache($v->product_id);
        return $variantId;
    }

    public function updateVariantStock(int $id, int $newStock): void {
        $this->variantService->updateStock($id, $newStock);
        $v = $this->findVariantById($id);
        if ($v) $this->clearCache($v->product_id);
    }

    public function deleteVariant(int $id): void {
        $v = $this->findVariantById($id);
        $productId = $v ? $v->product_id : 0;
        $this->variantService->delete($id);
        if ($productId) $this->clearCache($productId);
    }

    public function getRelatedProducts(int $productId, int $limit = 4): array {
        $cacheKey = "related_products_{$productId}_{$limit}";
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) return $cached;

        $products = $this->repository->getRelatedProducts($productId, $limit);
        foreach ($products as $product) {
            $this->hydrateProduct($product);
        }
        $this->cache->set($cacheKey, $products, 3600);
        return $products;
    }

    public function searchSuggestions(string $query, int $limit = 5): array {
        return $this->repository->searchSuggestions($query, $limit);
    }

    public function syncTiers(int $productId, array $tiers): void {
        $this->repository->syncTiers($productId, $tiers);
        $this->clearCache($productId);
    }

    public function syncBundleItems(int $bundleId, array $items): void {
        $this->repository->syncBundleItems($bundleId, $items);
        $this->clearCache($bundleId);
    }

    public function clearCache(int $productId): void {
        if (!$productId) return;
        $this->cache->delete("product_hydrated_$productId");
        // Related products cache might also be affected if this product was in a related list
        // For simplicity we could clear them all or just let them expire. 
        // Given this is a demo, let's keep it simple.
    }

    private function hydrateProduct(Product $product): void {
        $product->variants = $this->variantService->getVariants($product->id);
        $product->variant_attribute_ids = $this->attributeService->getVariantAttributes($product->id);
        $product->tiers = $this->repository->getTiers($product->id);
        
        if ($product->is_bundle) {
            $product->bundle_items = $this->repository->getBundleItems($product->id);
        }
    }
}

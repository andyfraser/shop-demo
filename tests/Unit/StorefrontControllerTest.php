<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Controllers\StorefrontController;
use App\Core\Request;
use App\Core\Responses\HtmlResponse;
use App\Models\Product;
use Tests\NullLogger;

class StorefrontControllerTest extends TestCase {
    public function testProductQuickViewReturnsPartial() {
        $product = new Product(new NullLogger());
        $product->id = 123;
        $product->name = 'Test Product';
        $product->slug = 'test-product';
        $product->price = 10.0;

        $productService = new class($product) implements \App\Services\ProductServiceInterface {
            public function __construct(private Product $prod) {}
            public function findBySlug(string $slug): ?Product { return $this->prod; }
            public function attachActivePromotions(array $products, ?\App\Models\User $user = null): void {}
            public function getAllForAdmin(\App\Core\QueryCriteria $criteria): array { return []; }
            public function findById(int $id): ?Product { return $this->prod; }
            public function save(array|\App\Models\Product $data, int $id = 0): int { return 0; }
            public function updateStock(int $id, int $newStock): void {}
            public function deactivate(int $id): void {}
            public function findByIds(array $ids): array { return []; }
            public function search(\App\Core\QueryCriteria $criteria): array { return []; }
            public function countSearch(\App\Core\QueryCriteria $criteria): int { return 0; }
            public function getByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): array { return []; }
            public function countByCategory(array $categoryIds, \App\Core\QueryCriteria $criteria): int { return 0; }
            public function getAllActive(\App\Core\QueryCriteria $criteria): array { return []; }
            public function countAllActive(\App\Core\QueryCriteria $criteria): int { return 0; }
            public function getAvailableFilters(array $categoryIds = [], string $query = ''): array { return []; }
            public function getLowStock(int $threshold, int $limit = 1000, string $sort = 'name'): array { return []; }
            public function countLowStock(int $threshold): int { return 0; }
            public function getFeatured(int $limit = 8): array { return []; }
            public function getVariants(int $productId): array { return []; }
            public function findVariantById(int $variantId): ?\App\Models\ProductVariant { return null; }
            public function findVariantsByIds(array $ids): array { return []; }
            public function saveVariant(array $data, int $id = 0): int { return 0; }
            public function updateVariantStock(int $id, int $newStock): void {}
            public function deleteVariant(int $id): void {}
            public function getRelatedProducts(int $productId, int $limit = 4): array { return []; }
            public function searchSuggestions(string $query, int $limit = 5): array { return []; }
            public function syncTiers(int $productId, array $tiers): void {}
            public function syncBundleItems(int $bundleId, array $items): void {}
            public function clearCache(int $productId): void {}
            public function syncPurchasableStatus(int $productId): void {}
        };

        $categoryService = new class implements \App\Services\CategoryServiceInterface {
            public function getAllForAdmin(): array { return []; }
            public function getAll(): array { return []; }
            public function findById(int $id): ?\App\Models\Category { return null; }
            public function findBySlug(string $slug): ?\App\Models\Category { return null; }
            public function getTree(): array { return []; }
            public function getFlat(): array { return []; }
            public function getSubcategories(int $parentId): array { return []; }
            public function getBreadcrumb(int $categoryId): array { return []; }
            public function save(array|\App\Models\Category $data, int $id = 0): int { return 0; }
            public function delete(int $id): void {}
        };

        $wishlistService = new class implements \App\Services\WishlistServiceInterface {
            public function getUserWishlist(int $userId): array { return []; }
            public function addToWishlist(int $userId, int $productId): bool { return true; }
            public function removeFromWishlist(int $userId, int $productId): bool { return true; }
            public function isInWishlist(int $userId, int $productId): bool { return false; }
            public function getSettings(int $userId): array { return []; }
            public function togglePrivacy(int $userId, bool $isPublic): array { return []; }
            public function getSharedWishlist(string $shareHash): ?array { return null; }
        };

        $authService = new class implements \App\Services\AuthServiceInterface {
            public function sessionStart(): void {}
            public function currentUser(): ?\App\Models\User { return null; }
            public function isAdmin(): bool { return false; }
            public function login(array|\App\Models\User $user, bool $remember = false): void {}
            public function logout(): void {}
            public function generateApiTokenForUser(\App\Models\User $user): string { return ''; }
            public function verifyApiToken(string $token): ?\App\Models\User { return null; }
            public function revokeApiToken(string $token): bool { return true; }
        };

        $reviewService = new class implements \App\Services\ReviewServiceInterface {
            public function getByProductId(int $productId, bool $onlyApproved = true): array { return []; }
            public function submit(int $productId, int $userId, int $rating, ?string $comment): bool { return true; }
            public function getAllForAdmin(): array { return []; }
            public function updateStatus(int $reviewId, string $status): bool { return true; }
            public function getAverageRating(int $productId): float { return 4.5; }
        };

        $promotionService = new class implements \App\Services\PromotionServiceInterface {
            public function getAllForAdmin(): array { return []; }
            public function findById(int $id): ?\App\Models\Promotion { return null; }
            public function findByCode(string $code): ?\App\Models\Promotion { return null; }
            public function save(array|\App\Models\Promotion $data, int $id = 0): int { return 0; }
            public function delete(int $id): void {}
            public function getActiveAutomaticPromotions(?\App\Models\User $user = null): array { return []; }
            public function getActivePromotions(bool $onlyAutomatic = false, ?\App\Models\User $user = null): array { return []; }
            public function validateCode(string $code, array $cartItems, float $subtotal, ?\App\Models\User $user = null): ?\App\Models\Promotion { return null; }
            public function calculateDiscount(\App\Models\Promotion $promotion, array $cartItems, float $subtotal): float { return 0.0; }
            public function isProductQualifying(\App\Models\Product $product, \App\Models\Promotion $promotion): bool { return false; }
        };

        $cartService = new class implements \App\Services\CartServiceInterface {
            public function get(): array { return []; }
            public function add(int $productId, int $qty = 1, ?int $variantId = null, ?array $metadata = null): void {}
            public function isVirtualOnly(): bool { return false; }
            public function remove(string $key): void {}
            public function update(string $key, int $qty): void {}
            public function clear(): void {}
            public function count(): int { return 0; }
            public function items(): array { return []; }
            public function total(): float { return 0.0; }
            public function totalVat(): float { return 0.0; }
            public function applyPromoCode(string $code): bool { return true; }
            public function removePromoCode(?string $code = null): void {}
            public function getAppliedPromotions(): array { return []; }
            public function getPromotionDiscount(\App\Models\Promotion $promo): float { return 0.0; }
            public function discount(): float { return 0.0; }
            public function grandTotal(): float { return 0.0; }
            public function syncOnLogin(int $userId): void {}
        };

        $orderService = new class implements \App\Services\OrderServiceInterface {
            public function create(array $orderData, array $items): int { return 0; }
            public function findById(int $id): ?\App\Models\Order { return null; }
            public function getItems(int $orderId): array { return []; }
            public function getAllForAdmin(string $status = ''): array { return []; }
            public function find(\App\Core\QueryCriteria $criteria): array { return []; }
            public function count(\App\Core\QueryCriteria $criteria): int { return 0; }
            public function getForUser(int $userId): array { return []; }
            public function hasOrders(int $userId): bool { return false; }
            public function countAll(): int { return 0; }
            public function getTotalRevenue(): float { return 0.0; }
            public function getRecentOrders(int $limit = 10): array { return []; }
            public function updateStatus(int $id, string $status, ?int $userId = null, string $notes = ''): void {}
            public function addHistoryEntry(int $orderId, string $status, string $notes = '', ?int $userId = null): void {}
            public function updatePaymentInfo(int $id, string $method, string $status, ?string $transactionId = null): void {}
            public function updateRefundInfo(int $id, string $status, float $amount, bool $deliveryRefunded = false): void {}
            public function cancelOrder(int $id, string $reason = '', ?int $userId = null): bool { return true; }
            public function getStatusHistory(int $orderId): array { return []; }
        };

        $renderer = new class extends \App\Core\Renderer {
            public function __construct() {}
            public function renderPartial(string $template, array $vars = []): string {
                return "partial:" . $template . ":" . $vars['product']->name;
            }
        };

        $controller = new StorefrontController(
            $productService,
            $categoryService,
            $wishlistService,
            $authService,
            $reviewService,
            $promotionService,
            $cartService,
            $orderService,
            $renderer
        );

        $request = new Request(['quickview' => '1'], [], [], [], [], []);
        $response = $controller->product($request, 'test-product');

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals('partial:partials/product_quickview:Test Product', $response->getContent());
    }
}

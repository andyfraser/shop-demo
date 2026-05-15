<?php
namespace App\Services;

use App\Repositories\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

class CartService implements CartServiceInterface {
    public function __construct(
        private CartRepositoryInterface $repository,
        private ProductServiceInterface $productService,
        private AuthServiceInterface $auth,
        private PricingServiceInterface $pricingService,
        private PromotionServiceInterface $promotionService,
        private OrderServiceInterface $orderService,
        private LoggerInterface $logger
    ) {}

    public function get(): array {
        $cartId = $this->getCartId();
        if (!$cartId) return [];

        $rows = $this->repository->getItems($cartId);

        $cart = [];
        foreach ($rows as $row) {
            $key = $this->generateKey($row['product_id'], $row['variant_id']);
            $cart[$key] = $row['qty'];
        }
        return $cart;
    }

    public function add(int $productId, int $qty = 1, ?int $variantId = null): void {
        $cartId = $this->ensureCart();
        $this->repository->addItem($cartId, $productId, $qty, $variantId);
        $this->repository->updateLastActivity($cartId);
    }

    public function remove(string $key): void {
        $cartId = $this->getCartId();
        if (!$cartId) return;

        $parts = explode('-', $key);
        $pid = (int)$parts[0];
        $vid = isset($parts[1]) ? (int)$parts[1] : null;

        $this->repository->removeItem($cartId, $pid, $vid);
        $this->repository->updateLastActivity($cartId);
    }

    public function update(string $key, int $qty): void {
        if ($qty <= 0) { $this->remove($key); return; }

        $cartId = $this->getCartId();
        if (!$cartId) return;

        $parts = explode('-', $key);
        $pid = (int)$parts[0];
        $vid = isset($parts[1]) ? (int)$parts[1] : null;

        $this->repository->updateItemQty($cartId, $pid, $qty, $vid);
        $this->repository->updateLastActivity($cartId);
    }

    public function clear(): void {
        $cartId = $this->getCartId();
        if (!$cartId) return;

        $this->repository->clearItems($cartId);
        $this->repository->updateLastActivity($cartId);
    }

    public function count(): int {
        return array_sum($this->get());
    }

    public function items(): array {
        $cart = $this->get();
        if (empty($cart)) return [];
        
        $productIds = [];
        $variantIds = [];
        
        foreach (array_keys($cart) as $key) {
            $parts = explode('-', $key);
            $productIds[] = (int)$parts[0];
            if (isset($parts[1])) {
                $variantIds[] = (int)$parts[1];
            }
        }

        $products = [];
        foreach ($this->productService->findByIds(array_unique($productIds)) as $p) {
            $products[$p->id] = $p;
        }

        $variants = [];
        if (!empty($variantIds)) {
            foreach ($this->productService->findVariantsByIds(array_unique($variantIds)) as $v) {
                $variants[$v->id] = $v;
            }
        }

        $items = [];
        foreach ($cart as $key => $qty) {
            $parts = explode('-', $key);
            $pid = (int)$parts[0];
            $vid = isset($parts[1]) ? (int)$parts[1] : null;

            if (!isset($products[$pid])) continue;

            $product = $products[$pid];
            $variant = $vid ? ($variants[$vid] ?? null) : null;

            $unitPrice = $variant ? $variant->getEffectivePrice($product->price) : $product->price;

            $item = new \App\Models\CartItem($this->logger);
            $item->key = $key;
            $item->product_id = $pid;
            $item->variant_id = $vid;
            $item->qty = $qty;
            $item->product = $product;
            $item->variant = $variant;
            $item->unit_price = $unitPrice;

            $items[] = $item;
        }
        return $items;
    }

    public function total(): float {
        return $this->pricingService->calculateTotalSubtotal($this->items());
    }

    public function totalVat(): float {
        return $this->pricingService->calculateTotalVat($this->items());
    }

    public function applyPromoCode(string $code): bool {
        $promo = $this->promotionService->findByCode($code);
        $user = $this->auth->currentUser();
        $isFirstOrder = $user ? !$this->orderService->hasOrders($user->id) : true;

        if (!$promo || !$promo->isActive($user, $isFirstOrder)) {
            $this->logger->warning("Invalid or inactive promo code attempted: {code}", ['code' => $code]);
            return false;
        }

        $cartId = $this->ensureCart();
        $applied = $this->repository->applyPromoCode($cartId, $code);
        if ($applied) {
            $this->logger->info("Promo code {code} applied to cart {cartId}", ['code' => $code, 'cartId' => $cartId]);
        }
        return $applied;
    }

    public function removePromoCode(?string $code = null): void {
        $cartId = $this->getCartId();
        if (!$cartId) return;

        $this->repository->removePromoCode($cartId, $code);
        $this->logger->info("Promo code {code} removed from cart {cartId}", ['code' => $code ?: 'all', 'cartId' => $cartId]);
    }

    public function getAppliedPromotions(): array {
        $cartId = $this->getCartId();
        if (!$cartId) return [];

        $codes = $this->repository->getPromoCodes($cartId);

        $applied = [];
        $hasManualPromo = false;
        $user = $this->auth->currentUser();
        $isFirstOrder = $user ? !$this->orderService->hasOrders($user->id) : true;

        foreach ($codes as $code) {
            $promo = $this->promotionService->findByCode($code);
            if ($promo && $promo->isActive($user, $isFirstOrder)) {
                $promo->applied_code = $code;
                $applied[$promo->id] = $promo;
                $hasManualPromo = true;
            } else {
                $this->removePromoCode($code);
            }
        }

        // Check for automatic promotions
        $autoPromos = $this->promotionService->getActivePromotions(true, $user);
        foreach ($autoPromos as $promo) {
            $canApply = false;
            if ($hasManualPromo) {
                if ($promo->stackable) {
                    $canApply = true;
                }
            } else {
                if (empty($applied)) {
                    $canApply = true;
                } elseif ($promo->stackable) {
                    $canApply = true;
                }
            }

            if ($canApply && $promo->isActive($user, $isFirstOrder)) {
                if ($this->total() >= $promo->min_order_amount) {
                    if ($promo->target_type !== \App\Models\Promotion::TARGET_ORDER) {
                        $hasTarget = false;
                        foreach ($this->items() as $item) {
                            if ($this->promotionService->isProductQualifying($item->product, $promo)) {
                                $hasTarget = true;
                                break;
                            }
                        }
                        if (!$hasTarget) continue;
                    }
                    
                    if (!isset($applied[$promo->id])) {
                        $applied[$promo->id] = $promo;
                    }
                }
            }
        }

        return array_values($applied);
    }

    public function getPromotionDiscount(\App\Models\Promotion $promo): float {
        return $this->pricingService->calculateDiscount($promo, $this->items(), $this->total());
    }

    public function discount(): float {
        $promotions = $this->getAppliedPromotions();
        if (empty($promotions)) return 0.0;

        $totalDiscount = 0.0;
        $items = $this->items();
        $subtotal = $this->total();

        foreach ($promotions as $promo) {
            $totalDiscount += $this->pricingService->calculateDiscount($promo, $items, $subtotal);
        }

        return min($totalDiscount, $subtotal);
    }

    public function grandTotal(): float {
        return max(0, $this->total() - $this->discount());
    }

    public function syncOnLogin(int $userId): void {
        $sessionId = session_id();
        
        $sessionCartId = $this->repository->findCartBySessionId($sessionId);
        $userCartId = $this->repository->findCartByUserId($userId);

        if (!$userCartId) {
            if ($sessionCartId) {
                $this->repository->attachCartToUser($sessionCartId, $userId);
                $this->logger->info("Guest cart {cartId} attached to user {userId} on login", [
                    'cartId' => $sessionCartId,
                    'userId' => $userId
                ]);
            }
        } elseif ($sessionCartId) {
            // Merge session cart promotions into user cart
            $sessionPromos = $this->repository->getPromoCodes($sessionCartId);
            foreach ($sessionPromos as $code) {
                $this->repository->applyPromoCode($userCartId, $code);
            }

            // Merge session items into user cart
            $sessionItems = $this->repository->getItems($sessionCartId);
            foreach ($sessionItems as $item) {
                $this->repository->addItem($userCartId, $item['product_id'], $item['qty'], $item['variant_id']);
            }

            // Delete session cart
            $this->repository->deleteCart($sessionCartId);
            $this->logger->info("Guest cart {sessionCartId} merged into user cart {userCartId} for user {userId}", [
                'sessionCartId' => $sessionCartId,
                'userCartId' => $userCartId,
                'userId' => $userId
            ]);
        }
    }

    private function getCartId(): ?int {
        $this->auth->sessionStart();
        $user = $this->auth->currentUser();
        $sessionId = session_id();

        if ($user) {
            return $this->repository->findCartByUserId($user->id);
        } else {
            return $this->repository->findCartBySessionId($sessionId);
        }
    }

    private function ensureCart(): int {
        $this->auth->sessionStart();
        $user = $this->auth->currentUser();
        $sessionId = session_id();

        $cartId = $this->getCartId();
        if ($cartId) return (int)$cartId;

        return $this->repository->createCart($user ? $user->id : null, $sessionId);
    }

    private function generateKey(int $productId, ?int $variantId = null): string {
        return $variantId ? "{$productId}-{$variantId}" : (string)$productId;
    }
}

<?php
namespace App\Services;

class CartService implements CartServiceInterface {
    public function __construct(
        private \PDO $db,
        private ProductServiceInterface $productService,
        private AuthServiceInterface $auth,
        private VatServiceInterface $vatService,
        private PromotionServiceInterface $promotionService,
        private OrderServiceInterface $orderService,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function get(): array {
        $cartId = $this->getCartId();
        if (!$cartId) return [];

        $stmt = $this->db->prepare("SELECT product_id, variant_id, qty FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cartId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $cart = [];
        foreach ($rows as $row) {
            $key = $this->generateKey($row['product_id'], $row['variant_id']);
            $cart[$key] = $row['qty'];
        }
        return $cart;
    }

    public function add(int $productId, int $qty = 1, ?int $variantId = null): void {
        $cartId = $this->ensureCart();
        
        $stmt = $this->db->prepare("SELECT id, qty FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
        $stmt->execute([$cartId, $productId, $variantId, $variantId]);
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($item) {
            $this->db->prepare("UPDATE cart_items SET qty = qty + ? WHERE id = ?")
                ->execute([$qty, $item['id']]);
        } else {
            $this->db->prepare("INSERT INTO cart_items (cart_id, product_id, variant_id, qty) VALUES (?, ?, ?, ?)")
                ->execute([$cartId, $productId, $variantId, $qty]);
        }
        $this->updateLastActivity($cartId);
    }

    public function remove(string $key): void {
        $cartId = $this->getCartId();
        if (!$cartId) return;

        $parts = explode('-', $key);
        $pid = (int)$parts[0];
        $vid = isset($parts[1]) ? (int)$parts[1] : null;

        $this->db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))")
            ->execute([$cartId, $pid, $vid, $vid]);
        
        $this->updateLastActivity($cartId);
    }

    public function update(string $key, int $qty): void {
        if ($qty <= 0) { $this->remove($key); return; }

        $cartId = $this->getCartId();
        if (!$cartId) return;

        $parts = explode('-', $key);
        $pid = (int)$parts[0];
        $vid = isset($parts[1]) ? (int)$parts[1] : null;

        $this->db->prepare("UPDATE cart_items SET qty = ? WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))")
            ->execute([$qty, $cartId, $pid, $vid, $vid]);
        
        $this->updateLastActivity($cartId);
    }

    public function clear(): void {
        $cartId = $this->getCartId();
        if (!$cartId) return;

        $this->db->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);
        $this->updateLastActivity($cartId);
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
        return array_sum(array_map(fn($item) => $item->getSubtotal(), $this->items()));
    }

    public function totalVat(): float {
        return array_sum(array_map(fn($item) => $item->getVatAmount(), $this->items()));
    }

    public function applyPromoCode(string $code): bool {
        $promo = $this->promotionService->findByCode($code);
        $user = $this->auth->currentUser();
        $isFirstOrder = $user ? !$this->orderService->hasOrders($user->id) : true;

        if (!$promo || !$promo->isActive($user, $isFirstOrder)) {
            return false;
        }

        $cartId = $this->ensureCart();
        $stmt = $this->db->prepare("INSERT OR IGNORE INTO cart_promotions (cart_id, promo_code) VALUES (?, ?)");
        if (DB_CONFIG['driver'] === 'mysql') {
            $stmt = $this->db->prepare("INSERT IGNORE INTO cart_promotions (cart_id, promo_code) VALUES (?, ?)");
        }
        $stmt->execute([$cartId, $code]);
        
        return true;
    }

    public function removePromoCode(?string $code = null): void {
        $cartId = $this->getCartId();
        if (!$cartId) return;

        if ($code) {
            $this->db->prepare("DELETE FROM cart_promotions WHERE cart_id = ? AND promo_code = ?")
                ->execute([$cartId, $code]);
        } else {
            $this->db->prepare("DELETE FROM cart_promotions WHERE cart_id = ?")
                ->execute([$cartId]);
        }
    }

    public function getAppliedPromotions(): array {
        $cartId = $this->getCartId();
        if (!$cartId) return [];

        $stmt = $this->db->prepare("SELECT promo_code FROM cart_promotions WHERE cart_id = ?");
        $stmt->execute([$cartId]);
        $codes = $stmt->fetchAll(\PDO::FETCH_COLUMN);

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
        $autoPromos = $this->promotionService->getActivePromotions(true);
        foreach ($autoPromos as $promo) {
            // Priority ordering is already handled by PromotionService::getActivePromotions

            // Rule: Include if it's the highest priority auto promo OR if it's stackable
            
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
        return $this->promotionService->calculateDiscount($promo, $this->items(), $this->total());
    }

    public function discount(): float {
        $promotions = $this->getAppliedPromotions();
        if (empty($promotions)) return 0.0;

        $totalDiscount = 0.0;
        $items = $this->items();
        $subtotal = $this->total();

        foreach ($promotions as $promo) {
            $totalDiscount += $this->promotionService->calculateDiscount($promo, $items, $subtotal);
        }

        return min($totalDiscount, $subtotal);
    }

    public function grandTotal(): float {
        return max(0, $this->total() - $this->discount());
    }

    public function syncOnLogin(int $userId): void {
        $sessionId = session_id();
        
        // Find session cart
        $stmt = $this->db->prepare("SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL");
        $stmt->execute([$sessionId]);
        $sessionCartId = $stmt->fetchColumn();

        // Find user cart
        $stmt = $this->db->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $userCartId = $stmt->fetchColumn();

        if (!$userCartId) {
            if ($sessionCartId) {
                // Attach session cart to user
                $this->db->prepare("UPDATE carts SET user_id = ? WHERE id = ?")->execute([$userId, $sessionCartId]);
            }
        } elseif ($sessionCartId) {
            // Merge session cart promotions into user cart
            $stmt = $this->db->prepare("SELECT promo_code FROM cart_promotions WHERE cart_id = ?");
            $stmt->execute([$sessionCartId]);
            $sessionPromos = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $insertPromo = $this->db->prepare("INSERT OR IGNORE INTO cart_promotions (cart_id, promo_code) VALUES (?, ?)");
            if (DB_CONFIG['driver'] === 'mysql') {
                $insertPromo = $this->db->prepare("INSERT IGNORE INTO cart_promotions (cart_id, promo_code) VALUES (?, ?)");
            }
            foreach ($sessionPromos as $code) {
                $insertPromo->execute([$userCartId, $code]);
            }

            // Merge session items into user cart
            $stmt = $this->db->prepare("SELECT * FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$sessionCartId]);
            $sessionItems = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($sessionItems as $item) {
                // Upsert into user cart
                $check = $this->db->prepare("SELECT id FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))");
                $check->execute([$userCartId, $item['product_id'], $item['variant_id'], $item['variant_id']]);
                $existingId = $check->fetchColumn();

                if ($existingId) {
                    $this->db->prepare("UPDATE cart_items SET qty = qty + ? WHERE id = ?")->execute([$item['qty'], $existingId]);
                } else {
                    $this->db->prepare("INSERT INTO cart_items (cart_id, product_id, variant_id, qty) VALUES (?, ?, ?, ?)")
                        ->execute([$userCartId, $item['product_id'], $item['variant_id'], $item['qty']]);
                }
            }

            // Delete session cart
            $this->db->prepare("DELETE FROM carts WHERE id = ?")->execute([$sessionCartId]);
        }
    }

    private function getCartId(): ?int {
        $this->auth->sessionStart();
        $user = $this->auth->currentUser();
        $sessionId = session_id();

        if ($user) {
            $stmt = $this->db->prepare("SELECT id FROM carts WHERE user_id = ?");
            $stmt->execute([$user->id]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL");
            $stmt->execute([$sessionId]);
        }
        return $stmt->fetchColumn() ?: null;
    }

    private function ensureCart(): int {
        $this->auth->sessionStart();
        $user = $this->auth->currentUser();
        $sessionId = session_id();

        $cartId = $this->getCartId();
        if ($cartId) return (int)$cartId;

        if ($user) {
            $this->db->prepare("INSERT INTO carts (user_id, session_id) VALUES (?, ?)")->execute([$user->id, $sessionId]);
        } else {
            $this->db->prepare("INSERT INTO carts (session_id) VALUES (?)")->execute([$sessionId]);
        }
        return (int)$this->db->lastInsertId();
    }

    private function updateLastActivity(int $cartId): void {
        $this->db->prepare("UPDATE carts SET last_activity = CURRENT_TIMESTAMP WHERE id = ?")->execute([$cartId]);
    }

    private function generateKey(int $productId, ?int $variantId = null): string {
        return $variantId ? "{$productId}-{$variantId}" : (string)$productId;
    }
}

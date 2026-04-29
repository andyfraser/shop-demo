<?php
namespace App\Services;

class CartService implements CartServiceInterface {
    public function __construct(
        private \PDO $db,
        private ProductServiceInterface $productService,
        private AuthServiceInterface $auth,
        private VatServiceInterface $vatService
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
            $subtotal = $unitPrice * $qty;
            $vatAmount = $this->vatService->calculateVatFromGross($subtotal, $product->vat_rate);

            $items[] = [
                'key'        => $key,
                'product'    => $product,
                'variant'    => $variant,
                'qty'        => $qty,
                'unit_price' => $unitPrice,
                'subtotal'   => $subtotal,
                'vat_amount' => $vatAmount,
            ];
        }
        return $items;
    }

    public function total(): float {
        return array_sum(array_column($this->items(), 'subtotal'));
    }

    public function totalVat(): float {
        return array_sum(array_column($this->items(), 'vat_amount'));
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
            // Merge session cart into user cart
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

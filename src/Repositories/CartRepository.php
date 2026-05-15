<?php
namespace App\Repositories;

class CartRepository implements CartRepositoryInterface {
    public function __construct(private \PDO $db) {}

    public function getItems(int $cartId): array {
        $stmt = $this->db->prepare("SELECT product_id, variant_id, qty FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cartId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addItem(int $cartId, int $productId, int $qty, ?int $variantId = null): void {
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
    }

    public function removeItem(int $cartId, int $productId, ?int $variantId = null): void {
        $this->db->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))")
            ->execute([$cartId, $productId, $variantId, $variantId]);
    }

    public function updateItemQty(int $cartId, int $productId, int $qty, ?int $variantId = null): void {
        $this->db->prepare("UPDATE cart_items SET qty = ? WHERE cart_id = ? AND product_id = ? AND (variant_id = ? OR (variant_id IS NULL AND ? IS NULL))")
            ->execute([$qty, $cartId, $productId, $variantId, $variantId]);
    }

    public function clearItems(int $cartId): void {
        $this->db->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cartId]);
    }

    public function applyPromoCode(int $cartId, string $code): bool {
        $sql = "INSERT OR IGNORE INTO cart_promotions (cart_id, promo_code) VALUES (?, ?)";
        if (DB_CONFIG['driver'] === 'mysql') {
            $sql = "INSERT IGNORE INTO cart_promotions (cart_id, promo_code) VALUES (?, ?)";
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$cartId, $code]);
    }

    public function removePromoCode(int $cartId, ?string $code = null): void {
        if ($code) {
            $this->db->prepare("DELETE FROM cart_promotions WHERE cart_id = ? AND promo_code = ?")
                ->execute([$cartId, $code]);
        } else {
            $this->db->prepare("DELETE FROM cart_promotions WHERE cart_id = ?")
                ->execute([$cartId]);
        }
    }

    public function getPromoCodes(int $cartId): array {
        $stmt = $this->db->prepare("SELECT promo_code FROM cart_promotions WHERE cart_id = ?");
        $stmt->execute([$cartId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function findCartByUserId(int $userId): ?int {
        $stmt = $this->db->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: null;
    }

    public function findCartBySessionId(string $sessionId): ?int {
        $stmt = $this->db->prepare("SELECT id FROM carts WHERE session_id = ? AND user_id IS NULL");
        $stmt->execute([$sessionId]);
        return $stmt->fetchColumn() ?: null;
    }

    public function createCart(?int $userId, string $sessionId): int {
        if ($userId) {
            $this->db->prepare("INSERT INTO carts (user_id, session_id) VALUES (?, ?)")->execute([$userId, $sessionId]);
        } else {
            $this->db->prepare("INSERT INTO carts (session_id) VALUES (?)")->execute([$sessionId]);
        }
        return (int)$this->db->lastInsertId();
    }

    public function updateLastActivity(int $cartId): void {
        $this->db->prepare("UPDATE carts SET last_activity = CURRENT_TIMESTAMP WHERE id = ?")->execute([$cartId]);
    }

    public function attachCartToUser(int $cartId, int $userId): void {
        $this->db->prepare("UPDATE carts SET user_id = ? WHERE id = ?")->execute([$userId, $cartId]);
    }

    public function deleteCart(int $cartId): void {
        $this->db->prepare("DELETE FROM carts WHERE id = ?")->execute([$cartId]);
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use Psr\Log\LoggerInterface;

class WishlistService implements WishlistServiceInterface {
    public function __construct(
        private \PDO $db,
        private ProductServiceInterface $productService,
        private LoggerInterface $logger
    ) {}

    public function getUserWishlist(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT product_id FROM wishlists WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        $productIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (empty($productIds)) {
            return [];
        }

        return $this->productService->findByIds($productIds);
    }

    public function addToWishlist(int $userId, int $productId): bool {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)"
            );
            return $stmt->execute([$userId, $productId]);
        } catch (\PDOException $e) {
            // Probably a duplicate entry, which is fine
            $this->logger->info("Wishlist add duplicate or error", ['userId' => $userId, 'productId' => $productId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function removeFromWishlist(int $userId, int $productId): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?"
        );
        return $stmt->execute([$userId, $productId]);
    }

    public function isInWishlist(int $userId, int $productId): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?"
        );
        $stmt->execute([$userId, $productId]);
        return (bool)$stmt->fetchColumn();
    }
}

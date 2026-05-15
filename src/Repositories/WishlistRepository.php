<?php
namespace App\Repositories;

use Psr\Log\LoggerInterface;

class WishlistRepository implements WishlistRepositoryInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function getUserProductIds(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT product_id FROM wishlists WHERE user_id = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function add(int $userId, int $productId): bool {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)"
            );
            return $stmt->execute([$userId, $productId]);
        } catch (\PDOException $e) {
            $this->logger->info("Wishlist add duplicate or error", ['userId' => $userId, 'productId' => $productId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function remove(int $userId, int $productId): bool {
        $stmt = $this->db->prepare(
            "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?"
        );
        return $stmt->execute([$userId, $productId]);
    }

    public function exists(int $userId, int $productId): bool {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM wishlists WHERE user_id = ? AND product_id = ?"
        );
        $stmt->execute([$userId, $productId]);
        return (bool)$stmt->fetchColumn();
    }
}

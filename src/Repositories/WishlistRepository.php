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

    public function getSettings(int $userId): ?array {
        $stmt = $this->db->prepare(
            "SELECT * FROM wishlist_settings WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $settings = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $settings ?: null;
    }

    public function updateSettings(int $userId, bool $isPublic, string $shareHash): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO wishlist_settings (user_id, is_public, share_hash) 
             VALUES (?, ?, ?)
             ON CONFLICT(user_id) DO UPDATE SET is_public = EXCLUDED.is_public, share_hash = EXCLUDED.share_hash"
        );
        
        // Handle MySQL vs SQLite for ON CONFLICT/DUPLICATE KEY
        try {
            return $stmt->execute([$userId, (int)$isPublic, $shareHash]);
        } catch (\PDOException $e) {
            // If SQLite version is too old for ON CONFLICT or if we are on MySQL
            $stmt = $this->db->prepare(
                "INSERT INTO wishlist_settings (user_id, is_public, share_hash) 
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE is_public = VALUES(is_public), share_hash = VALUES(share_hash)"
            );
            return $stmt->execute([$userId, (int)$isPublic, $shareHash]);
        }
    }

    public function getUserIdByShareHash(string $shareHash): ?int {
        $stmt = $this->db->prepare(
            "SELECT user_id FROM wishlist_settings WHERE share_hash = ? AND is_public = 1"
        );
        $stmt->execute([$shareHash]);
        $userId = $stmt->fetchColumn();
        return $userId ? (int)$userId : null;
    }
}

<?php
namespace App\Services;

use Psr\Log\LoggerInterface;

class ReviewService implements ReviewServiceInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function getByProductId(int $productId, bool $onlyApproved = true): array {
        $sql = "SELECT r.*, u.name as user_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ?";
        if ($onlyApproved) {
            $sql .= " AND r.status = 'approved'";
        }
        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, \App\Models\Review::class, [$this->logger]);
    }

    public function submit(int $productId, int $userId, int $rating, ?string $comment): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO reviews (product_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')"
        );
        return $stmt->execute([$productId, $userId, $rating, $comment]);
    }

    public function getAllForAdmin(): array {
        return $this->db->query(
            "SELECT r.*, u.name as user_name, p.name as product_name 
             FROM reviews r 
             JOIN users u ON r.user_id = u.id 
             JOIN products p ON r.product_id = p.id 
             ORDER BY r.created_at DESC"
        )->fetchAll(\PDO::FETCH_CLASS, \App\Models\Review::class, [$this->logger]);
    }

    public function updateStatus(int $reviewId, string $status): bool {
        $stmt = $this->db->prepare("UPDATE reviews SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $reviewId]);
    }

    public function getAverageRating(int $productId): float {
        $stmt = $this->db->prepare("SELECT AVG(rating) FROM reviews WHERE product_id = ? AND status = 'approved'");
        $stmt->execute([$productId]);
        return (float)$stmt->fetchColumn();
    }
}

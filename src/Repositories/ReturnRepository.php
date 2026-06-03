<?php
namespace App\Repositories;

use App\Models\ReturnOrder;
use App\Models\ReturnItem;
use Psr\Log\LoggerInterface;
use PDO;

class ReturnRepository implements ReturnRepositoryInterface {
    public function __construct(
        private PDO $db,
        private LoggerInterface $logger
    ) {}

    public function createReturnRequest(int $orderId, ?int $userId, string $reason, array $items): int {
        $stmt = $this->db->prepare("INSERT INTO returns (order_id, user_id, status, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$orderId, $userId, ReturnOrder::STATUS_PENDING, $reason]);
        $returnId = (int)$this->db->lastInsertId();

        $itemStmt = $this->db->prepare("INSERT INTO return_items (return_id, order_item_id, quantity) VALUES (?, ?, ?)");
        foreach ($items as $orderItemId => $quantity) {
            $itemStmt->execute([$returnId, $orderItemId, $quantity]);
        }

        return $returnId;
    }

    public function findById(int $id): ?ReturnOrder {
        $stmt = $this->db->prepare("SELECT r.*, o.customer_name FROM returns r JOIN orders o ON r.order_id = o.id WHERE r.id = ?");
        $stmt->setFetchMode(PDO::FETCH_CLASS, ReturnOrder::class, [$this->logger]);
        $stmt->execute([$id]);
        /** @var ReturnOrder|null $return */
        $return = $stmt->fetch() ?: null;

        if ($return) {
            $stmt = $this->db->prepare("
                SELECT ri.*, p.name as product_name, pv.name as variant_name, oi.unit_price, oi.product_id 
                FROM return_items ri
                JOIN order_items oi ON ri.order_item_id = oi.id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                WHERE ri.return_id = ?
            ");
            $stmt->execute([$id]);
            $return->items = $stmt->fetchAll(PDO::FETCH_CLASS, ReturnItem::class, [$this->logger]);
        }

        return $return;
    }

    public function getForUser(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM returns WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_CLASS, ReturnOrder::class, [$this->logger]);
    }

    public function getForOrder(int $orderId): array {
        $stmt = $this->db->prepare("SELECT * FROM returns WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $returns = $stmt->fetchAll(PDO::FETCH_CLASS, ReturnOrder::class, [$this->logger]);

        foreach ($returns as $return) {
            $stmt = $this->db->prepare("
                SELECT ri.*, p.name as product_name, pv.name as variant_name, oi.unit_price, oi.product_id 
                FROM return_items ri
                JOIN order_items oi ON ri.order_item_id = oi.id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                WHERE ri.return_id = ?
            ");
            $stmt->execute([$return->id]);
            $return->items = $stmt->fetchAll(PDO::FETCH_CLASS, ReturnItem::class, [$this->logger]);
        }

        return $returns;
    }

    public function getAllForAdmin(): array {
        $stmt = $this->db->prepare("SELECT r.*, o.customer_name FROM returns r JOIN orders o ON r.order_id = o.id ORDER BY r.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_CLASS, ReturnOrder::class, [$this->logger]);
    }

    public function updateStatus(int $id, string $status, ?string $reason = null, ?float $refundAmount = null): void {
        $sql = "UPDATE returns SET status = ?";
        $params = [$status];
        if ($reason !== null) {
            $sql .= ", reason = ?";
            $params[] = $reason;
        }
        if ($refundAmount !== null) {
            $sql .= ", refund_amount = ?";
            $params[] = $refundAmount;
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function getPendingReturnCount(int $orderId, int $excludeReturnId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM returns WHERE order_id = ? AND status = ? AND id != ?");
        $stmt->execute([$orderId, ReturnOrder::STATUS_PENDING, $excludeReturnId]);
        return (int)$stmt->fetchColumn();
    }

    public function replenishStock(array $items): void {
        $stockStmt = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = (SELECT product_id FROM order_items WHERE id = ?)");
        $variantStockStmt = $this->db->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = (SELECT variant_id FROM order_items WHERE id = ?)");

        foreach ($items as $item) {
            $stmt = $this->db->prepare("SELECT product_id, variant_id FROM order_items WHERE id = ?");
            $stmt->execute([$item->order_item_id]);
            $oi = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($oi && !empty($oi['variant_id'])) {
                $variantStockStmt->execute([$item->quantity, $item->order_item_id]);
            } elseif ($oi) {
                $stockStmt->execute([$item->quantity, $item->order_item_id]);
            }
        }
    }

    public function beginTransaction(): void { $this->db->beginTransaction(); }
    public function commit(): void { $this->db->commit(); }
    public function rollBack(): void { $this->db->rollBack(); }
    public function inTransaction(): bool { return $this->db->inTransaction(); }
}

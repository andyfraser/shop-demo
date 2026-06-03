<?php
namespace App\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use Psr\Log\LoggerInterface;

class OrderRepository implements OrderRepositoryInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function create(array $orderData, array $items): int {
        $stmt = $this->db->prepare(
            "INSERT INTO orders (user_id, customer_name, customer_email, total, total_vat_amount, shipping_address, notes, status, delivery_method, delivery_cost, promotion_id, discount_amount, gift_card_amount, applied_promo_name, applied_promo_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $orderData['user_id'] ?? null,
            $orderData['customer_name'],
            $orderData['customer_email'],
            $orderData['total'],
            $orderData['total_vat_amount'],
            $orderData['shipping_address'],
            $orderData['notes'],
            Order::STATUS_PENDING,
            $orderData['delivery_method'],
            $orderData['delivery_cost'],
            $orderData['promotion_id'] ?? null,
            $orderData['discount_amount'] ?? 0.0,
            $orderData['gift_card_amount'] ?? 0.0,
            $orderData['applied_promo_name'] ?? null,
            $orderData['applied_promo_code'] ?? null
        ]);

        $orderId = (int)$this->db->lastInsertId();

        // Update promotion usage if applicable
        if (!empty($orderData['applied_promotions'])) {
            $promoUpdateStmt = $this->db->prepare("UPDATE promotions SET used_count = used_count + 1 WHERE id = ?");
            $orderPromoStmt = $this->db->prepare("INSERT INTO order_promotions (order_id, promotion_id, promotion_name, discount_amount, promo_code) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($orderData['applied_promotions'] as $promo) {
                $promoUpdateStmt->execute([$promo['promotion_id']]);
                $orderPromoStmt->execute([
                    $orderId,
                    $promo['promotion_id'],
                    $promo['name'] ?? null,
                    $promo['discount_amount'],
                    $promo['promo_code'] ?? null
                ]);
            }
        }

        $itemStmt = $this->db->prepare(
            "INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price, vat_rate, vat_amount, metadata)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stockStmt = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $variantStockStmt = $this->db->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");

        $isMysql = ($this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql');
        $forUpdate = $isMysql ? " FOR UPDATE" : "";

        // First pass: validate all stocks before writing/decrementing anything
        foreach ($items as $item) {
            if ($item instanceof \App\Models\CartItem) {
                $product = $item->product;
                $variant = $item->variant;
                $qty = $item->qty;
            } else {
                $product = $item['product'];
                $variant = $item['variant'] ?? null;
                $qty = $item['qty'];
            }

            if (!$product->is_virtual) {
                if ($variant) {
                    $checkStmt = $this->db->prepare("SELECT stock, name FROM product_variants WHERE id = ?" . $forUpdate);
                    $checkStmt->execute([$variant->id]);
                    $vRow = $checkStmt->fetch(\PDO::FETCH_ASSOC);
                    if (!$vRow) {
                        throw new \App\Exceptions\OutOfStockException("Variant not found.");
                    }
                    if ($vRow['stock'] < $qty) {
                        throw new \App\Exceptions\OutOfStockException("Insufficient stock for " . $product->name . " (" . $vRow['name'] . "). Only " . $vRow['stock'] . " left.");
                    }
                } elseif ($product->is_bundle) {
                    $bundleItems = $this->db->prepare(
                        "SELECT bi.product_id, bi.qty, p.name, p.is_virtual 
                         FROM product_bundle_items bi 
                         JOIN products p ON p.id = bi.product_id 
                         WHERE bi.bundle_id = ?"
                    );
                    $bundleItems->execute([$product->id]);
                    foreach ($bundleItems->fetchAll(\PDO::FETCH_ASSOC) as $bi) {
                        if (!$bi['is_virtual']) {
                            $compCheckStmt = $this->db->prepare("SELECT stock, name FROM products WHERE id = ?" . $forUpdate);
                            $compCheckStmt->execute([$bi['product_id']]);
                            $compRow = $compCheckStmt->fetch(\PDO::FETCH_ASSOC);
                            $needed = $qty * $bi['qty'];
                            if (!$compRow) {
                                throw new \App\Exceptions\OutOfStockException("Bundle component not found.");
                            }
                            if ($compRow['stock'] < $needed) {
                                throw new \App\Exceptions\OutOfStockException("Insufficient stock for component " . $compRow['name'] . " in bundle " . $product->name . ". Needs " . $needed . ", but only " . $compRow['stock'] . " left.");
                            }
                        }
                    }
                } else {
                    $checkStmt = $this->db->prepare("SELECT stock, name FROM products WHERE id = ?" . $forUpdate);
                    $checkStmt->execute([$product->id]);
                    $pRow = $checkStmt->fetch(\PDO::FETCH_ASSOC);
                    if (!$pRow) {
                        throw new \App\Exceptions\OutOfStockException("Product not found.");
                    }
                    if ($pRow['stock'] < $qty) {
                        throw new \App\Exceptions\OutOfStockException("Insufficient stock for " . $product->name . ". Only " . $pRow['stock'] . " left.");
                    }
                }
            }
        }

        foreach ($items as $item) {
            if ($item instanceof \App\Models\CartItem) {
                $product = $item->product;
                $variant = $item->variant;
                $qty = $item->qty;
                $unitPrice = $item->unit_price;
                $vatAmount = $item->getVatAmount();
                $metadata = $item->metadata;
            } else {
                $product = $item['product'];
                $variant = $item['variant'] ?? null;
                $qty = $item['qty'];
                $unitPrice = $item['unit_price'];
                $vatAmount = $item['vat_amount'];
                $metadata = $item['metadata'] ?? null;
            }

            $itemStmt->execute([
                $orderId,
                $product->id,
                $variant ? $variant->id : null,
                $qty,
                $unitPrice,
                $product->vat_rate,
                $vatAmount,
                $metadata
            ]);

            if ($variant) {
                if (!$product->is_virtual) {
                    $variantStockStmt->execute([$qty, $variant->id]);
                }
            } else {
                if ($product->is_bundle) {
                    $bundleItems = $this->db->prepare(
                        "SELECT bi.product_id, bi.qty, p.is_virtual 
                         FROM product_bundle_items bi 
                         JOIN products p ON p.id = bi.product_id 
                         WHERE bi.bundle_id = ?"
                    );
                    $bundleItems->execute([$product->id]);
                    foreach ($bundleItems->fetchAll(\PDO::FETCH_ASSOC) as $bi) {
                        if (!$bi['is_virtual']) {
                            $stockStmt->execute([$qty * $bi['qty'], $bi['product_id']]);
                        }
                    }
                } else {
                    if (!$product->is_virtual) {
                        $stockStmt->execute([$qty, $product->id]);
                    }
                }
            }
        }

        return $orderId;
    }

    public function findById(int $id): ?Order {
        $stmt = $this->db->prepare(
            "SELECT o.*, 
                    COALESCE(u.name, o.customer_name) as user_name,
                    COALESCE(u.email, o.customer_email) as user_email,
                    COALESCE(p.name, o.applied_promo_name) as promotion_name
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             LEFT JOIN promotions p ON o.promotion_id = p.id
             WHERE o.id = ?"
        );
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Order::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getAppliedPromotions(int $orderId): array {
        $stmt = $this->db->prepare(
            "SELECT op.*, COALESCE(op.promotion_name, p.name) as promotion_name
             FROM order_promotions op
             LEFT JOIN promotions p ON op.promotion_id = p.id
             WHERE op.order_id = ?"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getItems(int $orderId): array {
        $stmt = $this->db->prepare(
            "SELECT oi.*, p.name as product_name, p.slug, pv.name as variant_name, p.is_bundle
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             LEFT JOIN product_variants pv ON oi.variant_id = pv.id
             WHERE oi.order_id = ?"
        );
        $items = $stmt->execute([$orderId]) ? $stmt->fetchAll(\PDO::FETCH_CLASS, OrderItem::class, [$this->logger]) : [];

        foreach ($items as $item) {
            if ($item->is_bundle) {
                $compStmt = $this->db->prepare(
                    "SELECT pbi.qty, p.name 
                     FROM product_bundle_items pbi
                     JOIN products p ON pbi.product_id = p.id
                     WHERE pbi.bundle_id = ?"
                );
                $compStmt->execute([$item->product_id]);
                $item->bundle_components = $compStmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        }

        return $items;
    }

    public function getAllForAdmin(string $status = ''): array {
        $criteria = new \App\Core\QueryCriteria();
        if ($status) {
            $criteria->addFilter('status', $status);
        }
        return $this->find($criteria);
    }

    public function find(\App\Core\QueryCriteria $criteria): array {
        $sql = "SELECT o.*, 
                       COALESCE(u.name, o.customer_name) as user_name,
                       COALESCE(u.email, o.customer_email) as user_email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id";
        
        $params = [];
        $where = [];

        if ($criteria->hasFilter('status')) {
            $where[] = "o.status = ?";
            $params[] = $criteria->getFilter('status');
        }
        if ($criteria->hasFilter('ids') && is_array($criteria->getFilter('ids'))) {
            $ids = $criteria->getFilter('ids');
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $where[] = "o.id IN ($placeholders)";
                $params = array_merge($params, $ids);
            }
        }
        if ($criteria->hasFilter('user_id')) {
            $where[] = "o.user_id = ?";
            $params[] = $criteria->getFilter('user_id');
        }
        if ($criteria->hasFilter('date_from')) {
            $where[] = "o.created_at >= ?";
            $params[] = $criteria->getFilter('date_from');
        }
        if ($criteria->hasFilter('date_to')) {
            $where[] = "o.created_at <= ?";
            $params[] = $criteria->getFilter('date_to');
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sort = $criteria->getSort();
        $order_by = match($sort) {
            'total_asc'  => 'o.total ASC',
            'total_desc' => 'o.total DESC',
            'oldest'     => 'o.created_at ASC',
            default      => 'o.created_at DESC',
        };
        $sql .= " ORDER BY $order_by";

        if ($criteria->getLimit() !== null) {
            $sql .= " LIMIT " . (int)$criteria->getLimit() . " OFFSET " . (int)$criteria->getOffset();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Order::class, [$this->logger]);
    }

    public function count(\App\Core\QueryCriteria $criteria): int {
        $sql = "SELECT COUNT(*) FROM orders o";
        $params = [];
        $where = [];

        if ($criteria->hasFilter('status')) {
            $where[] = "o.status = ?";
            $params[] = $criteria->getFilter('status');
        }
        if ($criteria->hasFilter('ids') && is_array($criteria->getFilter('ids'))) {
            $ids = $criteria->getFilter('ids');
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $where[] = "o.id IN ($placeholders)";
                $params = array_merge($params, $ids);
            }
        }
        if ($criteria->hasFilter('user_id')) {
            $where[] = "o.user_id = ?";
            $params[] = $criteria->getFilter('user_id');
        }
        if ($criteria->hasFilter('date_from')) {
            $where[] = "o.created_at >= ?";
            $params[] = $criteria->getFilter('date_from');
        }
        if ($criteria->hasFilter('date_to')) {
            $where[] = "o.created_at <= ?";
            $params[] = $criteria->getFilter('date_to');
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getForUser(int $userId): array {
        $stmt = $this->db->prepare(
            "SELECT o.*, COUNT(oi.id) as item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             WHERE o.user_id = ?
             GROUP BY o.id
             ORDER BY o.created_at DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Order::class, [$this->logger]);
    }

    public function hasOrders(int $userId): bool {
        $stmt = $this->db->prepare("SELECT EXISTS(SELECT 1 FROM orders WHERE user_id = ? AND status != ?)");
        $stmt->execute([$userId, Order::STATUS_CANCELLED]);
        return (bool)$stmt->fetchColumn();
    }

    public function countAll(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    }

    public function getTotalRevenue(): float {
        return (float)$this->db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != '" . Order::STATUS_CANCELLED . "'")->fetchColumn();
    }

    public function getRecentOrders(int $limit = 10): array {
        $stmt = $this->db->prepare(
            "SELECT o.*, 
                    COALESCE(u.name, o.customer_name) as user_name,
                    COALESCE(u.email, o.customer_email) as user_email
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Order::class, [$this->logger]);
    }

    public function updateStatus(int $id, string $status): void {
        $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }

    public function addHistoryEntry(int $orderId, string $status, string $notes = '', ?int $userId = null): void {
        $historyStmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status, notes, created_by_user_id) VALUES (?, ?, ?, ?)");
        $historyStmt->execute([$orderId, $status, $notes, $userId]);
    }

    public function getStatusHistory(int $orderId): array {
        $stmt = $this->db->prepare(
            "SELECT h.*, u.name as user_name
             FROM order_status_history h
             LEFT JOIN users u ON h.created_by_user_id = u.id
             WHERE h.order_id = ?
             ORDER BY h.id DESC"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, OrderStatusHistory::class, [$this->logger]);
    }

    public function updatePaymentInfo(int $id, string $method, string $status, ?string $transactionId = null): void {
        $stmt = $this->db->prepare("UPDATE orders SET payment_method = ?, payment_status = ?, payment_transaction_id = ? WHERE id = ?");
        $stmt->execute([$method, $status, $transactionId, $id]);
    }

    public function updateRefundInfo(int $id, string $status, float $amount, bool $deliveryRefunded = false): void {
        $stmt = $this->db->prepare("UPDATE orders SET refund_status = ?, refunded_amount = ?, delivery_refunded = CASE WHEN ? = 1 THEN 1 ELSE delivery_refunded END WHERE id = ?");
        $stmt->execute([$status, $amount, $deliveryRefunded ? 1 : 0, $id]);
    }

    public function replenishStock(array $items): void {
        $stockStmt = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $variantStockStmt = $this->db->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?");
        $isVirtualOrBundleStmt = $this->db->prepare("SELECT is_bundle, is_virtual FROM products WHERE id = ?");

        foreach ($items as $item) {
            $isVirtualOrBundleStmt->execute([$item->product_id]);
            $prodInfo = $isVirtualOrBundleStmt->fetch(\PDO::FETCH_ASSOC);
            if (!$prodInfo) continue;
            
            if ((int)$prodInfo['is_virtual'] === 1) {
                continue; // Do not replenish virtual product stock
            }

            if ($item->variant_id) {
                $variantStockStmt->execute([$item->quantity, $item->variant_id]);
            } else {
                if ((int)$prodInfo['is_bundle'] === 1) {
                    $bundleItems = $this->db->prepare(
                        "SELECT bi.product_id, bi.qty, p.is_virtual 
                         FROM product_bundle_items bi 
                         JOIN products p ON p.id = bi.product_id 
                         WHERE bi.bundle_id = ?"
                    );
                    $bundleItems->execute([$item->product_id]);
                    foreach ($bundleItems->fetchAll(\PDO::FETCH_ASSOC) as $bi) {
                        if ((int)$bi['is_virtual'] !== 1) {
                            $stockStmt->execute([$item->quantity * $bi['qty'], $bi['product_id']]);
                        }
                    }
                } else {
                    $stockStmt->execute([$item->quantity, $item->product_id]);
                }
            }
        }
    }

    public function inTransaction(): bool {
        return $this->db->inTransaction();
    }

    public function beginTransaction(): void {
        $this->db->beginTransaction();
    }

    public function commit(): void {
        $this->db->commit();
    }

    public function rollBack(): void {
        $this->db->rollBack();
    }
}

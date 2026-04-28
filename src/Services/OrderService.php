<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Psr\Log\LoggerInterface;
use App\Services\VatServiceInterface;
use App\Services\Payment\PaymentServiceInterface;
use App\Services\EmailServiceInterface;

class OrderService implements OrderServiceInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger,
        private VatServiceInterface $vatService,
        private PaymentServiceInterface $paymentService,
        private EmailServiceInterface $emailService
    ) {}

    /**
     * Create a new order with items.
     */
    public function create(array $orderData, array $items): int {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO orders (user_id, customer_name, customer_email, total, total_vat_amount, shipping_address, notes, status, delivery_method, delivery_cost)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
                $orderData['delivery_cost']
            ]);

            $orderId = (int)$this->db->lastInsertId();

            // Record initial status in history
            $historyStmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status, notes) VALUES (?, ?, ?)");
            $historyStmt->execute([$orderId, Order::STATUS_PENDING, 'Order created']);

            $itemStmt = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price, vat_rate, vat_amount)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stockStmt = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $variantStockStmt = $this->db->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ?");

            foreach ($items as $item) {
                $product = $item['product'];
                $variant = $item['variant'] ?? null;
                $qty = $item['qty'];
                $unitPrice = $item['unit_price'];
                $vatAmount = $item['vat_amount'];

                $itemStmt->execute([
                    $orderId,
                    $product->id,
                    $variant ? $variant->id : null,
                    $qty,
                    $unitPrice,
                    $product->vat_rate,
                    $vatAmount
                ]);

                if ($variant) {
                    $variantStockStmt->execute([$qty, $variant->id]);
                } else {
                    $stockStmt->execute([$qty, $product->id]);
                }
            }

            $this->db->commit();
            return $orderId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Find order by ID with its items.
     */
    public function findById(int $id): ?Order {
        $stmt = $this->db->prepare(
            "SELECT o.*, 
                    COALESCE(u.name, o.customer_name) as user_name,
                    COALESCE(u.email, o.customer_email) as user_email
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             WHERE o.id = ?"
        );
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Order::class, [$this->logger]);
        $stmt->execute([$id]);
        $order = $stmt->fetch() ?: null;

        if ($order) {
            $order->items = $this->getItems($id);
        }

        return $order;
    }

    /**
     * Get items for an order.
     */
    public function getItems(int $orderId): array {
        $stmt = $this->db->prepare(
            "SELECT oi.*, p.name as product_name, p.slug, pv.name as variant_name
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             LEFT JOIN product_variants pv ON oi.variant_id = pv.id
             WHERE oi.order_id = ?"
        );
        return $stmt->execute([$orderId]) ? $stmt->fetchAll(\PDO::FETCH_CLASS, OrderItem::class, [$this->logger]) : [];
    }

    /**
     * Get all orders for admin list.
     */
    public function getAllForAdmin(string $status = ''): array {
        $allowed = [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
            Order::STATUS_CANCELLED
        ];
        
        $sql = "SELECT o.*, 
                       COALESCE(u.name, o.customer_name) as user_name,
                       COALESCE(u.email, o.customer_email) as user_email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id";
        
        $params = [];
        if ($status && in_array($status, $allowed)) {
            $sql .= " WHERE o.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Order::class, [$this->logger]);
    }

    /**
     * Get orders for a specific user.
     */
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

    public function countAll(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    }

    public function getTotalRevenue(): float {
        return (float)$this->db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != '" . Order::STATUS_CANCELLED . "'")->fetchColumn();
    }

    /**
     * Get recent orders.
     */
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

    /**
     * Update order status.
     */
    public function updateStatus(int $id, string $status, ?int $userId = null, string $notes = ''): void {
        $startedTransaction = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTransaction = true;
            }

            $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            $this->addHistoryEntry($id, $status, $notes, $userId);

            if ($startedTransaction) {
                $this->db->commit();
            }
        } catch (\Exception $e) {
            if ($startedTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function addHistoryEntry(int $orderId, string $status, string $notes = '', ?int $userId = null): void {
        $historyStmt = $this->db->prepare("INSERT INTO order_status_history (order_id, status, notes, created_by_user_id) VALUES (?, ?, ?, ?)");
        $historyStmt->execute([$orderId, $status, $notes, $userId]);
    }

    /**
     * Get order status history.
     */
    public function getStatusHistory(int $orderId): array {
        $stmt = $this->db->prepare(
            "SELECT h.*, u.name as user_name
             FROM order_status_history h
             LEFT JOIN users u ON h.created_by_user_id = u.id
             WHERE h.order_id = ?
             ORDER BY h.id DESC"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update payment information.
     */
    public function updatePaymentInfo(int $id, string $method, string $status, ?string $transactionId = null): void {
        $stmt = $this->db->prepare("UPDATE orders SET payment_method = ?, payment_status = ?, payment_transaction_id = ? WHERE id = ?");
        $stmt->execute([$method, $status, $transactionId, $id]);
    }

    /**
     * Cancel an order.
     */
    public function cancelOrder(int $id, string $reason = '', ?int $userId = null): bool {
        $order = $this->findById($id);
        if (!$order || !$order->canBeCancelled()) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // 1. Process Refund if confirmed/paid
            if ($order->status === Order::STATUS_CONFIRMED && $order->payment_method) {
                $result = $this->paymentService->refund($order->payment_method, $order);
                if ($result->success) {
                    $stmt = $this->db->prepare("UPDATE orders SET refund_status = ?, refunded_amount = ? WHERE id = ?");
                    $stmt->execute([$result->status, $order->total, $id]);
                } else {
                    $this->logger->warning("Refund failed for order {id}: {message}", ['id' => $id, 'message' => $result->message]);
                }
            }

            // 2. Update Order Status
            $this->updateStatus($id, Order::STATUS_CANCELLED, $userId, $reason ?: 'Order cancelled');

            // 3. Replenish Stock
            $stockStmt = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $variantStockStmt = $this->db->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = ?");

            foreach ($order->items as $item) {
                if ($item->variant_id) {
                    $variantStockStmt->execute([$item->quantity, $item->variant_id]);
                } else {
                    $stockStmt->execute([$item->quantity, $item->product_id]);
                }
            }

            $this->db->commit();

            $this->logger->info("Order {id} successfully cancelled and items returned to stock.", ['id' => $id]);

            // 4. Send Email
            $this->emailService->sendStatusUpdateEmail($order->customer_email, $id, Order::STATUS_CANCELLED);

            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->logger->error("Failed to cancel order {id}: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Psr\Log\LoggerInterface;

class OrderService implements OrderServiceInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
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

            $itemStmt = $this->db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, unit_price, vat_rate, vat_amount)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stockStmt = $this->db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($items as $item) {
                // $item can be from CartService::items() which is an array with 'product' and 'qty'
                $product = $item['product'];
                $qty = $item['qty'];

                $itemStmt->execute([
                    $orderId,
                    $product->id,
                    $qty,
                    $product->price,
                    $product->vat_rate,
                    $product->getVatAmount($qty)
                ]);

                $stockStmt->execute([$qty, $product->id]);
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
            "SELECT oi.*, p.name as product_name, p.slug
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
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
        $where = ($status && in_array($status, $allowed))
            ? "WHERE o.status = " . $this->db->quote($status)
            : '';

        return $this->db->query(
            "SELECT o.*, 
                    COALESCE(u.name, o.customer_name) as user_name,
                    COALESCE(u.email, o.customer_email) as user_email
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             $where
             ORDER BY o.created_at DESC"
        )->fetchAll(\PDO::FETCH_CLASS, Order::class, [$this->logger]);
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
    public function updateStatus(int $id, string $status): void {
        $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
}

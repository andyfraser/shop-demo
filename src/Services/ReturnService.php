<?php

namespace App\Services;

use App\Models\ReturnOrder;
use App\Models\ReturnItem;
use App\Models\Order;
use Psr\Log\LoggerInterface;
use App\Services\OrderServiceInterface;
use App\Services\Payment\PaymentServiceInterface;

class ReturnService implements ReturnServiceInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger,
        private OrderServiceInterface $orderService,
        private PaymentServiceInterface $paymentService,
        private EmailServiceInterface $emailService
    ) {}

    public function createReturnRequest(int $orderId, array $items, string $reason): int {
        $order = $this->orderService->findById($orderId);
        if (!$order || !$order->canBeReturned()) {
            throw new \Exception("Order cannot be returned.");
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO returns (order_id, user_id, status, reason) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $order->user_id, ReturnOrder::STATUS_PENDING, $reason]);
            $returnId = (int)$this->db->lastInsertId();

            $itemStmt = $this->db->prepare("INSERT INTO return_items (return_id, order_item_id, quantity) VALUES (?, ?, ?)");
            foreach ($items as $orderItemId => $quantity) {
                $itemStmt->execute([$returnId, $orderItemId, $quantity]);
            }

            // Update order status to 'returning'
            $this->orderService->updateStatus($orderId, Order::STATUS_RETURNING, $order->user_id, 'Return requested: ' . $reason);

            $this->db->commit();
            
            $return = $this->findById($returnId);
            $this->emailService->sendReturnRequestedEmail($return, $order->customer_email);

            return $returnId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function findById(int $id): ?ReturnOrder {
        $stmt = $this->db->prepare("SELECT r.*, o.customer_name FROM returns r JOIN orders o ON r.order_id = o.id WHERE r.id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, ReturnOrder::class, [$this->logger]);
        $stmt->execute([$id]);
        /** @var ReturnOrder|null $return */
        $return = $stmt->fetch() ?: null;

        if ($return) {
            $stmt = $this->db->prepare("
                SELECT ri.*, p.name as product_name, pv.name as variant_name, oi.unit_price 
                FROM return_items ri
                JOIN order_items oi ON ri.order_item_id = oi.id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                WHERE ri.return_id = ?
            ");
            $stmt->execute([$id]);
            $return->items = $stmt->fetchAll(\PDO::FETCH_CLASS, ReturnItem::class, [$this->logger]);
        }

        return $return;
    }

    public function getForUser(int $userId): array {
        $stmt = $this->db->prepare("SELECT * FROM returns WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_CLASS, ReturnOrder::class, [$this->logger]);
    }

    public function getForOrder(int $orderId): array {
        $stmt = $this->db->prepare("SELECT * FROM returns WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $returns = $stmt->fetchAll(\PDO::FETCH_CLASS, ReturnOrder::class, [$this->logger]);

        foreach ($returns as $return) {
            $stmt = $this->db->prepare("
                SELECT ri.*, p.name as product_name, pv.name as variant_name, oi.unit_price 
                FROM return_items ri
                JOIN order_items oi ON ri.order_item_id = oi.id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                WHERE ri.return_id = ?
            ");
            $stmt->execute([$return->id]);
            $return->items = $stmt->fetchAll(\PDO::FETCH_CLASS, ReturnItem::class, [$this->logger]);
        }

        return $returns;
    }

    public function getAllForAdmin(): array {
        $stmt = $this->db->prepare("SELECT r.*, o.customer_name FROM returns r JOIN orders o ON r.order_id = o.id ORDER BY r.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_CLASS, ReturnOrder::class, [$this->logger]);
    }

    public function approveReturn(int $id, bool $refundDelivery = false, ?int $userId = null): bool {
        $return = $this->findById($id);
        if (!$return || $return->status !== ReturnOrder::STATUS_PENDING) {
            return false;
        }

        $order = $this->orderService->findById($return->order_id);
        if (!$order) return false;

        try {
            $this->db->beginTransaction();

            // Calculate refund amount based on returned items
            $refundAmount = 0;
            foreach ($return->items as $item) {
                $refundAmount += $item->unit_price * $item->quantity;
            }

            $actuallyRefundDelivery = false;
            if ($refundDelivery && $order->delivery_cost > 0 && !$order->delivery_refunded) {
                $refundAmount += $order->delivery_cost;
                $actuallyRefundDelivery = true;
            }

            // 1. Process Refund
            if ($order->payment_method) {
                $result = $this->paymentService->refund($order->payment_method, $order, $refundAmount);
                if ($result->success) {
                    $newRefundedAmount = $order->refunded_amount + $refundAmount;
                    $refundStatus = ($newRefundedAmount >= $order->total) ? 'fully_refunded' : 'partially_refunded';
                    
                    if ($newRefundedAmount >= $order->total) {
                        $orderStatus = Order::STATUS_FULLY_REFUNDED;
                    } else {
                        $orderStatus = Order::STATUS_PARTIAL_REFUND;
                    }

                    $stmt = $this->db->prepare("UPDATE orders SET refund_status = ?, refunded_amount = ?, status = ?, delivery_refunded = CASE WHEN ? = 1 THEN 1 ELSE delivery_refunded END WHERE id = ?");
                    $stmt->execute([$refundStatus, $newRefundedAmount, $orderStatus, $actuallyRefundDelivery ? 1 : 0, $order->id]);
                    
                    $this->orderService->addHistoryEntry($order->id, $orderStatus, sprintf('Return #%d approved. Refunded %s', $id, money($refundAmount)), $userId);
                } else {
                    $this->logger->warning("Return refund failed for return {id}: {message}", ['id' => $id, 'message' => $result->message]);
                }
            } else {
                // If no payment method (e.g. manual), just update order status
                $newRefundedAmount = $order->refunded_amount + $refundAmount;

                if ($newRefundedAmount >= $order->total) {
                    $orderStatus = Order::STATUS_FULLY_REFUNDED;
                } else {
                    $orderStatus = Order::STATUS_PARTIAL_REFUND;
                }

                $stmt = $this->db->prepare("UPDATE orders SET refunded_amount = ?, status = ?, delivery_refunded = CASE WHEN ? = 1 THEN 1 ELSE delivery_refunded END WHERE id = ?");
                $stmt->execute([$newRefundedAmount, $orderStatus, $actuallyRefundDelivery ? 1 : 0, $order->id]);

                $this->orderService->addHistoryEntry($order->id, $orderStatus, sprintf('Return #%d approved. Manual refund of %s', $id, money($refundAmount)), $userId);
            }

            // 2. Update Return status
            $stmt = $this->db->prepare("UPDATE returns SET status = ?, refund_amount = ? WHERE id = ?");
            $stmt->execute([ReturnOrder::STATUS_APPROVED, $refundAmount, $id]);

            // 3. Replenish Stock
            $stockStmt = $this->db->prepare("UPDATE products SET stock = stock + ? WHERE id = (SELECT product_id FROM order_items WHERE id = ?)");
            $variantStockStmt = $this->db->prepare("UPDATE product_variants SET stock = stock + ? WHERE id = (SELECT variant_id FROM order_items WHERE id = ?)");

            foreach ($return->items as $item) {
                $stmt = $this->db->prepare("SELECT product_id, variant_id FROM order_items WHERE id = ?");
                $stmt->execute([$item->order_item_id]);
                $oi = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($oi && !empty($oi['variant_id'])) {
                    $variantStockStmt->execute([$item->quantity, $item->order_item_id]);
                } elseif ($oi) {
                    $stockStmt->execute([$item->quantity, $item->order_item_id]);
                }
            }

            $this->db->commit();

            // Refresh the return object for the email
            $return = $this->findById($id);

            // Send Email
            $this->emailService->sendReturnUpdateEmail($return, $order->customer_email);

            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logger->error("Failed to approve return {id}: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    public function rejectReturn(int $id, string $reason, ?int $userId = null): bool {
        $return = $this->findById($id);
        if (!$return) return false;

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE returns SET status = ?, reason = ? WHERE id = ?");
            $stmt->execute([ReturnOrder::STATUS_REJECTED, $reason, $id]);

            // Update order status
            $order = $this->orderService->findById($return->order_id);
            if ($order->refunded_amount >= $order->total && $order->total > 0) {
                $orderStatus = Order::STATUS_FULLY_REFUNDED;
            } elseif ($order->refunded_amount > 0) {
                $orderStatus = Order::STATUS_PARTIAL_REFUND;
            } else {
                // Check if there are other pending returns
                $stmtPending = $this->db->prepare("SELECT COUNT(*) FROM returns WHERE order_id = ? AND status = ? AND id != ?");
                $stmtPending->execute([$return->order_id, ReturnOrder::STATUS_PENDING, $id]);
                if ($stmtPending->fetchColumn() > 0) {
                    $orderStatus = Order::STATUS_RETURNING;
                } else {
                    $orderStatus = Order::STATUS_NOT_REFUNDED;
                }
            }

            $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$orderStatus, $return->order_id]);

            $this->orderService->addHistoryEntry($return->order_id, $orderStatus, 'Return #' . $id . ' rejected: ' . $reason, $userId);

            $this->db->commit();

            $return = $this->findById($id);
            $order = $this->orderService->findById($return->order_id);
            if ($order) {
                $this->emailService->sendReturnUpdateEmail($return, $order->customer_email);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logger->error("Failed to reject return {id}: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
}

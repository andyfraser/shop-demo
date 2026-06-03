<?php

namespace App\Services;

use App\Models\Order;
use App\Repositories\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use App\Services\VatServiceInterface;
use App\Services\Payment\PaymentServiceInterface;
use App\Services\EmailServiceInterface;
use App\Core\Events\EventDispatcherInterface;
use App\Events\OrderPlaced;
use App\Services\ProductServiceInterface;

class OrderService implements OrderServiceInterface {
    public function __construct(
        private OrderRepositoryInterface $repository,
        private LoggerInterface $logger,
        private VatServiceInterface $vatService,
        private PaymentServiceInterface $paymentService,
        private EmailServiceInterface $emailService,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * Create a new order with items.
     */
    public function create(array $orderData, array $items): int {
        try {
            $this->repository->beginTransaction();

            $orderId = $this->repository->create($orderData, $items);

            // Record initial status in history
            $this->repository->addHistoryEntry($orderId, Order::STATUS_PENDING, 'Order created', $orderData['user_id'] ?? null);

            $this->repository->commit();

            // Dispatch OrderPlaced event
            $order = $this->findById($orderId);
            if ($order) {
                $this->eventDispatcher->dispatch(new OrderPlaced($order, $order->items));
            }

            return $orderId;
        } catch (\Exception $e) {
            $this->repository->rollBack();
            throw $e;
        }
    }

    /**
     * Find order by ID with its items.
     */
    public function findById(int $id): ?Order {
        $order = $this->repository->findById($id);

        if ($order) {
            $order->items = $this->repository->getItems($id);
            $order->applied_promotions = $this->repository->getAppliedPromotions($id);
        }

        return $order;
    }

    /**
     * Get applied promotions for an order.
     */
    public function getAppliedPromotions(int $orderId): array {
        return $this->repository->getAppliedPromotions($orderId);
    }

    /**
     * Get items for an order.
     */
    public function getItems(int $orderId): array {
        return $this->repository->getItems($orderId);
    }

    /**
     * Get all orders for admin list.
     */
    public function getAllForAdmin(string $status = ''): array {
        return $this->repository->getAllForAdmin($status);
    }

    public function find(\App\Core\QueryCriteria $criteria): array {
        return $this->repository->find($criteria);
    }

    public function count(\App\Core\QueryCriteria $criteria): int {
        return $this->repository->count($criteria);
    }

    /**
     * Get orders for a specific user.
     */
    public function getForUser(int $userId): array {
        return $this->repository->getForUser($userId);
    }

    public function hasOrders(int $userId): bool {
        return $this->repository->hasOrders($userId);
    }

    public function countAll(): int {
        return $this->repository->countAll();
    }

    public function getTotalRevenue(): float {
        return $this->repository->getTotalRevenue();
    }

    /**
     * Get recent orders.
     */
    public function getRecentOrders(int $limit = 10): array {
        return $this->repository->getRecentOrders($limit);
    }

    /**
     * Update order status.
     */
    public function updateStatus(int $id, string $status, ?int $userId = null, string $notes = ''): void {
        $order = $this->findById($id);
        $oldStatus = $order ? $order->status : '';
        
        $startedTransaction = false;
        try {
            if (!$this->repository->inTransaction()) {
                $this->repository->beginTransaction();
                $startedTransaction = true;
            }

            $this->repository->updateStatus($id, $status);

            $this->addHistoryEntry($id, $status, $notes, $userId);

            if ($startedTransaction) {
                $this->repository->commit();
            }

            if ($order) {
                $this->eventDispatcher->dispatch(new \App\Events\OrderStatusUpdated($order, $oldStatus, $status));
            }
        } catch (\Exception $e) {
            if ($startedTransaction) {
                $this->repository->rollBack();
            }
            throw $e;
        }
    }

    public function addHistoryEntry(int $orderId, string $status, string $notes = '', ?int $userId = null): void {
        $this->repository->addHistoryEntry($orderId, $status, $notes, $userId);
    }

    /**
     * Get order status history.
     */
    public function getStatusHistory(int $orderId): array {
        return $this->repository->getStatusHistory($orderId);
    }

    /**
     * Update payment information.
     */
    public function updatePaymentInfo(int $id, string $method, string $status, ?string $transactionId = null): void {
        $this->repository->updatePaymentInfo($id, $method, $status, $transactionId);
    }

    public function updateRefundInfo(int $id, string $status, float $amount, bool $deliveryRefunded = false): void {
        $this->repository->updateRefundInfo($id, $status, $amount, $deliveryRefunded);
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
            $this->repository->beginTransaction();

            // 1. Process Refund if confirmed/paid
            if ($order->status === Order::STATUS_CONFIRMED && $order->payment_method) {
                $result = $this->paymentService->refund($order->payment_method, $order);
                if ($result->success) {
                    $this->repository->updateRefundInfo($id, $result->status, $order->total);
                    $this->eventDispatcher->dispatch(new \App\Events\RefundProcessed($order, $order->total));
                } else {
                    $this->logger->warning("Refund failed for order {id}: {message}", ['id' => $id, 'message' => $result->message]);
                }
            }

            // 2. Update Order Status
            $this->updateStatus($id, Order::STATUS_CANCELLED, $userId, $reason ?: 'Order cancelled');

            // 3. Replenish Stock
            $this->repository->replenishStock($order->items);

            $productService = \App\Core\Container::getInstance()->get(ProductServiceInterface::class);
            foreach ($order->items as $item) {
                if ($item->product_id) {
                    $productService->syncPurchasableStatus($item->product_id);
                }
            }

            $this->repository->commit();

            $this->logger->info("Order {id} successfully cancelled and items returned to stock.", ['id' => $id]);

            return true;
        } catch (\Exception $e) {
            $this->repository->rollBack();
            $this->logger->error("Failed to cancel order {id}: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
}

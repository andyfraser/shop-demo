<?php

namespace App\Services;

use App\Models\ReturnOrder;
use App\Models\Order;
use App\Repositories\ReturnRepositoryInterface;
use Psr\Log\LoggerInterface;
use App\Services\OrderServiceInterface;
use App\Services\Payment\PaymentServiceInterface;
use App\Services\EmailServiceInterface;
use App\Services\ProductServiceInterface;

class ReturnService implements ReturnServiceInterface {
    public function __construct(
        private ReturnRepositoryInterface $repository,
        private LoggerInterface $logger,
        private OrderServiceInterface $orderService,
        private PaymentServiceInterface $paymentService,
        private EmailServiceInterface $emailService
    ) {}

    public function createReturnRequest(int $orderId, int $userId, array $items, string $reason): int {
        $order = $this->orderService->findById($orderId);
        if (!$order || $order->user_id !== $userId || !$order->canBeReturned()) {
            throw new \Exception("Order cannot be returned.");
        }

        try {
            $this->repository->beginTransaction();

            $returnId = $this->repository->createReturnRequest($orderId, $order->user_id, $reason, $items);

            // Update order status to 'returning'
            $this->orderService->updateStatus($orderId, Order::STATUS_RETURNING, $order->user_id, 'Return requested: ' . $reason);

            $this->repository->commit();
            
            $return = $this->findById($returnId);
            $this->emailService->sendReturnRequestedEmail($return, $order->customer_email);

            return $returnId;
        } catch (\Exception $e) {
            $this->repository->rollBack();
            throw $e;
        }
    }

    public function findById(int $id): ?ReturnOrder {
        return $this->repository->findById($id);
    }

    public function getForUser(int $userId): array {
        return $this->repository->getForUser($userId);
    }

    public function getForOrder(int $orderId): array {
        return $this->repository->getForOrder($orderId);
    }

    public function getAllForAdmin(): array {
        return $this->repository->getAllForAdmin();
    }

    public function approveReturn(int $id, bool $refundDelivery = false, ?int $userId = null): bool {
        $return = $this->findById($id);
        if (!$return || $return->status !== ReturnOrder::STATUS_PENDING) {
            return false;
        }

        $order = $this->orderService->findById($return->order_id);
        if (!$order) return false;

        try {
            $this->repository->beginTransaction();

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

                    $this->orderService->updateRefundInfo($order->id, $refundStatus, $newRefundedAmount, $actuallyRefundDelivery);
                    $this->orderService->updateStatus($order->id, $orderStatus, $userId, sprintf('Return #%d approved. Refunded %s', $id, money($refundAmount)));
                } else {
                    $this->logger->warning("Return refund failed for return {id}: {message}", ['id' => $id, 'message' => $result->message]);
                }
            } else {
                // If no payment method (e.g. manual), just update order status
                $newRefundedAmount = $order->refunded_amount + $refundAmount;
                $refundStatus = ($newRefundedAmount >= $order->total) ? 'fully_refunded' : 'partially_refunded';

                if ($newRefundedAmount >= $order->total) {
                    $orderStatus = Order::STATUS_FULLY_REFUNDED;
                } else {
                    $orderStatus = Order::STATUS_PARTIAL_REFUND;
                }

                $this->orderService->updateRefundInfo($order->id, $refundStatus, $newRefundedAmount, $actuallyRefundDelivery);
                $this->orderService->updateStatus($order->id, $orderStatus, $userId, sprintf('Return #%d approved. Manual refund of %s', $id, money($refundAmount)));
            }

            // 2. Update Return status
            $this->repository->updateStatus($id, ReturnOrder::STATUS_APPROVED, null, $refundAmount);

            // 3. Replenish Stock
            $this->repository->replenishStock($return->items);

            $productService = \App\Core\Container::getInstance()->get(ProductServiceInterface::class);
            foreach ($return->items as $item) {
                if ($item->product_id) {
                    $productService->syncPurchasableStatus($item->product_id);
                }
            }

            $this->repository->commit();

            // Refresh the return object for the email
            $return = $this->findById($id);

            // Send Email
            $this->emailService->sendReturnUpdateEmail($return, $order->customer_email);

            return true;
        } catch (\Exception $e) {
            if ($this->repository->inTransaction()) {
                $this->repository->rollBack();
            }
            $this->logger->error("Failed to approve return {id}: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }

    public function rejectReturn(int $id, string $reason, ?int $userId = null): bool {
        $return = $this->findById($id);
        if (!$return) return false;

        try {
            $this->repository->beginTransaction();

            $this->repository->updateStatus($id, ReturnOrder::STATUS_REJECTED, $reason);

            // Update order status
            $order = $this->orderService->findById($return->order_id);
            if ($order->refunded_amount >= $order->total && $order->total > 0) {
                $orderStatus = Order::STATUS_FULLY_REFUNDED;
            } elseif ($order->refunded_amount > 0) {
                $orderStatus = Order::STATUS_PARTIAL_REFUND;
            } else {
                // Check if there are other pending returns
                if ($this->repository->getPendingReturnCount($return->order_id, $id) > 0) {
                    $orderStatus = Order::STATUS_RETURNING;
                } else {
                    $orderStatus = Order::STATUS_NOT_REFUNDED;
                }
            }

            $this->orderService->updateStatus($return->order_id, $orderStatus, $userId, 'Return #' . $id . ' rejected: ' . $reason);

            $this->repository->commit();

            $return = $this->findById($id);
            $order = $this->orderService->findById($return->order_id);
            if ($order) {
                $this->emailService->sendReturnUpdateEmail($return, $order->customer_email);
            }

            return true;
        } catch (\Exception $e) {
            if ($this->repository->inTransaction()) {
                $this->repository->rollBack();
            }
            $this->logger->error("Failed to reject return {id}: " . $e->getMessage(), ['id' => $id]);
            return false;
        }
    }
}

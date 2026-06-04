<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Events\RefundProcessed;
use App\Services\EmailServiceInterface;
use Psr\Log\LoggerInterface;

class OrderListener implements ListenerInterface {
    public function __construct(
        private EmailServiceInterface $emailService,
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        if ($event instanceof OrderStatusUpdated) {
            $this->handleOrderStatusUpdated($event);
        } elseif ($event instanceof RefundProcessed) {
            $this->handleRefundProcessed($event);
        }
    }

    private function handleOrderStatusUpdated(OrderStatusUpdated $event): void {
        $order = $event->order;

        // Send order confirmation email upon transition to Paid status
        if ($event->newStatus === \App\Models\Order::STATUS_PAID) {
            $emailItems = array_map(fn($i) => [
                'name' => property_exists($i, 'product_name') ? $i->product_name : ($i->name ?? ''),
                'variant_name' => $i->variant_name ?? '',
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
                'vat_rate' => $i->vat_rate,
                'vat_amount' => $i->vat_amount,
                'metadata' => $i->metadata ?? null,
                'bundle_components' => $i->bundle_components ?? []
            ], $order->items);

            $this->emailService->sendOrderConfirmation($order, $emailItems);
            return;
        }

        // Only send emails for specific status transitions
        $notifyStatuses = [
            \App\Models\Order::STATUS_SHIPPED,
            \App\Models\Order::STATUS_DELIVERED
        ];

        if (!in_array($event->newStatus, $notifyStatuses)) {
            return;
        }

        $this->emailService->sendStatusUpdateEmail(
            $event->order->customer_email,
            $event->order,
            $event->newStatus
        );
    }

    private function handleRefundProcessed(RefundProcessed $event): void {
        $this->logger->info("Refund processed for order #{$event->order->id}", [
            'amount' => $event->amount,
            'order_id' => $event->order->id
        ]);
    }
}

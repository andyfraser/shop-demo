<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Services\VirtualProductServiceInterface;
use Psr\Log\LoggerInterface;

class VirtualProductListener implements ListenerInterface {
    public function __construct(
        private VirtualProductServiceInterface $virtualProductService,
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        if ($event instanceof OrderStatusUpdated) {
            $this->handleOrderStatusUpdated($event);
        }
    }

    private function handleOrderStatusUpdated(OrderStatusUpdated $event): void {
        $order = $event->order;
        // Fulfill digital items when status updates to paid or confirmed
        if ($event->newStatus === Order::STATUS_PAID || $event->newStatus === Order::STATUS_CONFIRMED) {
            $this->logger->info("Order #{$order->id} paid or confirmed, triggering virtual product fulfillment.");
            $this->virtualProductService->fulfillDigitalItems($order, $order->items);
        }
    }
}

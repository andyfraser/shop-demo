<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\OrderPlaced;
use App\Services\EmailServiceInterface;

class OrderEmailListener implements ListenerInterface {
    public function __construct(
        private EmailServiceInterface $emailService
    ) {}

    public function handle(Event $event): void {
        if (!$event instanceof OrderPlaced) {
            return;
        }

        $order = $event->order;
        $items = $event->items;

        // Convert items to array structure for email service if needed
        // The email service expects:
        // array_map(fn($i) => [
        //     'name' => $i->product_name ?? $i->name,
        //     'variant_name' => $i->variant_name,
        //     'quantity' => $i->quantity,
        //     'unit_price' => $i->unit_price,
        //     'vat_rate' => $i->vat_rate,
        //     'vat_amount' => $i->vat_amount
        // ], $order->items);
        
        $emailItems = array_map(fn($i) => [
            'name' => property_exists($i, 'product_name') ? $i->product_name : ($i->name ?? ''),
            'variant_name' => $i->variant_name ?? '',
            'quantity' => $i->quantity,
            'unit_price' => $i->unit_price,
            'vat_rate' => $i->vat_rate,
            'vat_amount' => $i->vat_amount
        ], $items);

        $this->emailService->sendOrderConfirmation($order, $emailItems);
    }
}

<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\StockUpdated;
use App\Events\OrderPlaced;
use App\Services\ProductServiceInterface;

class ProductAvailabilityListener implements ListenerInterface {
    public function __construct(
        private ProductServiceInterface $productService
    ) {}

    public function handle(Event $event): void {
        if ($event instanceof StockUpdated) {
            if ($event->isVariant) {
                $variant = $this->productService->findVariantById($event->id);
                if ($variant) {
                    $this->productService->syncPurchasableStatus($variant->product_id);
                }
            } else {
                $this->productService->syncPurchasableStatus($event->id);
            }
        } elseif ($event instanceof OrderPlaced) {
            foreach ($event->items as $item) {
                if ($item->product_id) {
                    $this->productService->syncPurchasableStatus($item->product_id);
                }
            }
        }
    }
}

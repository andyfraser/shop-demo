<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\StockUpdated;
use App\Events\OrderPlaced;
use App\Services\SettingsServiceInterface;
use App\Services\ProductServiceInterface;
use Psr\Log\LoggerInterface;

class InventoryListener implements ListenerInterface {
    public function __construct(
        private SettingsServiceInterface $settingsService,
        private ProductServiceInterface $productService,
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        $threshold = (int) $this->settingsService->get('low_stock_threshold');

        if ($event instanceof StockUpdated) {
            if (!$event->isVariant) {
                $product = $this->productService->findById($event->id);
                if ($product && ($product->is_virtual || $product->is_bundle)) {
                    return;
                }
            } else {
                $variant = $this->productService->findVariantById($event->id);
                if ($variant) {
                    $product = $this->productService->findById($variant->product_id);
                    if ($product && ($product->is_virtual || $product->is_bundle)) {
                        return;
                    }
                }
            }

            if ($event->newStock <= $threshold) {
                $type = $event->isVariant ? 'variant' : 'product';
                $this->logger->warning("Low stock warning for {$type} #{$event->id}", [
                    'type' => $type,
                    'id' => $event->id,
                    'old_stock' => $event->oldStock,
                    'new_stock' => $event->newStock,
                    'threshold' => $threshold
                ]);
            }
        } elseif ($event instanceof OrderPlaced) {
            foreach ($event->items as $item) {
                $this->productService->clearCache($item->product_id);
                if ($item->variant_id) {
                    $variant = $this->productService->findVariantById($item->variant_id);
                    if ($variant) {
                        $product = $this->productService->findById($variant->product_id);
                        if ($product && ($product->is_virtual || $product->is_bundle)) {
                            continue;
                        }
                        if ($variant->stock <= $threshold) {
                            $this->logger->warning("Low stock warning for variant #{$variant->id}", [
                                'type' => 'variant',
                                'id' => $variant->id,
                                'old_stock' => 'N/A',
                                'new_stock' => $variant->stock,
                                'threshold' => $threshold
                            ]);
                        }
                    }
                } else {
                    $product = $this->productService->findById($item->product_id);
                    if ($product) {
                        if ($product->is_virtual || $product->is_bundle) {
                            continue;
                        }
                        if ($product->stock <= $threshold) {
                            $this->logger->warning("Low stock warning for product #{$product->id}", [
                                'type' => 'product',
                                'id' => $product->id,
                                'old_stock' => 'N/A',
                                'new_stock' => $product->stock,
                                'threshold' => $threshold
                            ]);
                        }
                    }
                }
            }
        }
    }
}

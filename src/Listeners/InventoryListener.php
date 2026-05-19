<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\StockUpdated;
use App\Services\SettingsServiceInterface;
use Psr\Log\LoggerInterface;

class InventoryListener implements ListenerInterface {
    public function __construct(
        private SettingsServiceInterface $settingsService,
        private LoggerInterface $logger
    ) {}

    public function handle(Event $event): void {
        if (!$event instanceof StockUpdated) {
            return;
        }

        $threshold = (int) $this->settingsService->get('low_stock_threshold');
        if ($event->newStock < $threshold) {
            $this->logger->warning("Low stock warning for variant #{$event->variantId}", [
                'variant_id' => $event->variantId,
                'old_stock' => $event->oldStock,
                'new_stock' => $event->newStock,
                'threshold' => $threshold
            ]);
        }
    }
}

<?php

namespace App\Listeners;

use App\Core\Events\Event;
use App\Core\Events\ListenerInterface;
use App\Events\SettingUpdated;
use App\Services\AuditLogService;

class AuditListener implements ListenerInterface {
    public function __construct(
        private AuditLogService $auditLogService
    ) {}

    public function handle(Event $event): void {
        if (!$event instanceof SettingUpdated) {
            return;
        }

        $this->auditLogService->log(
            'setting_update',
            'system',
            $event->key,
            [
                'old' => $event->oldValue,
                'new' => $event->newValue
            ]
        );
    }
}

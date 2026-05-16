<?php

namespace App\Commands;

use App\Services\SettingsServiceInterface;

class MaintenanceDownCommand implements CommandInterface {
    public function __construct(
        private SettingsServiceInterface $settingsService
    ) {}

    public function getName(): string {
        return 'maintenance:down';
    }

    public function getDescription(): string {
        return 'Enables maintenance mode.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        $this->settingsService->set('maintenance_mode', '1');
        echo "Maintenance mode ENABLED.\n";
        return 0;
    }
}

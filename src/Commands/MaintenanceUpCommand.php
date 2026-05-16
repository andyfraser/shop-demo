<?php

namespace App\Commands;

use App\Services\SettingsServiceInterface;

class MaintenanceUpCommand implements CommandInterface {
    public function __construct(
        private SettingsServiceInterface $settingsService
    ) {}

    public function getName(): string {
        return 'maintenance:up';
    }

    public function getDescription(): string {
        return 'Disables maintenance mode.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        $this->settingsService->set('maintenance_mode', '0');
        echo "Maintenance mode DISABLED.\n";
        return 0;
    }
}

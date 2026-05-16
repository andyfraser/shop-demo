<?php

namespace App\Commands;

use App\Services\SettingsServiceInterface;

class SchedulePauseCommand implements CommandInterface {
    public function __construct(
        private SettingsServiceInterface $settingsService
    ) {}

    public function getName(): string {
        return 'schedule:pause';
    }

    public function getDescription(): string {
        return 'Temporarily stops all scheduled tasks from running.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        $this->settingsService->set('scheduler_paused', '1');
        echo "Scheduled tasks have been PAUSED.\n";
        return 0;
    }
}

<?php

namespace App\Commands;

use App\Services\SettingsServiceInterface;

class ScheduleResumeCommand implements CommandInterface {
    public function __construct(
        private SettingsServiceInterface $settingsService
    ) {}

    public function getName(): string {
        return 'schedule:resume';
    }

    public function getDescription(): string {
        return 'Resumes execution of scheduled tasks.';
    }

    public function getSchedule(): ?string {
        return null;
    }

    public function execute(): int {
        $this->settingsService->set('scheduler_paused', '0');
        echo "Scheduled tasks have been RESUMED.\n";
        return 0;
    }
}

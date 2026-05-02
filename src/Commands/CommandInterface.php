<?php

namespace App\Commands;

use App\Core\Container;

interface CommandInterface {
    /**
     * Execute the command.
     * 
     * @return int Exit code (0 for success).
     */
    public function execute(): int;

    /**
     * Get the descriptive name of the command.
     */
    public function getName(): string;

    /**
     * Get the description of what the command does.
     */
    public function getDescription(): string;

    /**
     * Get the schedule frequency or null if the command should not be scheduled automatically.
     * 
     * Supported values: 
     * - 'everyMinute', 'everyFiveMinutes', 'everyFifteenMinutes', 'everyThirtyMinutes'
     * - 'hourly', 'twiceDaily', 'daily', 'weekdays', 'weekly', 'monthly', 'yearly'
     */
    public function getSchedule(): ?string;
}

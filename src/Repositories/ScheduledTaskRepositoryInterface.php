<?php
namespace App\Repositories;

interface ScheduledTaskRepositoryInterface {
    public function exists(string $name): bool;
    public function getLastRunAt(string $name): ?string;
    public function createTask(string $name): void;
    public function updateLastRun(string $name, string $lastRunAt): void;
}

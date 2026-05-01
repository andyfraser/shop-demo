<?php

namespace App\Core;

use App\Commands\CommandInterface;
use PDO;

class Scheduler {
    private array $commands;
    private PDO $db;

    /**
     * @param PDO $db
     * @param CommandInterface[] $commands
     */
    public function __construct(PDO $db, array $commands = []) {
        $this->db = $db;
        $this->commands = $commands;
    }

    public function run(): void {
        echo "Running scheduler at " . date('Y-m-d H:i:s') . "\n";

        foreach ($this->commands as $command) {
            $frequency = $command->getSchedule();
            if (!$frequency) {
                continue;
            }

            $name = $command->getName();

            if ($this->isDue($name, $frequency)) {
                echo "Executing command: {$name} ({$frequency})... ";
                $command->execute();
                $this->updateLastRun($name);
                echo "Finished.\n";
            } else {
                echo "Command {$name} is not due yet.\n";
            }
        }
    }

    private function isDue(string $name, string $frequency): bool {
        $stmt = $this->db->prepare("SELECT last_run_at FROM scheduled_tasks WHERE name = ?");
        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // First time running, insert record
            $this->db->prepare("INSERT INTO scheduled_tasks (name) VALUES (?)")->execute([$name]);
            return true;
        }

        $lastRunAt = $row['last_run_at'];
        if (!$lastRunAt) return true;

        $lastRun = strtotime($lastRunAt);
        $now = time();

        switch ($frequency) {
            case 'everyMinute':
                return ($now - $lastRun) >= 59; 
            case 'hourly':
                return date('Y-m-d H', $now) !== date('Y-m-d H', $lastRun);
            case 'daily':
                return date('Y-m-d', $now) !== date('Y-m-d', $lastRun);
            case 'weekly':
                return (date('W', $now) !== date('W', $lastRun)) || (date('Y', $now) !== date('Y', $lastRun));
            default:
                return false;
        }
    }

    private function updateLastRun(string $name): void {
        $stmt = $this->db->prepare("UPDATE scheduled_tasks SET last_run_at = CURRENT_TIMESTAMP WHERE name = ?");
        $stmt->execute([$name]);
    }
}

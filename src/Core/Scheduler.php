<?php

namespace App\Core;

use App\Commands\CommandInterface;
use PDO;
use Psr\Log\LoggerInterface;

class Scheduler {
    private array $commands;
    private PDO $db;
    private ?LoggerInterface $logger;

    /**
     * @param PDO $db
     * @param CommandInterface[] $commands
     * @param LoggerInterface|null $logger
     */
    public function __construct(PDO $db, array $commands = [], ?LoggerInterface $logger = null) {
        $this->db = $db;
        $this->commands = $commands;
        $this->logger = $logger;
    }

    public function run(): void {
        $timestamp = date('Y-m-d H:i:s');
        echo "Running scheduler at " . $timestamp . "\n";
        if ($this->logger) {
            $this->logger->info("Scheduler run started at {timestamp}", ['timestamp' => $timestamp]);
        }

        foreach ($this->commands as $command) {
            $frequency = $command->getSchedule();
            if (!$frequency) {
                continue;
            }

            $name = $command->getName();

            if ($this->isDue($name, $frequency)) {
                echo "Executing command: {$name} ({$frequency})... ";
                if ($this->logger) {
                    $this->logger->info("Executing scheduled command: {name} ({frequency})", [
                        'name' => $name,
                        'frequency' => $frequency
                    ]);
                }

                try {
                    $exitCode = $command->execute();
                    $this->updateLastRun($name);
                    echo "Finished with exit code {$exitCode}.\n";
                    
                    if ($this->logger) {
                        $this->logger->info("Scheduled command {name} finished with exit code {exitCode}", [
                            'name' => $name,
                            'exitCode' => $exitCode
                        ]);
                    }
                } catch (\Exception $e) {
                    echo "Failed!\n";
                    if ($this->logger) {
                        $this->logger->error("Scheduled command {name} failed: {error}", [
                            'name' => $name,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
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
            case 'everyFiveMinutes':
                return ($now - $lastRun) >= 299;
            case 'everyFifteenMinutes':
                return ($now - $lastRun) >= 899;
            case 'everyThirtyMinutes':
                return ($now - $lastRun) >= 1799;
            case 'hourly':
                return date('Y-m-d H', $now) !== date('Y-m-d H', $lastRun);
            case 'twiceDaily':
                return ($now - $lastRun) >= 43199;
            case 'daily':
                return date('Y-m-d', $now) !== date('Y-m-d', $lastRun);
            case 'weekdays':
                return (date('Y-m-d', $now) !== date('Y-m-d', $lastRun)) && (date('N', $now) <= 5);
            case 'weekly':
                return (date('W', $now) !== date('W', $lastRun)) || (date('Y', $now) !== date('Y', $lastRun));
            case 'monthly':
                return date('Y-m', $now) !== date('Y-m', $lastRun);
            case 'yearly':
                return date('Y', $now) !== date('Y', $lastRun);
            default:
                return false;
        }
    }

    private function updateLastRun(string $name): void {
        $stmt = $this->db->prepare("UPDATE scheduled_tasks SET last_run_at = CURRENT_TIMESTAMP WHERE name = ?");
        $stmt->execute([$name]);
    }
}

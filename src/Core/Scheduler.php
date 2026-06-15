<?php

namespace App\Core;

use App\Commands\CommandInterface;
use App\Repositories\ScheduledTaskRepositoryInterface;
use Psr\Log\LoggerInterface;

class Scheduler {
    private array $commands;
    private ScheduledTaskRepositoryInterface $taskRepository;
    private ?\Psr\Log\LoggerInterface $logger;
    private \App\Services\SettingsServiceInterface $settingsService;

    /**
     * @param ScheduledTaskRepositoryInterface $taskRepository
     * @param \App\Services\SettingsServiceInterface $settingsService
     * @param CommandInterface[] $commands
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(
        ScheduledTaskRepositoryInterface $taskRepository,
        \App\Services\SettingsServiceInterface $settingsService,
        array $commands = [],
        ?\Psr\Log\LoggerInterface $logger = null
    ) {
        $this->taskRepository = $taskRepository;
        $this->settingsService = $settingsService;
        $this->commands = $commands;
        $this->logger = $logger;
    }

    public function run(): void {
        $lockFile = sys_get_temp_dir() . '/demoshop_backup_restore.lock';
        if (file_exists($lockFile)) {
            echo "Scheduler is currently DISABLED because a database backup or restore is in progress. Skipping execution.\n";
            if ($this->logger) {
                $this->logger->info("Scheduler run skipped because a backup/restore is in progress.");
            }
            return;
        }

        if ($this->settingsService->get('scheduler_paused') === '1') {
            echo "Scheduler is currently PAUSED. Skipping execution.\n";
            if ($this->logger) {
                $this->logger->info("Scheduler run skipped because it is paused.");
            }
            return;
        }

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
                $lockDir = dirname(__DIR__, 2) . '/logs';
                if (!is_dir($lockDir)) {
                    mkdir($lockDir, 0755, true);
                }
                
                $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
                $lockFilePath = $lockDir . '/scheduler_task_' . $safeName . '.lock';
                
                $lockFile = @fopen($lockFilePath, 'c');
                if (!$lockFile) {
                    echo "Executing command: {$name} ({$frequency})... Error: Could not open or create lock file at {$lockFilePath}\n";
                    if ($this->logger) {
                        $this->logger->error("Could not open/create lock file for task {name}", ['name' => $name]);
                    }
                    continue;
                }

                $acquired = flock($lockFile, LOCK_EX | LOCK_NB);
                if (!$acquired) {
                    echo "Command {$name} is already running. Skipping.\n";
                    if ($this->logger) {
                        $this->logger->info("Scheduled command {name} execution skipped because another instance holds the lock.", ['name' => $name]);
                    }
                    fclose($lockFile);
                    continue;
                }

                echo "Executing command: {$name} ({$frequency})... ";
                if ($this->logger) {
                    $this->logger->info("Executing scheduled command: {name} ({frequency})", [
                        'name' => $name,
                        'frequency' => $frequency
                    ]);
                }

                try {
                    $exitCode = $command->execute();
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
                } finally {
                    $this->updateLastRun($name);
                    flock($lockFile, LOCK_UN);
                    fclose($lockFile);
                }
            } else {
                echo "Command {$name} is not due yet.\n";
            }
        }
    }

    public function isDue(string $name, string $frequency): bool {
        if (!$this->taskRepository->exists($name)) {
            // First time running, insert record
            $this->taskRepository->createTask($name);
            return true;
        }

        $lastRunAt = $this->taskRepository->getLastRunAt($name);
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
        $this->taskRepository->updateLastRun($name, date('Y-m-d H:i:s'));
    }
}

<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\HtmlResponse;
use App\Core\Responses\RedirectResponse;
use App\Core\Renderer;
use App\Services\SettingsServiceInterface;
use Psr\Log\LoggerInterface;
use App\Core\Container;
use PDO;

class TextResponse extends Response {
    public function __construct(string $content, int $statusCode = 200, array $headers = []) {
        parent::__construct($content, $statusCode, $headers);
        $this->setHeader('Content-Type', 'text/plain; charset=UTF-8');
    }
}

class AdminSchedulerController {
    public function __construct(
        private Renderer $renderer,
        private SettingsServiceInterface $settingsService,
        private \App\Repositories\ScheduledTaskRepositoryInterface $taskRepository,
        private Container $container,
        private LoggerInterface $logger
    ) {}

    public function index(Request $request): Response {
        // Automatically generate cron security token if empty
        $token = $this->settingsService->get('cron_security_token');
        if (empty($token)) {
            $token = bin2hex(random_bytes(16));
            $this->settingsService->set('cron_security_token', $token);
        }

        $commands = $this->getCommands();
        $scheduler = new \App\Core\Scheduler($this->taskRepository, $this->settingsService, array_values($commands), $this->logger);
        
        $tasks = [];
        foreach ($commands as $command) {
            $name = $command->getName();
            $frequency = $command->getSchedule();
            if (!$frequency) {
                continue;
            }

            $lastRunAt = $this->getLastRunAt($name);
            $isDue = $scheduler->isDue($name, $frequency);
            $nextDueAt = $this->calculateNextDue($frequency, $lastRunAt);
            
            $tasks[] = [
                'name' => $name,
                'description' => $command->getDescription(),
                'frequency' => $frequency,
                'last_run_at' => $lastRunAt,
                'last_run_relative' => $lastRunAt ? $this->formatRelativeTime($lastRunAt) : 'Never',
                'is_due' => $isDue,
                'next_due_at' => $nextDueAt ? date('Y-m-d H:i:s', $nextDueAt) : 'N/A',
                'next_due_relative' => $nextDueAt ? $this->formatRelativeTimeFuture($nextDueAt) : 'N/A',
            ];
        }

        $isPaused = $this->settingsService->get('scheduler_paused') === '1';

        // Retrieve logs/console output from flash session if available
        $consoleOutput = flash('scheduler_output');

        return new HtmlResponse($this->renderer->adminRender('scheduler', [
            'page_title' => 'Task Scheduler',
            'active' => 'scheduler',
            'tasks' => $tasks,
            'is_paused' => $isPaused,
            'cron_token' => $token,
            'console_output' => $consoleOutput,
        ]));
    }

    public function togglePause(Request $request): Response {
        $isPaused = $this->settingsService->get('scheduler_paused') === '1';
        $newStatus = $isPaused ? '0' : '1';
        $this->settingsService->set('scheduler_paused', $newStatus);

        $statusMsg = $newStatus === '1' ? 'paused' : 'resumed';
        $this->logger->info("Scheduler has been manually {$statusMsg} via Admin Panel.");
        flash('success', "Scheduler successfully " . $statusMsg . ".");

        return new RedirectResponse('/admin/scheduler');
    }

    public function runTask(Request $request): Response {
        $taskName = $request->getPost('task');
        $commands = $this->getCommands();

        if (!isset($commands[$taskName])) {
            flash('error', "Task '{$taskName}' not found.");
            return new RedirectResponse('/admin/scheduler');
        }

        $command = $commands[$taskName];
        $frequency = $command->getSchedule();

        // Enforce scheduling guard: Do not run tasks before their next scheduled run time.
        $scheduler = new \App\Core\Scheduler($this->taskRepository, $this->settingsService, [$command], $this->logger);
        if ($frequency && !$scheduler->isDue($taskName, $frequency)) {
            $lastRunAt = $this->getLastRunAt($taskName);
            $nextDue = $this->calculateNextDue($frequency, $lastRunAt);
            $remaining = $nextDue - time();
            $remainingStr = $remaining > 0 ? $this->formatDuration($remaining) : 'shortly';

            flash('error', "Cannot run task '{$taskName}'. It is not due yet. Next run allowed in: {$remainingStr}.");
            return new RedirectResponse('/admin/scheduler');
        }

        // Run command and capture output
        ob_start();
        $exitCode = 1;
        try {
            $exitCode = $command->execute();
            $output = ob_get_clean();
            $this->updateLastRun($taskName);
            flash('success', "Task '{$taskName}' ran successfully.");
        } catch (\Exception $e) {
            $output = ob_get_clean() . "\n[Exception Error] " . $e->getMessage();
            $this->updateLastRun($taskName);
            flash('error', "Task '{$taskName}' failed during execution.");
        }

        $formattedOutput = "=== MANUAL TASK RUN: {$taskName} ===\n"
            . "Time: " . date('Y-m-d H:i:s') . " (UTC)\n"
            . "Exit Code: {$exitCode}\n"
            . "--------------------------------------------------\n"
            . (!empty($output) ? $output : "No terminal output printed.\n");

        flash('scheduler_output', $formattedOutput);
        return new RedirectResponse('/admin/scheduler');
    }

    public function runAllDue(Request $request): Response {
        $commands = $this->getCommands();
        $scheduler = new \App\Core\Scheduler($this->taskRepository, $this->settingsService, array_values($commands), $this->logger);

        ob_start();
        try {
            $scheduler->run();
            $output = ob_get_clean();
            flash('success', "Scheduler run executed successfully.");
        } catch (\Exception $e) {
            $output = ob_get_clean() . "\n[Exception Error] " . $e->getMessage();
            flash('error', "Scheduler run failed during execution.");
        }

        $formattedOutput = "=== MANUAL RUN ALL DUE TASKS ===\n"
            . "Time: " . date('Y-m-d H:i:s') . " (UTC)\n"
            . "--------------------------------------------------\n"
            . (!empty($output) ? $output : "No tasks were due or executed.\n");

        flash('scheduler_output', $formattedOutput);
        return new RedirectResponse('/admin/scheduler');
    }

    public function regenToken(Request $request): Response {
        $newToken = bin2hex(random_bytes(16));
        $this->settingsService->set('cron_security_token', $newToken);
        flash('success', "Web Cron security token regenerated.");
        return new RedirectResponse('/admin/scheduler');
    }

    public function runWebCron(Request $request): Response {
        $suppliedToken = $request->getQuery('token');
        $validToken = $this->settingsService->get('cron_security_token');

        if (empty($validToken) || $suppliedToken !== $validToken) {
            return new TextResponse("Access Denied: Invalid or missing token.", 403);
        }

        $commands = $this->getCommands();
        $scheduler = new \App\Core\Scheduler($this->taskRepository, $this->settingsService, array_values($commands), $this->logger);

        ob_start();
        $time = date('Y-m-d H:i:s');
        echo "=== WEB CRON TRIGGER: START {$time} ===\n";
        try {
            $scheduler->run();
            echo "=== WEB CRON TRIGGER: FINISHED SUCCESS ===\n";
        } catch (\Exception $e) {
            echo "=== WEB CRON TRIGGER: FINISHED WITH EXCEPTION ===\n";
            echo "Error: " . $e->getMessage() . "\n";
        }
        $output = ob_get_clean();

        return new TextResponse($output);
    }

    /**
     * Helper to discover commands in src/Commands/
     */
    protected function getCommands(): array {
        $commands = [];
        $commandDir = dirname(__DIR__) . '/Commands';
        $files = scandir($commandDir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'CommandInterface.php') {
                continue;
            }

            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = 'App\\Commands\\' . pathinfo($file, PATHINFO_FILENAME);
                if (class_exists($className)) {
                    $reflection = new \ReflectionClass($className);
                    if ($reflection->implementsInterface(\App\Commands\CommandInterface::class) && !$reflection->isAbstract()) {
                        $command = $this->container->get($className);
                        $commands[$command->getName()] = $command;
                    }
                }
            }
        }
        return $commands;
    }

    private function getLastRunAt(string $name): ?string {
        return $this->taskRepository->getLastRunAt($name);
    }

    private function updateLastRun(string $name): void {
        $this->taskRepository->updateLastRun($name, date('Y-m-d H:i:s'));
    }

    private function calculateNextDue(string $frequency, ?string $lastRunAt): ?int {
        if (!$lastRunAt) {
            return time(); // Due now if never run
        }

        $lastRun = strtotime($lastRunAt);
        switch ($frequency) {
            case 'everyMinute':
                return $lastRun + 60;
            case 'everyFiveMinutes':
                return $lastRun + 300;
            case 'everyFifteenMinutes':
                return $lastRun + 900;
            case 'everyThirtyMinutes':
                return $lastRun + 1800;
            case 'hourly':
                return strtotime(date('Y-m-d H:00:00', $lastRun) . ' +1 hour');
            case 'twiceDaily':
                return $lastRun + 43200;
            case 'daily':
            case 'weekdays':
                return strtotime(date('Y-m-d 00:00:00', $lastRun) . ' +1 day');
            case 'weekly':
                return strtotime(date('Y-m-d 00:00:00', $lastRun) . ' +1 week');
            case 'monthly':
                return strtotime(date('Y-m-d 00:00:00', $lastRun) . ' +1 month');
            case 'yearly':
                return strtotime(date('Y-m-d 00:00:00', $lastRun) . ' +1 year');
            default:
                return null;
        }
    }

    private function formatRelativeTime(string $datetime): string {
        $time = strtotime($datetime);
        $diff = time() - $time;
        if ($diff < 5) return 'just now';
        if ($diff < 60) return $diff . ' seconds ago';
        $mins = round($diff / 60);
        if ($mins < 60) return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        $hours = round($diff / 3600);
        if ($hours < 24) return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        $days = round($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }

    private function formatRelativeTimeFuture(int $timestamp): string {
        $diff = $timestamp - time();
        if ($diff <= 0) return 'Due now';
        if ($diff < 60) return 'in ' . $diff . ' seconds';
        $mins = round($diff / 60);
        if ($mins < 60) return 'in ' . $mins . ' minute' . ($mins > 1 ? 's' : '');
        $hours = round($diff / 3600);
        if ($hours < 24) return 'in ' . $hours . ' hour' . ($hours > 1 ? 's' : '');
        $days = round($diff / 86400);
        return 'in ' . $days . ' day' . ($days > 1 ? 's' : '');
    }

    private function formatDuration(int $seconds): string {
        if ($seconds < 60) return $seconds . 's';
        $mins = floor($seconds / 60);
        $secs = $seconds % 60;
        if ($mins < 60) return $mins . 'm' . ($secs > 0 ? ' ' . $secs . 's' : '');
        $hours = floor($mins / 60);
        $mins = $mins % 60;
        return $hours . 'h' . ($mins > 0 ? ' ' . $mins . 'm' : '');
    }
}

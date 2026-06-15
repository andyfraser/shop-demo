<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\Scheduler;
use App\Commands\CommandInterface;
use App\Services\SettingsServiceInterface;
use PDO;

class SchedulerMockSettingsService implements SettingsServiceInterface {
    public array $settings = [];
    public function getSettings(): \App\Models\Settings { return new \App\Models\Settings(new \Tests\NullLogger()); }
    public function get(string $key): mixed { return $this->settings[$key] ?? null; }
    public function all(): array { return $this->settings; }
    public function set(string $key, mixed $value): void { $this->settings[$key] = $value; }
}

class MockCommand implements CommandInterface {
    public bool $executed = false;
    public ?string $schedule = null;
    public string $name = 'mock:command';

    public function execute(): int {
        $this->executed = true;
        return 0;
    }

    public function getName(): string { return $this->name; }
    public function getDescription(): string { return 'Mock'; }
    public function getSchedule(): ?string { return $this->schedule; }
}

class SchedulerTest extends TestCase {
    private ?PDO $db = null;
    private ?\App\Repositories\ScheduledTaskRepository $taskRepository = null;

    public function setUp(): void {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec("CREATE TABLE scheduled_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            last_run_at DATETIME
        )");
        $this->taskRepository = new \App\Repositories\ScheduledTaskRepository($this->db);
    }

    public function testSchedulerExecutesFirstTime(): void {
        $command = new MockCommand();
        $command->schedule = 'daily';
        
        $settings = new SchedulerMockSettingsService();
        $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);
        
        // Suppress output for tests
        ob_start();
        $scheduler->run();
        ob_end_clean();
        
        $this->assertTrue($command->executed);
        
        // Verify record in DB
        $stmt = $this->db->query("SELECT * FROM scheduled_tasks WHERE name = 'mock:command'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($row);
        $this->assertNotNull($row['last_run_at']);
    }

    public function testSchedulerSkipsIfNotDue(): void {
        $command = new MockCommand();
        $command->schedule = 'daily';
        
        // Insert a record that says it ran just now
        $stmt = $this->db->prepare("INSERT INTO scheduled_tasks (name, last_run_at) VALUES (?, ?)");
        $stmt->execute([$command->name, date('Y-m-d H:i:s')]);
        
        $settings = new SchedulerMockSettingsService();
        $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);
        
        ob_start();
        $scheduler->run();
        ob_end_clean();
        
        $this->assertFalse($command->executed);
    }

    public function testEveryFiveMinutesFrequency(): void {
        $command = new MockCommand();
        $command->schedule = 'everyFiveMinutes';
        
        // Set last run to 6 minutes ago
        $sixMinsAgo = date('Y-m-d H:i:s', time() - 360);
        $stmt = $this->db->prepare("INSERT INTO scheduled_tasks (name, last_run_at) VALUES (?, ?)");
        $stmt->execute([$command->name, $sixMinsAgo]);
        
        $settings = new SchedulerMockSettingsService();
        $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);
        
        ob_start();
        $scheduler->run();
        ob_end_clean();
        
        $this->assertTrue($command->executed);
    }

    public function testEveryFiveMinutesFrequencySkips(): void {
        $command = new MockCommand();
        $command->schedule = 'everyFiveMinutes';
        
        // Set last run to 4 minutes ago
        $fourMinsAgo = date('Y-m-d H:i:s', time() - 240);
        $stmt = $this->db->prepare("INSERT INTO scheduled_tasks (name, last_run_at) VALUES (?, ?)");
        $stmt->execute([$command->name, $fourMinsAgo]);
        
        $settings = new SchedulerMockSettingsService();
        $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);
        
        ob_start();
        $scheduler->run();
        ob_end_clean();
        
        $this->assertFalse($command->executed);
    }

    public function testTwiceDailyFrequency(): void {
        $command = new MockCommand();
        $command->schedule = 'twiceDaily';
        
        // 13 hours ago
        $thirteenHoursAgo = date('Y-m-d H:i:s', time() - (13 * 3600));
        $stmt = $this->db->prepare("INSERT INTO scheduled_tasks (name, last_run_at) VALUES (?, ?)");
        $stmt->execute([$command->name, $thirteenHoursAgo]);
        
        $settings = new SchedulerMockSettingsService();
        $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);
        
        ob_start();
        $scheduler->run();
        ob_end_clean();
        
        $this->assertTrue($command->executed);
    }

    public function testTwiceDailyFrequencySkips(): void {
        $command = new MockCommand();
        $command->schedule = 'twiceDaily';
        
        // 11 hours ago
        $elevenHoursAgo = date('Y-m-d H:i:s', time() - (11 * 3600));
        $stmt = $this->db->prepare("INSERT INTO scheduled_tasks (name, last_run_at) VALUES (?, ?)");
        $stmt->execute([$command->name, $elevenHoursAgo]);
        
        $settings = new SchedulerMockSettingsService();
        $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);
        
        ob_start();
        $scheduler->run();
        ob_end_clean();
        
        $this->assertFalse($command->executed);
    }

    public function testSchedulerSkipsWhenPaused(): void {
        $command = new MockCommand();
        $command->schedule = 'everyMinute';
        
        $settings = new SchedulerMockSettingsService();
        $settings->set('scheduler_paused', '1');
        
        $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);
        
        ob_start();
        $scheduler->run();
        $output = ob_get_clean();
        
        $this->assertFalse($command->executed);
        $this->assertStringContainsString('Scheduler is currently PAUSED', $output);
    }

    public function testSchedulerUpdatesLastRunEvenOnFailure(): void {
        $command = new class implements CommandInterface {
            public function execute(): int { throw new \Exception("Command failed!"); }
            public function getName(): string { return 'failing:command'; }
            public function getDescription(): string { return 'Fails'; }
            public function getSchedule(): ?string { return 'everyMinute'; }
        };

        $settings = new SchedulerMockSettingsService();
        $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);

        ob_start();
        $scheduler->run();
        ob_end_clean();

        // Verify last_run_at was updated despite failure
        $stmt = $this->db->query("SELECT last_run_at FROM scheduled_tasks WHERE name = 'failing:command'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($row['last_run_at']);
    }

    public function testSchedulerSkipsWhenBackupRestoreLockFileExists(): void {
        $lockFile = sys_get_temp_dir() . '/demoshop_backup_restore.lock';
        file_put_contents($lockFile, 'test');

        try {
            $command = new MockCommand();
            $command->schedule = 'everyMinute';
            
            $settings = new SchedulerMockSettingsService();
            $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);
            
            ob_start();
            $scheduler->run();
            $output = ob_get_clean();
            
            $this->assertFalse($command->executed);
            $this->assertStringContainsString('Scheduler is currently DISABLED because a database backup or restore is in progress', $output);
        } finally {
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }
        }
    }

    public function testSchedulerSkipsWhenCommandIsLocked(): void {
        $command = new MockCommand();
        $command->name = 'locked:command';
        $command->schedule = 'everyMinute';
        
        $lockDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }
        $lockFilePath = $lockDir . '/scheduler_task_locked_command.lock';
        
        $fp = fopen($lockFilePath, 'c');
        $this->assertTrue($fp !== false);
        $locked = flock($fp, LOCK_EX | LOCK_NB);
        $this->assertTrue($locked);

        try {
            $settings = new SchedulerMockSettingsService();
            $scheduler = new Scheduler($this->taskRepository, $settings, [$command]);
            
            ob_start();
            $scheduler->run();
            $output = ob_get_clean();
            
            $this->assertFalse($command->executed);
            $this->assertStringContainsString('Command locked:command is already running. Skipping', $output);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
            if (file_exists($lockFilePath)) {
                @unlink($lockFilePath);
            }
        }
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Controllers\AdminSchedulerController;
use App\Commands\CommandInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Responses\RedirectResponse;
use App\Services\SettingsServiceInterface;
use App\Core\Container;
use PDO;

class ControllerMockCommand implements CommandInterface {
    public bool $executed = false;
    public ?string $schedule = null;
    public string $name = 'test:command';
    public string $description = 'Mock test command';

    public function execute(): int {
        $this->executed = true;
        echo "Mock executed successfully.";
        return 0;
    }

    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
    public function getSchedule(): ?string { return $this->schedule; }
}

class ControllerMockSettingsService implements SettingsServiceInterface {
    public array $settings = [];
    public function getSettings(): \App\Models\Settings {
        $s = new \App\Models\Settings(new \Tests\NullLogger());
        $s->fill($this->settings);
        return $s;
    }
    public function get(string $key): mixed { return $this->settings[$key] ?? null; }
    public function all(): array { return $this->settings; }
    public function set(string $key, mixed $value): void { $this->settings[$key] = $value; }
}

class StubRenderer extends \App\Core\Renderer {
    public function __construct() {}
    public function adminRender(string $template, array $vars = []): string {
        return "rendered:" . $template;
    }
}

class TestAdminSchedulerController extends AdminSchedulerController {
    public array $mockCommands = [];
    protected function getCommands(): array {
        return $this->mockCommands;
    }
}

class AdminSchedulerControllerTest extends TestCase {
    private ?PDO $db = null;
    private ?ControllerMockSettingsService $settings = null;
    private ?TestAdminSchedulerController $controller = null;
    private ?Container $container = null;
    private ?ControllerMockCommand $command = null;

    public function setUp(): void {
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec("CREATE TABLE scheduled_tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            last_run_at DATETIME DEFAULT NULL
        )");

        $this->settings = new ControllerMockSettingsService();
        $this->settings->set('cron_security_token', 'test-token-123');
        $this->settings->set('scheduler_paused', '0');

        $this->container = new Container();
        $renderer = new StubRenderer();

        $this->command = new ControllerMockCommand();
        $this->command->schedule = 'hourly';

        $this->controller = new TestAdminSchedulerController(
            $renderer,
            $this->settings,
            $this->db,
            $this->container,
            new \Tests\NullLogger()
        );
        $this->controller->mockCommands = [$this->command->getName() => $this->command];

        // Ensure clean session for flash messages
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['flash'] = [];
    }

    private function createRequest(string $method, string $uri, array $query = [], array $post = []): Request {
        return new Request(
            $query,
            $post,
            [
                'REQUEST_METHOD' => $method,
                'REQUEST_URI' => $uri
            ],
            [],
            [],
            []
        );
    }

    public function testIndexGeneratesTokenIfEmpty(): void {
        $this->settings->set('cron_security_token', '');
        
        $request = $this->createRequest('GET', '/admin/scheduler');
        $response = $this->controller->index($request);
        
        $this->assertInstanceOf(\App\Core\Responses\HtmlResponse::class, $response);
        $token = $this->settings->get('cron_security_token');
        $this->assertNotEmpty($token);
    }

    public function testTogglePauseTogglesSetting(): void {
        $this->settings->set('scheduler_paused', '0');
        
        $request = $this->createRequest('POST', '/admin/scheduler/toggle');
        $response = $this->controller->togglePause($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('1', $this->settings->get('scheduler_paused'));
        
        $this->assertEquals('Scheduler successfully paused.', $_SESSION['flash']['success'] ?? null);
    }

    public function testRegenTokenGeneratesNewToken(): void {
        $oldToken = 'test-token-123';
        $this->settings->set('cron_security_token', $oldToken);
        
        $request = $this->createRequest('POST', '/admin/scheduler/regen-token');
        $response = $this->controller->regenToken($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $newToken = $this->settings->get('cron_security_token');
        $this->assertNotEmpty($newToken);
        $this->assertTrue($newToken !== $oldToken);
        
        $this->assertEquals('Web Cron security token regenerated.', $_SESSION['flash']['success'] ?? null);
    }

    public function testRunTaskEnforcesScheduleCheckWhenNotDue(): void {
        // Mark as run just now
        $stmt = $this->db->prepare("INSERT INTO scheduled_tasks (name, last_run_at) VALUES (?, ?)");
        $stmt->execute([$this->command->name, date('Y-m-d H:i:s')]);
        
        $request = $this->createRequest('POST', '/admin/scheduler/run-task', [], ['task' => $this->command->name]);
        $response = $this->controller->runTask($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertFalse($this->command->executed);
        
        // Should flash an error message
        $this->assertNotNull($_SESSION['flash']['error'] ?? null);
        $this->assertStringContainsString('It is not due yet', $_SESSION['flash']['error']);
    }

    public function testRunTaskAllowsExecutionWhenDue(): void {
        // Mark as run 2 hours ago
        $twoHoursAgo = date('Y-m-d H:i:s', time() - 7200);
        $stmt = $this->db->prepare("INSERT INTO scheduled_tasks (name, last_run_at) VALUES (?, ?)");
        $stmt->execute([$this->command->name, $twoHoursAgo]);
        
        $request = $this->createRequest('POST', '/admin/scheduler/run-task', [], ['task' => $this->command->name]);
        $response = $this->controller->runTask($request);
        
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($this->command->executed);
        
        // Verify DB update
        $stmt = $this->db->prepare("SELECT last_run_at FROM scheduled_tasks WHERE name = ?");
        $stmt->execute([$this->command->name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertTrue(strtotime($row['last_run_at']) > time() - 5);
        
        $this->assertEquals('Task \'test:command\' ran successfully.', $_SESSION['flash']['success'] ?? null);
        $this->assertStringContainsString('Mock executed successfully.', $_SESSION['flash']['scheduler_output'] ?? null);
    }

    public function testWebCronAccessDeniedWithInvalidToken(): void {
        $request = $this->createRequest('GET', '/api/cron', ['token' => 'invalid-token']);
        $response = $this->controller->runWebCron($request);
        
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Access Denied', $response->getContent());
        $this->assertFalse($this->command->executed);
    }

    public function testWebCronExecutesOnlyDueTasksWithValidToken(): void {
        // Task is due because it has never run (no record in DB)
        $request = $this->createRequest('GET', '/api/cron', ['token' => 'test-token-123']);
        $response = $this->controller->runWebCron($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($this->command->executed);
        $this->assertStringContainsString('Executing command: test:command', $response->getContent());
    }
}

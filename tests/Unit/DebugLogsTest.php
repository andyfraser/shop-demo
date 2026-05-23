<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\DebugCollector;
use App\Core\View\DebugToolbarComponent;

class DebugLogsTest extends TestCase {
    private string $tempAppLog = __DIR__ . '/../../temp_app_test.log';
    private string $tempErrorLog = __DIR__ . '/../../temp_error_test.log';

    public function setUp(): void {
        DebugCollector::forceEnable(true);

        // Clear session if any
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
        }
        
        // Remove temp log files
        if (file_exists($this->tempAppLog)) {
            unlink($this->tempAppLog);
        }
        if (file_exists($this->tempErrorLog)) {
            unlink($this->tempErrorLog);
        }
    }

    public function testDebugCollectorDoesNotLogToSessionWhenInactive() {
        // Ensure session is inactive
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        $collector = new DebugCollector();
        $collector->logMessage('info', 'Test in-memory only message');

        $this->assertFalse(isset($_SESSION['__debug_redirect_logs']), 'Should not set session key if session is inactive');
    }

    public function testDebugCollectorLogsToSessionWhenActive() {
        // Simulate active session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $_SESSION = [];

        $collector = new DebugCollector();
        $collector->logMessage('warning', 'Test warning message', ['user_id' => 42]);

        $this->assertTrue(isset($_SESSION['__debug_redirect_logs']), 'Should set session key when session is active');
        $this->assertCount(1, $collector->getLogs(), 'Should contain exactly 1 log');
        
        // Check session log structure
        $redirectLogs = $_SESSION['__debug_redirect_logs'];
        $this->assertCount(1, $redirectLogs);
        
        $warningLog = $redirectLogs[0];
        $this->assertEquals('warning', $warningLog['level']);
        $this->assertEquals('Test warning message', $warningLog['message']);
        $this->assertEquals(42, $warningLog['context']['user_id']);
    }

    public function testDebugToolbarComponentMergesAndClearsRedirectLogs() {
        // Simulate active session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        // Pre-populate session with redirect logs from previous request
        $_SESSION['__debug_redirect_logs'] = [
            [
                'level' => 'error',
                'message' => 'Previous request critical error',
                'context' => [],
                'time' => 0.123
            ]
        ];

        // Create new collector for current request context
        $collector = new DebugCollector();
        $collector->logMessage('info', 'Current request normal message');

        $toolbar = new DebugToolbarComponent();
        $html = $toolbar->render();

        // 1. Assert that the redirect logs were cleared from session
        $this->assertFalse(isset($_SESSION['__debug_redirect_logs']), 'Redirect logs must be cleared from the session once rendered');

        // 2. Assert HTML contains both the redirect log and the current request log
        $this->assertStringContainsString('Previous request critical error', $html);
        $this->assertStringContainsString('Current request normal message', $html);
        
        // 3. Assert REDIRECT badge is rendered for previous request logs
        $this->assertStringContainsString('REDIRECT', $html);
    }

    public function testTailLogFilesSafely() {
        // Write mock logs to temp files
        $appLogs = [];
        $errorLogs = [];
        for ($i = 1; $i <= 40; $i++) {
            $appLogs[] = "[2026-05-23 12:00:00] INFO: App message number $i";
            if ($i % 10 === 0) {
                $errorLogs[] = "[2026-05-23 12:00:00] ERROR: Error message number $i";
            }
        }

        file_put_contents($this->tempAppLog, implode(PHP_EOL, $appLogs) . PHP_EOL);
        file_put_contents($this->tempErrorLog, implode(PHP_EOL, $errorLogs) . PHP_EOL);

        $toolbar = new DebugToolbarComponent();
        
        // Use reflection to test the private tail method
        $reflection = new \ReflectionClass(DebugToolbarComponent::class);
        $method = $reflection->getMethod('getRecentLogLines');

        // Get last 30 lines of app log
        $appTail = $method->invokeArgs($toolbar, [$this->tempAppLog, 30]);
        $this->assertCount(30, $appTail, 'Should return exactly 30 lines');
        $this->assertEquals('[2026-05-23 12:00:00] INFO: App message number 40', end($appTail), 'The last element should be the last line in the file');
        $this->assertEquals('[2026-05-23 12:00:00] INFO: App message number 11', reset($appTail), 'The first element should be the 30th line from the end');

        // Get last 30 lines of error log (which has only 4 lines)
        $errorTail = $method->invokeArgs($toolbar, [$this->tempErrorLog, 30]);
        $this->assertCount(4, $errorTail, 'Should return all 4 lines when file has fewer lines than the limit');
        $this->assertEquals('[2026-05-23 12:00:00] ERROR: Error message number 40', end($errorTail));
        $this->assertEquals('[2026-05-23 12:00:00] ERROR: Error message number 10', reset($errorTail));
    }

    public function tearDown(): void {
        DebugCollector::forceEnable(null);

        if (file_exists($this->tempAppLog)) {
            unlink($this->tempAppLog);
        }
        if (file_exists($this->tempErrorLog)) {
            unlink($this->tempErrorLog);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}

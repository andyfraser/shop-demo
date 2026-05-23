<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Core\FileLogger;
use Psr\Log\LogLevel;

class LoggerTest extends TestCase {
    private string $testLog = __DIR__ . '/../../test_app.log';

    public function setUp(): void {
        if (file_exists($this->testLog)) {
            unlink($this->testLog);
        }
    }

    public function testFileLoggerWritesToFile() {
        $logger = new FileLogger($this->testLog);
        $logger->info('Test message');

        $this->assertTrue(file_exists($this->testLog), 'Log file should be created');
        $content = file_get_contents($this->testLog);
        $this->assertStringContainsString('INFO: Test message', $content);
    }

    public function testInterpolation() {
        $logger = new FileLogger($this->testLog);
        $logger->warning('User {name} failed login', ['name' => 'admin']);

        $content = file_get_contents($this->testLog);
        $this->assertStringContainsString('WARNING: User admin failed login', $content);
    }

    public function testMultipleLevels() {
        $logger = new FileLogger($this->testLog, true);
        $logger->error('Error occurred');
        $logger->debug('Debug info');

        $content = file_get_contents($this->testLog);
        $this->assertStringContainsString('ERROR: Error occurred', $content);
        $this->assertStringContainsString('DEBUG: Debug info', $content);
    }

    public function testDebugLoggingEnabled() {
        $logger = new FileLogger($this->testLog, true);
        $logger->debug('Debug message when enabled');

        $content = file_get_contents($this->testLog);
        $this->assertStringContainsString('DEBUG: Debug message when enabled', $content);
    }

    public function testDebugLoggingDisabled() {
        $logger = new FileLogger($this->testLog, false);
        $logger->debug('Debug message when disabled');
        $logger->info('Info message still logged');

        $content = file_get_contents($this->testLog);
        $this->assertStringNotContainsString('DEBUG: Debug message when disabled', $content);
        $this->assertStringContainsString('INFO: Info message still logged', $content);
    }

    public function testConfigExampleHasLogPathsDefined() {
        $configExampleFile = __DIR__ . '/../../config/config.example.php';
        $this->assertTrue(file_exists($configExampleFile), 'config.example.php should exist');
        
        $config = require $configExampleFile;
        $this->assertTrue(isset($config['app']), 'config.example.php must have app config');
        $this->assertTrue(isset($config['app']['log_path']), 'config.example.php must have log_path');
        $this->assertTrue(isset($config['app']['error_log_path']), 'config.example.php must have error_log_path');
        
        $this->assertStringContainsString('logs/app.log', $config['app']['log_path']);
        $this->assertStringContainsString('logs/error.log', $config['app']['error_log_path']);
    }

    public function tearDown(): void {
        if (file_exists($this->testLog)) {
            unlink($this->testLog);
        }
    }
}

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

    public function testRotation() {
        $logger = new FileLogger($this->testLog);
        $logger->info('Yesterday log');
        
        // Force file time to yesterday
        $yesterday = strtotime('-1 day');
        touch($this->testLog, $yesterday);
        
        $logger->info('Today log');
        
        $rotatedFile = __DIR__ . '/../../test_app-' . date('Y-m-d', $yesterday) . '.log';
        $this->assertTrue(file_exists($rotatedFile), 'Rotated file should exist');
        $this->assertTrue(file_exists($this->testLog), 'New log file should exist');
        
        $this->assertStringContainsString('Yesterday log', file_get_contents($rotatedFile));
        $this->assertStringContainsString('Today log', file_get_contents($this->testLog));
        
        if (file_exists($rotatedFile)) unlink($rotatedFile);
    }

    public function testCleanup() {
        $logger = new FileLogger($this->testLog, false, 7); // 7 days retention
        
        $oldFile = __DIR__ . '/../../test_app-2000-01-01.log';
        file_put_contents($oldFile, 'Ancient log');
        touch($oldFile, strtotime('-10 days')); // Older than 7 days
        
        $logger->info('Trigger cleanup');
        
        // We need to trigger a rotation to run cleanup, OR we can just test cleanup specifically if it was public.
        // But log() calls rotate() which calls cleanup(). 
        // rotate() only runs if the main log file is "from another day".
        touch($this->testLog, strtotime('-1 day'));
        $logger->info('Rotating now');

        $this->assertFalse(file_exists($oldFile), 'Old log file should be deleted');
        
        $rotatedOld = __DIR__ . '/../../test_app-' . date('Y-m-d', strtotime('-1 day')) . '.log';
        if (file_exists($rotatedOld)) unlink($rotatedOld);
    }

    public function tearDown(): void {
        if (file_exists($this->testLog)) {
            unlink($this->testLog);
        }
    }
}

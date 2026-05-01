<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Commands\RotateLogsCommand;

class RotateLogsCommandTest extends TestCase {
    private string $testLogDir = __DIR__ . '/../../test_logs';

    public function setUp(): void {
        if (!is_dir($this->testLogDir)) {
            mkdir($this->testLogDir, 0755, true);
        }
        $this->cleanDir();
    }

    private function cleanDir(): void {
        $files = glob($this->testLogDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    }

    public function testRotation() {
        $logFile = $this->testLogDir . '/app.log';
        file_put_contents($logFile, 'Yesterday log content');
        
        $yesterday = strtotime('-1 day');
        touch($logFile, $yesterday);
        
        $command = new RotateLogsCommand($this->testLogDir, 30);
        
        ob_start();
        $command->execute();
        ob_end_clean();
        
        $expectedRotatedFile = $this->testLogDir . '/app-' . date('Y-m-d', $yesterday) . '.log';
        $this->assertTrue(file_exists($expectedRotatedFile), 'Log file should be rotated');
        $this->assertFalse(file_exists($logFile), 'Original log file should be moved');
        $this->assertStringContainsString('Yesterday log content', file_get_contents($expectedRotatedFile));
    }

    public function testCleanup() {
        $oldFile = $this->testLogDir . '/app-2000-01-01.log';
        file_put_contents($oldFile, 'Ancient content');
        touch($oldFile, strtotime('-40 days')); // Older than 30 days
        
        $command = new RotateLogsCommand($this->testLogDir, 30);
        
        ob_start();
        $command->execute();
        ob_end_clean();
        
        $this->assertFalse(file_exists($oldFile), 'Old rotated file should be deleted');
    }

    public function testHandlesMultipleLogFiles() {
        $log1 = $this->testLogDir . '/web.log';
        $log2 = $this->testLogDir . '/db.log';
        
        file_put_contents($log1, 'web');
        file_put_contents($log2, 'db');
        
        $yesterday = strtotime('-1 day');
        touch($log1, $yesterday);
        touch($log2, $yesterday);
        
        $command = new RotateLogsCommand($this->testLogDir, 30);
        
        ob_start();
        $command->execute();
        ob_end_clean();
        
        $this->assertTrue(file_exists($this->testLogDir . '/web-' . date('Y-m-d', $yesterday) . '.log'));
        $this->assertTrue(file_exists($this->testLogDir . '/db-' . date('Y-m-d', $yesterday) . '.log'));
    }

    public function tearDown(): void {
        $this->cleanDir();
        if (is_dir($this->testLogDir)) {
            rmdir($this->testLogDir);
        }
    }
}
